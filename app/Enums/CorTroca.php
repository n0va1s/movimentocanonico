<?php

namespace App\Enums;

enum CorTroca: string
{
    case VERMELHA = 'vermelha';
    case AZUL = 'azul';
    case VERDE = 'verde';
    case AMARELA = 'amarela';
    case LARANJA = 'laranja';

    public function label(): string
    {
        return match ($this) {
            self::VERMELHA => 'Vermelha',
            self::AZUL => 'Azul',
            self::VERDE => 'Verde',
            self::AMARELA => 'Amarela',
            self::LARANJA => 'Laranja',
        };
    }

    public function cor(): string
    {
        return match ($this) {
            self::VERMELHA => '#FF0000',
            self::AZUL => '#0000FF',
            self::VERDE => '#008000',
            self::AMARELA => '#FFFF00',
            self::LARANJA => '#FFA500',
        };
    }

    public function borderLClass(): string
    {
        return match ($this) {
            self::VERMELHA => 'border-l-red-500 dark:border-l-red-400',
            self::AZUL => 'border-l-blue-500 dark:border-l-blue-400',
            self::VERDE => 'border-l-green-500 dark:border-l-green-400',
            self::AMARELA => 'border-l-yellow-400 dark:border-l-yellow-400',
            self::LARANJA => 'border-l-orange-400 dark:border-l-orange-400',
        };
    }
}
