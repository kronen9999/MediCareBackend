<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class cuidadoresNotificarFamiliarRecuperacion extends Mailable
{
    use Queueable, SerializesModels;

    public $correo;
    public $usuario;

    /**
     * Create a new message instance.
     */
    public function __construct($correo, $usuario)
    {
        $this->correo = $correo;
        $this->usuario = $usuario;
    }
    

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Notificacion de Recuperacion de Contraseña',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'Mails.cuidadoresNotificarFamiliarRecuperacion',
            with:[
                'correo' => $this->correo,
                'usuario' => $this->usuario
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
