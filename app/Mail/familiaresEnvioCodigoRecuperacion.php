<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FamiliaresEnvioCodigoRecuperacion extends Mailable
{
    use Queueable, SerializesModels;

    public $correoE;
    public $codigoRecuperacion;

    /**
     * Create a new message instance.
     */
    public function __construct($correoE,$codigoRecuperacion)
    {
        $this->correoE = $correoE;
        $this->codigoRecuperacion = $codigoRecuperacion;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Codigo de recuperacion',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'Mails.familiaresEnvioCodigoRecuperacion',
            with: [
                'correoE' => $this->correoE,
                'codigoRecuperacion' => $this->codigoRecuperacion
            ]
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
