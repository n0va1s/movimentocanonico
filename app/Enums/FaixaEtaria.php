<?php

namespace App\Enums;

enum FaixaEtaria: string
{
    case ADOLESCENTE = '12-15';
    case JOVEM = '16-23';
    case IDOSO = '60+';
    case CASADO = 'CSDO';
    case SOLTEIRO = 'SLTRO';
    case LIVRE = 'Livre';

    public function label(): string
    {
        return match ($this) {
            self::ADOLESCENTE => '12 a 15 anos',
            self::JOVEM => '16 a 23 anos',
            self::IDOSO => '60 anos ou mais',
            self::CASADO => 'Casado',
            self::SOLTEIRO => 'Solteiro',
            self::LIVRE => 'Livre para todos os públicos',
        };
    }
}
