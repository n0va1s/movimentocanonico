<?php

namespace App\Listeners;

use App\Events\FichaAprovadaEvent;
use App\Models\TipoMovimento;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use App\Mail\FichaAprovadaMail;

class EnviarEmailFichaAprovada
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(FichaAprovadaEvent $event): void
    {
        $ficha = $event->ficha;
        
        $ficha->loadMissing('evento');
        $responsavelInfo = $ficha->responsavel_info;
        
        $emails = [];
        if (!empty($responsavelInfo['email'])) {
            $emails[] = $responsavelInfo['email'];
        }

        // Se for ECC, também envia para o candidato (ambos os cônjuges)
        if ($ficha->evento && $ficha->evento->idt_movimento === TipoMovimento::ECC) {
            if (!empty($ficha->eml_candidato)) {
                $emails[] = $ficha->eml_candidato;
            }
        } else {
            // Se não tiver email de responsável/mãe/pai mas for VEM/SGM e tiver email do candidato, envia pra ele
            if (empty($emails) && !empty($ficha->eml_candidato)) {
                $emails[] = $ficha->eml_candidato;
            }
        }

        $emails = array_unique(array_filter($emails));

        if (!empty($emails)) {
            Mail::to($emails)->send(new FichaAprovadaMail($ficha));
        }
    }
}
