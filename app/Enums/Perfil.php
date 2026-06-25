<?php

namespace App\Enums;

enum Perfil: string
{
    case ADMIN = 'admin';
    case COORD = 'coord';
    case ESPEC = 'espec';
    case USER = 'user';
    case VISIT = 'visit';
    case SALES = 'sales';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrador',
            self::COORD => 'Coordenador',
            self::ESPEC => 'Especialista',
            self::USER => 'Usuário',
            self::VISIT => 'Visitação',
            self::SALES => 'Mercadinho',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::ADMIN => 'shield-exclamation',
            self::COORD => 'users',
            self::ESPEC => 'wrench-screwdriver',
            self::USER => 'user',
            self::VISIT => 'document-text',
            self::SALES => 'shopping-cart',
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
        return [self::ADMIN->value, self::COORD->value, self::ESPEC->value];
    }

    /**
     * Mapa de quais perfis têm acesso a cada aba do gerenciamento de evento.
     * coord e espec só têm acesso se estiverem trabalhando no evento (verificado via Gate).
     */
    public static function abasPermitidas(): array
    {
        return [
            'resumo' => [self::ADMIN->value, self::COORD->value, self::ESPEC->value],
            'participantes' => [self::ADMIN->value, self::COORD->value, self::ESPEC->value],
            'trabalhadores' => [self::ADMIN->value, self::COORD->value, self::ESPEC->value],
            'presenca' => [self::ADMIN->value, self::COORD->value, self::ESPEC->value],
            'crachas' => [self::ADMIN->value, self::COORD->value, self::ESPEC->value],
            'quadrante' => [self::ADMIN->value, self::COORD->value, self::ESPEC->value],
            'fichas' => [self::ADMIN->value, self::ESPEC->value],
            'voluntarios' => [self::ADMIN->value, self::ESPEC->value],
            'contas' => [self::ADMIN->value, self::ESPEC->value],
            'restricoes' => [self::ADMIN->value, self::COORD->value, self::ESPEC->value],
        ];
    }
}
