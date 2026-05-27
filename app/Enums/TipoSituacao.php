<?php

namespace App\Enums;

enum TipoSituacao: string
{
    case CADASTRADO = 'C';
    case AVALIADO = 'A';
    case VISITADO = 'V';
    case APROVADO = 'D';

    public function label(): string
    {
        return match ($this) {
            self::CADASTRADO => 'Cadastrado',
            self::AVALIADO => 'Avaliado',
            self::VISITADO => 'Visitado',
            self::APROVADO => 'Aprovado',
        };
    }
}
