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

    public function badgeClass(): string
    {
        return match ($this) {
            self::VERMELHA => 'bg-red-100 text-red-800 dark:bg-red-950/40 dark:text-red-300 border border-red-200 dark:border-red-800',
            self::AZUL => 'bg-blue-100 text-blue-800 dark:bg-blue-950/40 dark:text-blue-300 border border-blue-200 dark:border-blue-800',
            self::VERDE => 'bg-green-100 text-green-800 dark:bg-green-950/40 dark:text-green-300 border border-green-200 dark:border-green-800',
            self::AMARELA => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-950/30 dark:text-yellow-300 border border-yellow-200 dark:border-yellow-800',
            self::LARANJA => 'bg-orange-100 text-orange-800 dark:bg-orange-950/40 dark:text-orange-300 border border-orange-200 dark:border-orange-800',
        };
    }
}
