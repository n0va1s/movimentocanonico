<?php

namespace App\Enums;

enum Perfil: string
{
    case ADMIN = 'admin';
    case COORD = 'coord';
    case DIRIG = 'dirig';
    case USER = 'user';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrador',
            self::COORD => 'Coordenador',
            self::DIRIG => 'Dirigente',
            self::USER => 'Usuário',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::ADMIN => 'shield-exclamation',
            self::COORD => 'users',
            self::DIRIG => 'wrench-screwdriver',
            self::USER => 'user',
        };
    }

    /**
     * Retorna os perfis que têm acesso à área administrativa (configurações, contatos, gerenciar eventos).
     */
    public static function adminOnly(): array
    {
        return [self::ADMIN->value];
    }

    /**
     * Retorna os perfis que podem acessar o gerenciamento de eventos (qualquer aba permitida).
     */
    public static function gestores(): array
    {
        return [self::ADMIN->value, self::COORD->value, self::DIRIG->value];
    }

    /**
     * Mapa de quais perfis têm acesso a cada aba do gerenciamento de evento.
     * coord e espec só têm acesso se estiverem trabalhando no evento (verificado via Gate).
     */
    public static function abasPermitidas(): array
    {
        return [
            'resumo' => [self::ADMIN->value, self::COORD->value, self::DIRIG->value],
            'participantes' => [self::ADMIN->value, self::COORD->value, self::DIRIG->value],
            'trabalhadores' => [self::ADMIN->value, self::COORD->value, self::DIRIG->value],
            'presenca' => [self::ADMIN->value, self::COORD->value, self::DIRIG->value],
            'crachas' => [self::ADMIN->value, self::DIRIG->value],
            'quadrante' => [self::ADMIN->value, self::COORD->value, self::DIRIG->value],
            'fichas' => [self::ADMIN->value, self::COORD->value, self::DIRIG->value],
            'voluntarios' => [self::ADMIN->value, self::DIRIG->value],
            'contas' => [self::ADMIN->value, self::DIRIG->value],
            'restricoes' => [self::ADMIN->value, self::COORD->value, self::DIRIG->value],
        ];
    }
}
