<?php

use App\Models\Evento;
use Livewire\Volt\Component;

new class extends Component {
    public Evento $evento;

    public function mount(Evento $evento): void
    {
        $this->evento = $evento->loadCount([
            'fichas',
            'participantes',
            'trabalhadores',
            'voluntarios as voluntarios_count' => function ($query) {
                $query->whereNull('idt_trabalhador')
                    ->select(\Illuminate\Support\Facades\DB::raw('count(distinct(idt_pessoa))'));
            },
        ]);
    }
}; ?>

<div class="space-y-8">
    <flux:heading size="lg">Resumo</flux:heading>

    {{-- Cards de contagem --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach ([
            ['label' => 'Fichas',        'count' => $evento->fichas_count,        'icon' => 'document-text', 'color' => 'text-zinc-600 dark:text-zinc-300'],
            ['label' => 'Participantes', 'count' => $evento->participantes_count, 'icon' => 'user-group',    'color' => 'text-blue-600 dark:text-blue-400'],
            ['label' => 'Voluntários',   'count' => $evento->voluntarios_count,   'icon' => 'hand-raised',   'color' => 'text-orange-600 dark:text-orange-400'],
            ['label' => 'Trabalhadores', 'count' => $evento->trabalhadores_count, 'icon' => 'briefcase',     'color' => 'text-green-600 dark:text-green-400'],
        ] as $card)
            <flux:card class="flex flex-col items-center justify-center py-6 gap-2">
                <flux:icon :name="$card['icon']" variant="outline" class="size-6 {{ $card['color'] }}" />
                <flux:text class="uppercase text-xs font-bold tracking-wide text-zinc-500">{{ $card['label'] }}</flux:text>
                <flux:heading size="xl" class="{{ $card['color'] }}">{{ $card['count'] }}</flux:heading>
            </flux:card>
        @endforeach
    </div>

    {{-- Prestação de Contas --}}
    @php
        $feita = $evento->txt_relatorio !== null;
    @endphp

    <div class="border border-zinc-200 dark:border-zinc-700 rounded-xl overflow-hidden">
        {{-- Cabeçalho da seção --}}
        <div class="flex items-center justify-between px-5 py-4 bg-zinc-50 dark:bg-zinc-900/40 border-b border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center gap-2">
                <flux:icon name="banknotes" variant="outline" class="size-5 text-zinc-500" />
                <flux:heading size="sm">Prestação de Contas</flux:heading>
            </div>

            @if($feita)
                <flux:badge color="green">Realizada</flux:badge>
            @else
                <flux:badge color="yellow">Pendente</flux:badge>
            @endif
        </div>

        {{-- Corpo --}}
        <div class="p-5">
            @if($feita)
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    {{-- Entradas --}}
                    <div class="flex flex-col gap-1">
                        <flux:text size="sm" class="uppercase text-xs font-bold text-zinc-400 tracking-wide">Total Entradas</flux:text>
                        <flux:heading size="lg" class="text-green-600 dark:text-green-400">
                            R$ {{ number_format($evento->val_receita ?? 0, 2, ',', '.') }}
                        </flux:heading>
                    </div>

                    {{-- Saídas --}}
                    <div class="flex flex-col gap-1">
                        <flux:text size="sm" class="uppercase text-xs font-bold text-zinc-400 tracking-wide">Total Saídas</flux:text>
                        <flux:heading size="lg" class="text-red-600 dark:text-red-400">
                            R$ {{ number_format($evento->val_despesa ?? 0, 2, ',', '.') }}
                        </flux:heading>
                    </div>

                    {{-- Saldo --}}
                    @php
                        $saldo = ($evento->val_receita ?? 0) - ($evento->val_despesa ?? 0);
                    @endphp
                    <div class="flex flex-col gap-1">
                        <flux:text size="sm" class="uppercase text-xs font-bold text-zinc-400 tracking-wide">Saldo</flux:text>
                        <flux:heading size="lg" class="{{ $saldo >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            R$ {{ number_format($saldo, 2, ',', '.') }}
                        </flux:heading>
                    </div>
                </div>

                @if(!empty($evento->txt_relatorio))
                    <div class="mt-4 pt-4 border-t border-zinc-100 dark:border-zinc-700">
                        <flux:text size="sm" class="text-zinc-500 font-bold uppercase text-xs mb-1 tracking-wide">Relatório</flux:text>
                        <flux:text size="sm" class="text-zinc-700 dark:text-zinc-300">{{ $evento->txt_relatorio }}</flux:text>
                    </div>
                @endif
            @else
                <div class="flex flex-col items-center justify-center py-6 text-center gap-2">
                    <flux:icon name="exclamation-circle" variant="outline" class="size-8 text-yellow-400" />
                    <flux:text class="text-zinc-500">Prestação de contas ainda não registrada para este evento.</flux:text>
                    <flux:text size="sm" class="text-zinc-400">Acesse a aba <strong>Prestação de Contas</strong> para preenchê-la.</flux:text>
                </div>
            @endif
        </div>
    </div>

    {{-- Ações do Evento --}}
    <div class="pt-2">
        <flux:heading size="sm" class="mb-4 text-zinc-500 uppercase tracking-wide text-xs">Ações</flux:heading>

        <div class="flex flex-wrap gap-3">
            <flux:button icon="pencil-square" href="{{ route('eventos.edit', $evento) }}" variant="ghost">
                Editar Evento
            </flux:button>

            <form
                method="POST"
                action="{{ route('eventos.destroy', $evento) }}"
                onsubmit="return confirm('Tem certeza que deseja excluir este evento? Esta ação não pode ser desfeita.')"
            >
                @csrf
                @method('DELETE')
                <flux:button type="submit" variant="danger" icon="trash">Excluir Evento</flux:button>
            </form>
        </div>
    </div>
</div>
