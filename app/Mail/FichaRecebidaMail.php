<?php

namespace App\Mail;

use App\Models\Ficha;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FichaRecebidaMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Ficha $ficha;

    public function __construct(Ficha $ficha)
    {
        $this->ficha = $ficha;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), 'Movimento Canônico'),
            subject: 'Recebemos a Ficha de ' . $this->ficha->nom_candidato,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.ficha_recebida',
            with: [
                'candidato' => $this->ficha->nom_candidato,
                'evento' => $this->ficha->evento ? $this->ficha->evento->nom_evento : 'o evento',
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
