<?php

namespace App\Enums;

enum TipoRestricao: string
{
    case INT = 'INT';
    case ALE = 'ALE';
    case CUT = 'CUT';
    case RES = 'RES';
    case PNE = 'PNE';
    case VEG = 'VEG';
    case MED = 'MED';

    public function label(): string
    {
        return match ($this) {
            self::INT => 'Intolerância',
            self::ALE => 'Alergia',
            self::CUT => 'Cutânea',
            self::RES => 'Respiratória',
            self::PNE => 'PNE',
            self::VEG => 'Vegetariano',
            self::MED => 'Medicação',
            self::NDV => 'Neurodivergente'
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::INT => '🥛',
            self::ALE => '🌿',
            self::CUT => '🩹',
            self::RES => '💨',
            self::PNE => '♿',
            self::VEG => '🥗',
            self::MED => '💊',
            self::NDV => '🧩',
        };
    }

    public function placeholder(): string
    {
        return match ($this) {
            self::ALE => 'Ex: amendoim, camarão...',
            self::INT => 'Ex: lactose, glúten...',
            self::MED => 'Ex: dipirona, AAS...',
            self::CUT => 'Ex: látex, níquel...',
            self::PNE => 'Descreva a necessidade...',
            self::VEG => 'Ex: vegano, ovolactovegetariano...',
            self::RES => 'Ex: poeira, pólen...',
            self::NDV => 'Ex: espectro autista, TDAH...',
        };
    }
}
