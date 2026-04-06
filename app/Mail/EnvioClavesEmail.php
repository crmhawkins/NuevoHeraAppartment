<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EnvioClavesEmail extends Mailable
{
    use Queueable, SerializesModels;

    protected $vista;
    protected $data;
    protected $asunto;
    // protected $titulo;
    protected $token = null;

    /**
     * Create a new message instance.
     */
    public function __construct($vista, $data, $asunto, $token = null,)
    {
        $this->vista = $vista;
        $this->data = $data;
        $this->asunto = $asunto;
        // $this->titulo = $titulo;
        $token != null ? $this->token = $token : $this->token = null;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->asunto,
            cc: ['david@hawkins.es']
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: $this->vista,
            with: ['data' => $this->data]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
