<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
// use Webklex\IMAP\Client;  // Si usas la librería Webklex/IMAP para conectar al correo
use App\Models\Email; // Asegúrate de que esto apunte a tu modelo 'Email'
use Webklex\IMAP\Facades\Client;

class FetchEmails extends Command
{
    // El nombre y la firma del comando de consola
    protected $signature = 'emails:fetch';

    // La descripción del comando de consola
    protected $description = 'Fetch unseen emails and store them in the database';

    public function __construct()
    {
        parent::__construct();
    }

    // Ejecuta el comando
    public function handle()
    {
        // Crear un cliente IMAP para conectar al servidor de correo
        $client = Client::account('default');
        $client->connect();

        // Obtener la carpeta INBOX
        $inbox = $client->getFolder('INBOX');

        // Obtener todos los correos no leídos
        $messages = $inbox->messages()->unseen()->get(); // También puedes probar con recent()

        // Procesar solo los primeros 10 correos
        $counter = 0;
        foreach ($messages as $message) {
            if ($counter >= 10) break; // Salir del loop después de procesar 10 mensajes

            // Procesar cada correo
            $sender = $message->getFrom()[0]->mail;
            $subject = $message->getSubject();
            $body = $message->getHTMLBody() ?: $message->getTextBody();
            $messageId = $message->getMessageId(); // Obtiene el Message-ID del correo original

            // Guardar en la base de datos o hacer algo con los correos
            Email::create([
                'sender' => $sender,
                'subject' => $subject,
                'body' => $body,
                'message_id' => $messageId,
            ]);

            // Marcar el correo como leído
            $message->setFlag('Seen');

            // Aumentar el contador de correos procesados
            $counter++;
        }

        // Desconectar el cliente IMAP
        $client->disconnect();

        $this->info('Emails fetched and processed successfully.');
    }
}
