<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Email;
use OpenAI\Client as OpenAIClient; // Asegúrate de tener un cliente OpenAI adecuado
use Illuminate\Support\Facades\Mail;
use Webklex\IMAP\Facades\Client as ImapClient;
use Illuminate\Support\Facades\Log;
use OpenAI;
use Symfony\Component\Mime\Part\TextPart;
use Symfony\Component\Mime\Part\HtmlPart;

class ReplyToCategorizedEmails extends Command
{
    // El nombre y la firma del comando de consola
    protected $signature = 'emails:auto-reply';

    // La descripción del comando de consola
    protected $description = 'Auto reply to categorized emails that are not spam using OpenAI';

    public function __construct()
    {
        parent::__construct();
    }

    // Ejecuta el comando
    public function handle()
    {
        // Obtener los correos categorizados que no sean spam
        $emailsToReply = Email::whereNotNull('category_id')
            ->whereHas('category', function ($query) {
                $query->where('name', '!=', 'Spam');
            })
            ->get();

        if ($emailsToReply->isEmpty()) {
            $this->info('No emails found to reply to.');
            return;
        }

        foreach ($emailsToReply as $email) {
            try {
                $this->info('Processing email ID: ' . $email->id);
                
                // Llamada a OpenAI para generar la respuesta
                $replyContent = $this->generateReplyFromOpenAI($email);
                if (!$replyContent) {
                    $this->error('No reply generated for email ID: ' . $email->id);
                    continue;
                }

                // Conectar a la cuenta de correo para responder
                $client = ImapClient::account('default');
                $client->connect();

                // Cargar el correo original desde la carpeta
                $inbox = $client->getFolder('INBOX');
                $originalMessage = $inbox->messages()->whereMessageId($email->message_id)->get()->first();

                if (!$originalMessage) {
                    $this->error('Original email not found for email ID: ' . $email->id);
                    $client->disconnect();
                    continue;
                }

                // Preparar y enviar la respuesta por correo
                $this->sendEmailReply($originalMessage, $email, $replyContent);

                $client->disconnect();
                $this->info('Reply sent successfully for email ID: ' . $email->id);
                
            } catch (\Exception $e) {
                Log::error('Error processing email ID ' . $email->id . ': ' . $e->getMessage());
            }
        }
    }

    // Función para generar la respuesta desde OpenAI
    private function generateReplyFromOpenAI($email)
    {
      $openai = OpenAI::client(env('OPENAI_API_KEY'));

        try {
            $instructionsFilePath = public_path('instructions.txt');
            $instructionsContent = file_exists($instructionsFilePath) ? file_get_contents($instructionsFilePath) : 'You are an intelligent email assistant. Write a polite and helpful response based on the email provided.';

            // Instrucciones de comportamiento para el modelo
            $systemMessage = [
                'role' => 'system',
                'content' => $instructionsContent,
            ];

            // Crear el mensaje del usuario con el contenido del email a responder
            $userMessage = [
                'role' => 'user',
                'content' => "You need to reply to the following email:\n\n"
                    . "Subject: " . $email->subject . "\n\n"
                    . "Body: " . $email->body . "\n\n"
                    . "Write a response to this email:",
            ];

            // Hacer la solicitud al modelo chat de OpenAI
            $response = $openai->chat()->create([
                'model' => 'gpt-4',
                'messages' => [
                    $systemMessage,
                    $userMessage
                ],
                'max_tokens' => 200,
                'temperature' => 0.5,
            ]);

            // Guardar la respuesta completa en un archivo de log para diagnóstico
            $logPath = storage_path('logs/openai_responses.log');
            file_put_contents($logPath, "Response for email ID: " . $email->id . "\n" . json_encode($response, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);

            // Validar la respuesta de OpenAI
            if (!isset($response['choices']) || empty($response['choices'])) {
                throw new \Exception('No response from OpenAI.');
            }

            return trim($response['choices'][0]['message']['content']);
        } catch (\Exception $e) {
            Log::error('Error in OpenAI request for email ID: ' . $email->id . ' - ' . $e->getMessage());
            return null;
        }
    }


    // Función para enviar la respuesta por correo
    // Función para enviar la respuesta por correo
    private function sendEmailReply($originalMessage, $email, $replyContent)
    {
        $recipient = $email->sender;  // Destinatario original
        $messageId = $originalMessage->getMessageId();
        $bccRecipients = ['helena@hawkins.es', 'ivan@hawkins.es', 'david@hawkins.es'];

        Mail::send([], [], function ($message) use ($recipient, $email, $replyContent, $messageId, $bccRecipients) {
          $message->to($recipient)
                  ->bcc($bccRecipients)
                  ->subject('Re: ' . $email->subject)
                  ->setBody($replyContent, 'text/html') // Configura el cuerpo del mensaje como HTML
                  ->setReplyTo($recipient)
                  ->getHeaders()
                  ->addTextHeader('In-Reply-To', $messageId)
                  ->addTextHeader('References', $messageId);
      });
      
    }
}
