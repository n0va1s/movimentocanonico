<?php

namespace App\Enums;

enum TipoSituacao: string
{
    case NOVA = 'N'; // criada pelos pais ou pelo candidato
    case SELECIONADA = 'S'; // selecionada por atender aos requisitos 
    case ENVIADA = 'E'; // enviada por email para confirmacao por um dos pais
    case RECEBIDA = 'R'; // documentacao recebida
    case PAGA = 'P'; // confirmada com pagamento
    case CANCELADA = 'C'; // nao podera participar
    case APROVADA = 'A'; // enviar email com a confirmação do candidato e o resumo de informações do evento

    public function label(): string
    {
        return match ($this) {
            self::NOVA => 'Nova',
            self::SELECIONADA => 'Selecionada',
            self::ENVIADA => 'Enviada',
            self::RECEBIDA => 'Recebida',
            self::PAGA => 'Paga',
            self::CANCELADA => 'Cancelada',
            self::APROVADA => 'Aprovada',
        };
    }

    public function badge(): array
    {
         return match ($this) {
            self::NOVA => ['bg' => 'bg-slate-500', 'text' => 'text-slate-500', 'hover' => 'hover:bg-slate-600 hover:border-slate-600', 'border' => 'border-slate-500', 'light' => 'bg-slate-50 dark:bg-slate-900/20 text-slate-700 dark:text-slate-300 border-slate-300 dark:border-slate-700'],
            self::SELECIONADA => ['bg' => 'bg-indigo-500', 'text' => 'text-indigo-500', 'hover' => 'hover:bg-indigo-600 hover:border-indigo-600', 'border' => 'border-indigo-500', 'light' => 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300 border-indigo-300 dark:border-indigo-700'],
            self::ENVIADA => ['bg' => 'bg-blue-500', 'text' => 'text-blue-500', 'hover' => 'hover:bg-blue-600 hover:border-blue-600', 'border' => 'border-blue-500', 'light' => 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 border-blue-300 dark:border-blue-700'],
            self::RECEBIDA => ['bg' => 'bg-teal-500', 'text' => 'text-teal-500', 'hover' => 'hover:bg-teal-600 hover:border-teal-600', 'border' => 'border-teal-500', 'light' => 'bg-teal-50 dark:bg-teal-900/20 text-teal-700 dark:text-teal-300 border-teal-300 dark:border-teal-700'],
            self::PAGA => ['bg' => 'bg-emerald-500', 'text' => 'text-emerald-500', 'hover' => 'hover:bg-emerald-600 hover:border-emerald-600', 'border' => 'border-emerald-500', 'light' => 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300 border-emerald-300 dark:border-emerald-700'],
            self::CANCELADA => ['bg' => 'bg-rose-500', 'text' => 'text-rose-500', 'hover' => 'hover:bg-rose-600 hover:border-rose-600', 'border' => 'border-rose-500', 'light' => 'bg-rose-50 dark:bg-rose-900/20 text-rose-700 dark:text-rose-300 border-rose-300 dark:border-rose-700'],
            self::APROVADA => ['bg' => 'bg-amber-500', 'text' => 'text-amber-500', 'hover' => 'hover:bg-amber-600 hover:border-amber-600', 'border' => 'border-amber-500', 'light' => 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300 border-amber-300 dark:border-amber-700'],
        };
    }
}
