<?php

use App\Models\Mensagem;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public bool $readyToLoad = false;

    public function loadData(): void
    {
        $this->readyToLoad = true;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        if (!$this->readyToLoad) {
            return [
                'mensagens' => collect(),
            ];
        }

        return [
            'mensagens' => Mensagem::with(['evento', 'usuario'])
                ->withCount([
                    'envios',
                    'envios as envios_sucesso_count' => fn($q) => $q->where('ind_enviado', true)
                ])
                ->when($this->search, function ($query) {
                    $query->where('nom_campanha', 'like', '%' . $this->search . '%')
                        ->orWhereHas('evento', function ($q) {
                            $q->where('des_evento', 'like', '%' . $this->search . '%');
                        });
                })
                ->orderBy('created_at', 'desc')
                ->paginate(10),
        ];
    }
}; ?>

<div class="space-y-6 w-full max-w-7xl mx-auto p-4 md:p-8">
    {{-- Cabeçalho --}}
    <header class="mb-8 flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" class="text-indigo-900 dark:text-indigo-100 font-bold tracking-tight mb-1" aria-label="Mensagens">
                Mensagens
            </flux:heading>
            <p class="text-indigo-900/70 dark:text-indigo-300/70 mt-1 font-medium">
                Mensagens enviadas, taxas de impacto e histórico de disparos para pessoas dos eventos.
            </p>
        </div>

        <flux:button :href="route('mensagens.create')" icon="plus" variant="primary" wire:navigate class="bg-gradient-to-r from-indigo-500 to-purple-500 hover:from-indigo-600 hover:to-purple-600 border-none shadow-md">
            Nova Mensagem / Campanha
        </flux:button>
    </header>

    {{-- Filtros --}}
    <div class="flex flex-col md:flex-row gap-4 items-center justify-between bg-zinc-50 dark:bg-zinc-900 p-4 rounded-xl border border-zinc-200 dark:border-zinc-700">
        <flux:input
            wire:model.live.debounce.300ms="search"
            icon="magnifying-glass"
            placeholder="Buscar por campanha ou evento..."
            class="w-full md:max-w-md"
        />
        <div class="text-xs text-zinc-500">
            Mostrando resultados paginados
        </div>
    </div>

    {{-- Tabela --}}
    <div wire:init="loadData">
        @if(!$readyToLoad)
            <div class="flex items-center justify-center min-h-[30vh]">
                <div class="animate-pulse flex flex-col items-center">
                    <div class="w-12 h-12 border-4 border-zinc-200 dark:border-zinc-700 border-t-indigo-600 rounded-full animate-spin"></div>
                    <p class="mt-4 text-indigo-600 dark:text-indigo-400 font-medium tracking-tight">Carregando histórico de envios...</p>
                </div>
            </div>
        @else
    <flux:card class="overflow-x-auto p-0 border border-zinc-200 dark:border-zinc-700 shadow-sm rounded-xl">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Campanha</flux:table.column>
                <flux:table.column>Evento</flux:table.column>
                <flux:table.column>Destinatários</flux:table.column>
                <flux:table.column>Progresso</flux:table.column>
                <flux:table.column>Quem Enviou</flux:table.column>
                <flux:table.column>Data de Criação</flux:table.column>
                <flux:table.column align="end">Ações</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($mensagens as $msg)
                    <flux:table.row :key="'msg-'.$msg->idt_mensagem">
                        {{-- Campanha --}}
                        <flux:table.cell>
                            <div class="font-semibold text-zinc-950 dark:text-white">
                                {{ $msg->nom_campanha }}
                            </div>
                            <div class="text-xs text-zinc-500 truncate max-w-xs" title="{{ $msg->txt_mensagem }}">
                                {{ Str::limit($msg->txt_mensagem, 60) }}
                            </div>
                        </flux:table.cell>

                        {{-- Evento --}}
                        <flux:table.cell>
                            <div class="font-medium text-zinc-800 dark:text-zinc-200">
                                {{ $msg->evento->des_evento }}
                            </div>
                            <flux:badge :color="$msg->evento->movimento->cor_badge" size="sm" class="uppercase font-bold tracking-wider mt-1">
                                {{ $msg->evento->movimento->des_sigla }}
                            </flux:badge>
                        </flux:table.cell>

                        {{-- Destinatários --}}
                        <flux:table.cell>
                            @if ($msg->tip_destinatario === 'P')
                                <flux:badge color="blue" size="sm" class="font-medium">Participantes</flux:badge>
                            @elseif ($msg->tip_destinatario === 'R')
                                <flux:badge color="purple" size="sm" class="font-medium">Responsáveis</flux:badge>
                            @elseif ($msg->tip_destinatario === 'T')
                                <flux:badge color="orange" size="sm" class="font-medium">Trabalhadores</flux:badge>
                            @endif
                        </flux:table.cell>

                        {{-- Progresso --}}
                        <flux:table.cell>
                            <div class="flex flex-col gap-1 w-32">
                                <div class="flex justify-between text-2xs font-semibold text-zinc-600 dark:text-zinc-400">
                                    <span>{{ $msg->envios_sucesso_count }} / {{ $msg->envios_count }}</span>
                                    <span>{{ $msg->envios_count > 0 ? round(($msg->envios_sucesso_count / $msg->envios_count) * 100) : 0 }}%</span>
                                </div>
                                <div class="w-full bg-zinc-200 dark:bg-zinc-700 h-1.5 rounded-full overflow-hidden">
                                    @php
                                        $percent = $msg->envios_count > 0 ? ($msg->envios_sucesso_count / $msg->envios_count) * 100 : 0;
                                        $progressColor = $percent === 100 ? 'bg-green-500' : 'bg-blue-500';
                                    @endphp
                                    <div class="{{ $progressColor }} h-1.5 rounded-full" style="width: {{ $percent }}%"></div>
                                </div>
                            </div>
                        </flux:table.cell>

                        {{-- Quem Enviou --}}
                        <flux:table.cell class="text-sm">
                            <div class="flex items-center gap-2">
                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded bg-zinc-200 dark:bg-zinc-700 text-3xs font-bold text-zinc-800 dark:text-zinc-200">
                                    {{ $msg->usuario->initials() }}
                                </span>
                                <span class="text-zinc-800 dark:text-zinc-200">{{ $msg->usuario->name }}</span>
                            </div>
                        </flux:table.cell>

                        {{-- Data --}}
                        <flux:table.cell class="text-sm text-zinc-600 dark:text-zinc-400">
                            {{ $msg->created_at->format('d/m/Y H:i') }}
                        </flux:table.cell>

                        {{-- Ações --}}
                        <flux:table.cell>
                            <div class="flex justify-end gap-2">
                                <flux:button
                                    icon="eye"
                                    size="sm"
                                    variant="ghost"
                                    :href="route('mensagens.show', ['mensagem' => $msg->idt_mensagem])"
                                    wire:navigate
                                    tooltip="Ver Detalhes / Retomar Envios"
                                />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7" class="text-center py-12 text-zinc-500">
                            Nenhum envio registrado no sistema.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    <div class="mt-4">
        {{ $mensagens instanceof \Illuminate\Pagination\LengthAwarePaginator ? $mensagens->links(data: ['scrollTo' => false]) : '' }}
    </div>
        @endif
    </div>
</div>
