<?php

use App\Models\Mensagem;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $tip_destinatario = '';
    public string $idt_movimento = '';
    public string $idt_paroquia = '';
    
    public bool $readyToLoad = false;

    public function loadData(): void
    {
        $this->readyToLoad = true;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }
    
    public function updatedTipDestinatario(): void
    {
        $this->resetPage();
    }
    
    public function updatedIdtMovimento(): void
    {
        $this->resetPage();
    }
    
    public function updatedIdtParoquia(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        if (!$this->readyToLoad) {
            return [
                'mensagens' => collect(),
                'movimentos' => collect(),
                'paroquias' => collect(),
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
                ->when($this->tip_destinatario, function ($query) {
                    $query->where('tip_destinatario', $this->tip_destinatario);
                })
                ->when($this->idt_movimento, function ($query) {
                    $query->whereHas('evento', function ($q) {
                        $q->where('idt_movimento', $this->idt_movimento);
                    });
                })
                ->when($this->idt_paroquia, function ($query) {
                    $query->whereHas('evento.movimento', function ($q) {
                        $q->where('idt_paroquia', $this->idt_paroquia);
                    });
                })
                ->orderBy('created_at', 'desc')
                ->paginate(10),
            'movimentos' => \App\Models\TipoMovimento::orderBy('nom_movimento')->get(),
            'paroquias' => \App\Models\TipoParoquia::orderBy('nom_paroquia')->get(),
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
    <nav x-data="{ isFiltersOpen: false }" class="bg-white dark:bg-zinc-800 p-5 rounded-xl border border-gray-200 dark:border-zinc-700 shadow-sm mb-8">
        {{-- Versão Desktop --}}
        <div class="hidden md:block">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center">
                <div class="md:col-span-3">
                    <flux:input
                        wire:model.live.debounce.300ms="search"
                        icon="magnifying-glass"
                        placeholder="Buscar por campanha ou evento..."
                    />
                </div>
                
                <div class="md:col-span-3">
                    <flux:select wire:model.live="idt_paroquia" placeholder="Todas Paróquias">
                        <flux:select.option value="">Todas</flux:select.option>
                        @foreach ($paroquias ?? [] as $par)
                            <flux:select.option value="{{ $par->idt_paroquia }}">{{ $par->nom_paroquia }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                
                <div class="md:col-span-3">
                    <flux:select wire:model.live="idt_movimento" placeholder="Todos Movimentos">
                        <flux:select.option value="">Todos</flux:select.option>
                        @foreach ($movimentos ?? [] as $mov)
                            <flux:select.option value="{{ $mov->idt_movimento }}">{{ $mov->des_sigla }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="md:col-span-3">
                    <flux:select wire:model.live="tip_destinatario" placeholder="Todos Destinatários">
                        <flux:select.option value="">Todos</flux:select.option>
                        <flux:select.option value="P">Participantes</flux:select.option>
                        <flux:select.option value="R">Responsáveis</flux:select.option>
                        <flux:select.option value="T">Trabalhadores</flux:select.option>
                    </flux:select>
                </div>
            </div>
        </div>
        
        {{-- Versão Mobile --}}
        <div class="md:hidden">
            <button 
                type="button" 
                @click="isFiltersOpen = !isFiltersOpen" 
                class="w-full flex items-center justify-between text-zinc-700 dark:text-zinc-200 font-semibold text-sm cursor-pointer focus:outline-none"
            >
                <span class="flex items-center gap-2">
                    <flux:icon name="funnel" variant="outline" class="size-4 text-zinc-500" />
                    <span>Filtrar Mensagens</span>
                </span>
                <flux:icon.chevron-down class="size-4 text-zinc-500 transition-transform duration-300" x-bind:class="isFiltersOpen ? 'rotate-180' : ''" />
            </button>

            <div 
                x-show="isFiltersOpen" 
                x-collapse
                class="mt-4 grid grid-cols-1 gap-4"
            >
                <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Buscar por campanha ou evento..." />
                
                <flux:select wire:model.live="idt_paroquia" placeholder="Todas Paróquias">
                    <flux:select.option value="">Todas</flux:select.option>
                    @foreach ($paroquias ?? [] as $par)
                        <flux:select.option value="{{ $par->idt_paroquia }}">{{ $par->nom_paroquia }}</flux:select.option>
                    @endforeach
                </flux:select>
                
                <flux:select wire:model.live="idt_movimento" placeholder="Todos Movimentos">
                    <flux:select.option value="">Todos</flux:select.option>
                    @foreach ($movimentos ?? [] as $mov)
                        <flux:select.option value="{{ $mov->idt_movimento }}">{{ $mov->des_sigla }}</flux:select.option>
                    @endforeach
                </flux:select>
                
                <flux:select wire:model.live="tip_destinatario" placeholder="Todos Destinatários">
                    <flux:select.option value="">Todos</flux:select.option>
                    <flux:select.option value="P">Participantes</flux:select.option>
                    <flux:select.option value="R">Responsáveis</flux:select.option>
                    <flux:select.option value="T">Trabalhadores</flux:select.option>
                </flux:select>
            </div>
        </div>
    </nav>

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
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse ($mensagens as $msg)
            <article class="flex flex-col bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 shadow-sm overflow-hidden hover:shadow-md transition-all duration-300">
                
                <div class="px-5 pt-5 flex justify-between items-start">
                    <div class="flex items-center gap-2">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded bg-zinc-200 dark:bg-zinc-700 text-3xs font-bold text-zinc-800 dark:text-zinc-200" title="{{ $msg->usuario->name }}">
                            {{ $msg->usuario->initials() }}
                        </span>
                        <span class="text-xs text-zinc-500 font-medium">{{ $msg->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <flux:badge :color="$msg->evento->movimento->cor_badge" size="sm" class="uppercase font-bold tracking-wider">
                        {{ $msg->evento->movimento->des_sigla }}
                    </flux:badge>
                </div>

                <div class="p-5 flex-grow">
                    <h2 class="text-lg font-bold text-gray-800 dark:text-white mb-2 line-clamp-2">
                        {{ $msg->nom_campanha }}
                    </h2>
                    
                    <div class="text-sm font-medium text-zinc-800 dark:text-zinc-200 mb-4">
                        {{ $msg->evento->des_evento }}
                    </div>

                    <div class="space-y-4">
                        <div>
                            @if ($msg->tip_destinatario === 'P')
                                <flux:badge color="blue" size="sm" class="font-medium">Participantes</flux:badge>
                            @elseif ($msg->tip_destinatario === 'R')
                                <flux:badge color="purple" size="sm" class="font-medium">Responsáveis</flux:badge>
                            @elseif ($msg->tip_destinatario === 'T')
                                <flux:badge color="orange" size="sm" class="font-medium">Trabalhadores</flux:badge>
                            @endif
                        </div>

                        {{-- Progresso --}}
                        <div class="flex flex-col gap-1 w-full">
                            <div class="flex justify-between text-xs font-semibold text-zinc-600 dark:text-zinc-400">
                                <span>Progresso ({{ $msg->envios_sucesso_count }} / {{ $msg->envios_count }})</span>
                                <span>{{ $msg->envios_count > 0 ? round(($msg->envios_sucesso_count / $msg->envios_count) * 100) : 0 }}%</span>
                            </div>
                            <div class="w-full bg-zinc-200 dark:bg-zinc-700 h-2 rounded-full overflow-hidden">
                                @php
                                    $percent = $msg->envios_count > 0 ? ($msg->envios_sucesso_count / $msg->envios_count) * 100 : 0;
                                    $progressColor = $percent === 100 ? 'bg-green-500' : 'bg-blue-500';
                                @endphp
                                <div class="{{ $progressColor }} h-2 rounded-full transition-all duration-500" style="width: {{ $percent }}%"></div>
                            </div>
                        </div>
                        
                        <div class="text-xs text-zinc-500 truncate mt-2" title="{{ $msg->txt_mensagem }}">
                            {{ Str::limit($msg->txt_mensagem, 80) }}
                        </div>
                    </div>
                </div>
                
                <footer class="p-4 bg-gray-50 dark:bg-zinc-800/50 border-t border-gray-100 dark:border-zinc-700 mt-auto">
                    <flux:button
                        variant="filled"
                        color="blue"
                        class="w-full"
                        icon="eye"
                        :href="route('mensagens.show', ['mensagem' => $msg->idt_mensagem])"
                        wire:navigate
                    >
                        Ver Detalhes / Retomar
                    </flux:button>
                </footer>
            </article>
        @empty
            <div class="col-span-full">
                <x-sem-registro icon="heroicon-o-chat-bubble-bottom-center-text" title="Nenhuma mensagem encontrada" />
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $mensagens instanceof \Illuminate\Pagination\LengthAwarePaginator ? $mensagens->links(data: ['scrollTo' => false]) : '' }}
    </div>
        @endif
    </div>
</div>
