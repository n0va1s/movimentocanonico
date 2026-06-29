<?php

namespace App\Observers;

use App\Models\Ficha;

class FichaObserver
{
    /**
     * Handle the Ficha "created" event.
     */
    public function created(Ficha $ficha): void
    {
        //
    }

    /**
     * Handle the Ficha "updated" event.
     */
    public function updated(Ficha $ficha): void
    {
        if ($ficha->wasChanged('tip_situacao')) {
            if ($ficha->tip_situacao === \App\Enums\TipoSituacao::APROVADA) {
                \App\Events\FichaAprovadaEvent::dispatch($ficha);
            } elseif ($ficha->tip_situacao === \App\Enums\TipoSituacao::ENVIADA) {
                \App\Events\FichaEnviadaEvent::dispatch($ficha);
            }
        }
    }

    /**
     * Handle the Ficha "deleted" event.
     */
    public function deleted(Ficha $ficha): void
    {
        //
    }

    /**
     * Handle the Ficha "restored" event.
     */
    public function restored(Ficha $ficha): void
    {
        //
    }

    /**
     * Handle the Ficha "force deleted" event.
     */
    public function forceDeleted(Ficha $ficha): void
    {
        //
    }
}
