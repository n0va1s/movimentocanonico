<?php

namespace App\Events;

use App\Models\Ficha;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FichaAprovadaEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $ficha;

    /**
     * Create a new event instance.
     */
    public function __construct(Ficha $ficha)
    {
        $this->ficha = $ficha;
    }
}
