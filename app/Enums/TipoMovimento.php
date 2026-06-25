<?php

namespace App\Enums;

enum TipoMovimento: int
{
    case ECC = 1;
    case VEM = 2;
    case SGM = 3;

    public function label(): string
    {
        return match ($this) {
            self::ECC => 'Encontro de Casais com Cristo',
            self::VEM => 'Encontro de Adolescentes com Cristo',
            self::SGM => 'Encontro de Jovens com Cristo',
        };
    }

    public function sigla(): string
    {
        return match ($this) {
            self::ECC => 'ECC',
            self::VEM => 'VEM',
            self::SGM => 'Segue-Me',
        };
    }
}
