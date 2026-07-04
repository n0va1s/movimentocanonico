<?php

namespace App\Listeners;

use App\Events\FichaRecebidaEvent;
use App\Models\TipoMovimento;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use App\Mail\FichaRecebidaMail;

class EnviarEmailFichaRecebida
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
    public function handle(FichaRecebidaEvent $event): void
    {
        $ficha = $event->ficha;
        $emails = [];

        // Certifica-se de que a relação evento está carregada para pegar o idt_movimento
        $ficha->loadMissing('evento');

        if ($ficha->evento && $ficha->evento->idt_movimento === TipoMovimento::ECC) {
            if (!empty($ficha->eml_candidato)) {
                $emails[] = $ficha->eml_candidato;
            }
            if ($ficha->fichaEcc && !empty($ficha->fichaEcc->eml_conjuge)) {
                $emails[] = $ficha->fichaEcc->eml_conjuge;
            }
        } else {
            // Lógica unificada para VEM e SGM
            $responsavelInfo = $ficha->responsavel_info;
            if (!empty($responsavelInfo['email'])) {
                $emails[] = $responsavelInfo['email'];
            } elseif (!empty($ficha->eml_candidato)) {
                $emails[] = $ficha->eml_candidato;
            }
        }

        $emails = array_unique(array_filter($emails));

        if (!empty($emails)) {
            Mail::to($emails)->send(new FichaRecebidaMail($ficha));
        }
    }
}
