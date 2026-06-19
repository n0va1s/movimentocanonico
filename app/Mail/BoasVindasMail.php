<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BoasVindasMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public User $user;

    public string $senhaTemporaria;

    public function __construct(User $user, string $senhaTemporaria)
    {
        $this->user = $user;
        $this->senhaTemporaria = $senhaTemporaria;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), 'Movimento Canônico'),
            subject: 'Bem-vindo(a) ao Movimento Canônico da Paróquia Nossa Senhora do Lago',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.boasvindas',
            with: [
                'nome' => $this->user->name,
                'email' => $this->user->email,
                'senha' => $this->senhaTemporaria,
            ]
        );
    }

    /**
     * Obtenha os anexos da mensagem.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
