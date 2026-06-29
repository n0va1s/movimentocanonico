<?php

namespace App\Listeners;

use App\Events\FichaEnviadaEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use App\Mail\FichaEnviadaMail;
use Illuminate\Support\Facades\Log;

class EnviarEmailFichaEnviada
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
    public function handle(FichaEnviadaEvent $event): void
    {
        $ficha = $event->ficha;
        
        $ficha->loadMissing('evento');
        $responsavelInfo = $ficha->responsavel_info;
        
        $email = $responsavelInfo['email'] ?? null;
        
        if (!empty($email)) {
            Mail::to($email)->send(new FichaEnviadaMail($ficha));
        } else {
            Log::warning('Ficha Enviada sem email de responsável', ['ficha_id' => $ficha->idt_ficha]);
        }
    }
}
