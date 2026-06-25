<?php

namespace App\Enums;

enum Religiao: string
{
    case CATOLICA = 'C';
    case EVANGELICA = 'E';
    case ESPIRITA = 'S';
    case MATRIZ_AFRICANA = 'A';
    case ORIENTAL = 'O';
    case JUDAICA = 'J';
    case ISLAMICA = 'I';
    case SEM_RELIGIAO = 'N';
    case OUTRA = 'T';

    public function label(): string
    {
        return match ($this) {
            self::CATOLICA => 'Católica',
            self::EVANGELICA => 'Evangélica',
            self::ESPIRITA => 'Espírita',
            self::MATRIZ_AFRICANA => 'Matriz Africana',
            self::ORIENTAL => 'Oriental',
            self::JUDAICA => 'Judaica',
            self::ISLAMICA => 'Islamica',
            self::SEM_RELIGIAO => 'Sem Religião',
            self::OUTRA => 'Outra',
        };
    }
}
