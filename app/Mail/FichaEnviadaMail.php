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

class FichaEnviadaMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Ficha $ficha;

    /**
     * Create a new message instance.
     */
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
            from: new Address(config('mail.from.address', 'contato@canonico.com.br'), 'Movimento Canônico'),
            subject: 'Autorização e Ciência de Inscrição - ' . $this->ficha->nom_candidato,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $movimento = $this->ficha->evento && $this->ficha->evento->movimento 
            ? $this->ficha->evento->movimento->nom_movimento 
            : 'Movimento';

        $siglaMovimento = $this->ficha->evento && $this->ficha->evento->movimento 
            ? $this->ficha->evento->movimento->des_sigla 
            : 'Movimento';

        $dataInicio = $this->ficha->evento && $this->ficha->evento->dat_inicio 
            ? $this->ficha->evento->dat_inicio->format('d/m/Y') 
            : 'data a definir';

        $dataTermino = $this->ficha->evento && $this->ficha->evento->dat_termino 
            ? $this->ficha->evento->dat_termino->format('d/m/Y') 
            : 'data a definir';

        $urlAutorizacao = \Illuminate\Support\Facades\URL::signedRoute('fichas.autorizar', ['ficha' => $this->ficha->idt_ficha]);

        return new Content(
            view: 'emails.ficha_enviada',
            with: [
                'candidato' => $this->ficha->nom_candidato,
                'nomeMovimento' => $movimento,
                'siglaMovimento' => $siglaMovimento,
                'dataInicio' => $dataInicio,
                'dataTermino' => $dataTermino,
                'evento' => $this->ficha->evento ? $this->ficha->evento->des_evento : 'o evento',
                'paroquia' => 'Paróquia Nossa Senhora do Lago',
                'urlAutorizacao' => $urlAutorizacao,
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
