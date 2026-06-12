<?php

namespace App\Enums;

enum TipoResponsavel: int
{
    case PAI = 1;
    case MAE = 2;
    case AVO_MASC = 3;
    case AVO_FEM = 4;
    case TIO = 5;
    case TIA = 6;
    case PADRINHO = 7;
    case MADRINHA = 8;
    case OUTRO = 9;

    public function label(): string
    {
        return match ($this) {
            self::PAI => 'Pai',
            self::MAE => 'Mãe',
            self::AVO_MASC => 'Avô',
            self::AVO_FEM => 'Avó',
            self::TIO => 'Tio',
            self::TIA => 'Tia',
            self::PADRINHO => 'Padrinho',
            self::MADRINHA => 'Madrinha',
            self::OUTRO => 'Outro(a)',
        };
    }
}
