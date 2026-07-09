<?php

namespace App\Enums;

enum TipoSituacao: string
{
    case NOVA = 'N'; // criada pelos pais ou pelo candidato
    case RESERVA = 'X'; // ficha em reserva/cadastro reserva
    case SELECIONADA = 'S'; // selecionada por atender aos requisitos
    case CONTATO = 'F'; // Contato feito com os responsaveis
    case AGUARDANDO = 'W'; // Aguardando resposta dos responsáveis
    case VISITADA = 'V'; // Visita concluída pode seguir
    case ENVIADA = 'E'; // enviada por email para confirmacao por um dos pais
    case RECEBIDA = 'R'; // documentacao recebida
    case PAGA = 'P'; // confirmada com pagamento
    case CANCELADA = 'C'; // nao podera participar
    case APROVADA = 'A'; // enviar email com a confirmação do candidato e o resumo de informações do evento

    public function label(): string
    {
        return match ($this) {
            self::NOVA => 'Nova',
            self::RESERVA => 'Reserva',
            self::SELECIONADA => 'Selecionada',
            self::CONTATO => 'Contato',
            self::AGUARDANDO => 'Aguardando',
            self::VISITADA => 'Visitada',
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
            self::NOVA => [
                'bg' => 'bg-slate-100 dark:bg-slate-900/40',
                'text' => 'text-slate-800 dark:text-slate-300',
                'hover' => 'hover:bg-slate-200 hover:border-slate-300',
                'border' => 'border-slate-200',
                'light' => 'bg-slate-100 text-slate-800 border-slate-200 dark:bg-slate-950/40 dark:text-slate-300 dark:border-slate-800',
                'border-l' => 'border-l-slate-300 dark:border-l-slate-600'
            ],
            self::RESERVA => [
                'bg' => 'bg-orange-100 dark:bg-orange-900/40',
                'text' => 'text-orange-800 dark:text-orange-300',
                'hover' => 'hover:bg-orange-200 hover:border-orange-300',
                'border' => 'border-orange-200',
                'light' => 'bg-orange-100 text-orange-800 border-orange-200 dark:bg-orange-950/30 dark:text-orange-300 dark:border-orange-800',
                'border-l' => 'border-l-orange-500 dark:border-l-orange-400'
            ],
            self::SELECIONADA => [
                'bg' => 'bg-lime-100 dark:bg-lime-900/40',
                'text' => 'text-lime-800 dark:text-lime-300',
                'hover' => 'hover:bg-lime-200 hover:border-lime-300',
                'border' => 'border-lime-200',
                'light' => 'bg-lime-100 text-lime-800 border-lime-200 dark:bg-lime-950/40 dark:text-lime-300 dark:border-lime-800',
                'border-l' => 'border-l-lime-500 dark:border-l-lime-400'
            ],
            self::CONTATO => [
                'bg' => 'bg-sky-100 dark:bg-sky-900/40',
                'text' => 'text-sky-800 dark:text-sky-300',
                'hover' => 'hover:bg-sky-200 hover:border-sky-300',
                'border' => 'border-sky-200',
                'light' => 'bg-sky-100 text-sky-800 border-sky-200 dark:bg-sky-950/40 dark:text-sky-300 dark:border-sky-800',
                'border-l' => 'border-l-sky-500 dark:border-l-sky-400'
            ],
            self::AGUARDANDO => [
                'bg' => 'bg-amber-100 dark:bg-amber-900/40',
                'text' => 'text-amber-800 dark:text-amber-300',
                'hover' => 'hover:bg-amber-200 hover:border-amber-300',
                'border' => 'border-amber-200',
                'light' => 'bg-amber-100 text-amber-800 border-amber-200 dark:bg-amber-950/30 dark:text-amber-300 dark:border-amber-800',
                'border-l' => 'border-l-amber-500 dark:border-l-amber-400'
            ],
            self::VISITADA => [
                'bg' => 'bg-purple-100 dark:bg-purple-900/40',
                'text' => 'text-purple-800 dark:text-purple-300',
                'hover' => 'hover:bg-purple-200 hover:border-purple-300',
                'border' => 'border-purple-200',
                'light' => 'bg-purple-100 text-purple-800 border-purple-200 dark:bg-purple-950/40 dark:text-purple-300 dark:border-purple-800',
                'border-l' => 'border-l-purple-600 dark:border-l-purple-400'
            ],
            self::ENVIADA => [
                'bg' => 'bg-cyan-50 dark:bg-cyan-900/30',
                'text' => 'text-cyan-800 dark:text-cyan-300',
                'hover' => 'hover:bg-cyan-100 hover:border-cyan-200',
                'border' => 'border-cyan-200',
                'light' => 'bg-cyan-50 text-cyan-800 border-cyan-200 dark:bg-cyan-950/30 dark:text-cyan-300 dark:border-cyan-800',
                'border-l' => 'border-l-cyan-500 dark:border-l-cyan-400'
            ],
            self::RECEBIDA => [
                'bg' => 'bg-green-100 dark:bg-green-900/40',
                'text' => 'text-green-800 dark:text-green-300',
                'hover' => 'hover:bg-green-200 hover:border-green-300',
                'border' => 'border-green-200',
                'light' => 'bg-green-100 text-green-800 border-green-200 dark:bg-green-950/40 dark:text-green-300 dark:border-green-800',
                'border-l' => 'border-l-green-500 dark:border-l-green-400'
            ],
            self::PAGA => [
                'bg' => 'bg-emerald-100 dark:bg-emerald-900/40',
                'text' => 'text-emerald-800 dark:text-emerald-300',
                'hover' => 'hover:bg-emerald-200 hover:border-emerald-300',
                'border' => 'border-emerald-200',
                'light' => 'bg-emerald-100 text-emerald-800 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800',
                'border-l' => 'border-l-emerald-600 dark:border-l-emerald-400'
            ],
            self::CANCELADA => [
                'bg' => 'bg-rose-100 dark:bg-rose-900/40',
                'text' => 'text-rose-800 dark:text-rose-300',
                'hover' => 'hover:bg-rose-200 hover:border-rose-300',
                'border' => 'border-rose-200',
                'light' => 'bg-rose-100 text-rose-800 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800',
                'border-l' => 'border-l-rose-500 dark:border-l-rose-400'
            ],
            self::APROVADA => [
                'bg' => 'bg-teal-100 dark:bg-teal-900/40',
                'text' => 'text-teal-800 dark:text-teal-300',
                'hover' => 'hover:bg-teal-200 hover:border-teal-300',
                'border' => 'border-teal-200',
                'light' => 'bg-teal-100 text-teal-800 border-teal-200 dark:bg-teal-950/40 dark:text-teal-300 dark:border-teal-800',
                'border-l' => 'border-l-teal-500 dark:border-l-teal-400'
            ],
        };
    }

    public function mail(): array
    {
        return match ($this) {
            self::NOVA => ['Sim'],
            self::ENVIADA => ['Sim'], // ENVIADA actually sends email in our new flow (FichaEnviadaEvent)
            self::APROVADA => ['Sim'],
            default => ['Não'],
        };
    }

    public function cardConfig(): array
    {
        return match ($this) {
            self::AGUARDANDO => [
                'bg' => 'bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-900 text-amber-700 dark:text-amber-400',
                'icon' => 'clock',
                'label' => 'Aguardando'
            ],
            self::SELECIONADA => [
                'bg' => 'bg-blue-50 dark:bg-blue-950/20 border border-blue-200 dark:border-blue-900 text-blue-700 dark:text-blue-400',
                'icon' => 'check-circle',
                'label' => 'Selecionada'
            ],
            self::VISITADA => [
                'bg' => 'bg-green-50 dark:bg-green-950/20 border border-green-200 dark:border-green-900 text-green-700 dark:text-green-400',
                'icon' => 'book-open',
                'label' => 'Visitado'
            ],
            self::CONTATO => [
                'bg' => 'bg-cyan-50 dark:bg-cyan-950/20 border border-cyan-200 dark:border-cyan-900 text-cyan-700 dark:text-cyan-400',
                'icon' => 'phone',
                'label' => 'Contato Feito'
            ],
            self::CANCELADA => [
                'bg' => 'bg-rose-50 dark:bg-rose-950/20 border border-rose-200 dark:border-rose-900 text-rose-700 dark:text-rose-400',
                'icon' => 'x-circle',
                'label' => 'Cancelada'
            ],
            default => [
                'bg' => 'bg-zinc-50 dark:bg-zinc-950/20 border border-zinc-200 dark:border-zinc-900 text-zinc-700 dark:text-zinc-400',
                'icon' => 'document-text',
                'label' => $this->label()
            ],
        };
    }
}
