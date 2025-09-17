<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class cuidadoresEnvioCodigoVerifiacion extends Mailable
{
    use Queueable, SerializesModels;

    public $correo;
    public $codigo;

    /**
     * Create a new message instance.
     */
    public function __construct($correo, $codigo)
    {
        $this->correo = $correo;
        $this->codigo = $codigo;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Envio de codigo de recuperacion',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'Mails.cuidadoresEnvioCodigoVerifiacion',
            with: [
                'correo' => $this->correo,
                'codigo' => $this->codigo,
            ],
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
