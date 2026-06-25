<?php

namespace App\Enums;

enum EscolaridadeSituacao: string
{
    case CURSANDO = 'C';
    case CONCLUIDO = 'O';
    case TRANCADO = 'T';
    case INTERROMPIDO = 'I';

    public function label(): string
    {
        return match ($this) {
            self::CURSANDO => 'Cursando',
            self::CONCLUIDO => 'Concluído',
            self::TRANCADO => 'Trancado',
            self::INTERROMPIDO => 'Interrompido',
        };
    }
}
