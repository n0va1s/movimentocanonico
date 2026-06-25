<?php

namespace App\Enums;

enum Escolaridade: string
{
    case FUNDAMENTAL = 'F';
    case MEDIO = 'M';
    case SUPERIOR = 'S';
    case POS_GRADUACAO = 'P';

    public function label(): string
    {
        return match ($this) {
            self::FUNDAMENTAL => 'Fundamental',
            self::MEDIO => 'Médio',
            self::SUPERIOR => 'Superior',
            self::POS_GRADUACAO => 'Pós-Graduação',
        };
    }
}
