<?php

use App\Models\Evento;
use App\Enums\TipoEvento;
use Livewire\Volt\Component;
use Livewire\Attributes\Computed;

new class extends Component {
    public Evento $evento;
    public string $activeTab = 'resumo';

    public function mount(Evento $evento): void
    {
        \Illuminate\Support\Facades\Gate::authorize('acessar-gerenciamento-evento', $evento);

        $this->evento = $evento->load(['movimento'])->loadCount([
            'fichas',
            'participantes',
            'trabalhadores',
            'voluntarios as voluntarios_count' => fn($q) => $q->whereNull('idt_trabalhador')->distinct('idt_pessoa'),
        ]);

        $abas = array_keys($this->tabs());
        if (!in_array($this->activeTab, $abas) && !empty($abas)) {
            $this->activeTab = $abas[0];
        }
    }

    public function setTab(string $tab): void
    {
        if (array_key_exists($tab, $this->tabs)) {
            $this->activeTab = $tab;
        }
    }
    
    #[Computed]
    public function tabs(): array
    {
        $isEncontro = ($this->evento->tip_evento?->value ?? $this->evento->tip_evento) === 'E';
        $evento     = $this->evento;

        $todasAbas = [
            'resumo'       => ['icon' => 'chart-bar',      'label' => 'Resumo'],
            'fichas'       => ['icon' => 'document-text', 'label' => 'Fichas',        'encontro_only' => true],
            'participantes'=> ['icon' => 'user-group',    'label' => 'Participantes'],
            'restricoes'   => ['icon' => 'shield-check',  'label' => 'Restrições'],
            'voluntarios'  => ['icon' => 'hand-raised',   'label' => 'Voluntários',   'encontro_only' => true],
            'trabalhadores'=> ['icon' => 'briefcase',     'label' => 'Trabalhadores', 'encontro_only' => true],
            'crachas'      => ['icon' => 'identification','label' => 'Crachás',        'encontro_only' => true],
            'presenca'     => ['icon' => 'finger-print',  'label' => 'Presença'],
            'quadrante'    => ['icon' => 'table-cells',   'label' => 'Quadrante',     'encontro_only' => true],            
            'contas'       => ['icon' => 'banknotes',     'label' => 'Prestação de Contas'],
        ];

        return array_filter($todasAbas, function ($aba, $tab) use ($isEncontro, $evento) {
            $tipoPermitido = !($aba['encontro_only'] ?? false) || $isEncontro;
            $temPermissao  = \Illuminate\Support\Facades\Gate::allows("evento-tab-{$tab}", $evento);

            return $tipoPermitido && $temPermissao;
        }, ARRAY_FILTER_USE_BOTH);
    }
}; ?>

<section class="w-full">
    <style>
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none;  /* IE and Edge */
            scrollbar-width: none;  /* Firefox */
        }
    </style>

    {{-- Cabeçalho do Evento --}}
    <header class="mb-8 space-y-4">
        <div class="md:hidden">
            <flux:button href="{{ route('eventos.index') }}" icon="arrow-left" variant="ghost" class="text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">
                Voltar para Eventos
            </flux:button>
        </div>

        <div class="flex items-center gap-3 mb-2">
            <flux:heading size="xl" class="text-indigo-900 dark:text-indigo-100 font-bold tracking-tight">
                {{ $evento->des_evento }}
            </flux:heading>
            
            {{-- Badge de Movimento --}}
            <flux:badge :color="$evento->movimento->cor_badge" inset="top bottom" size="sm" class="uppercase font-bold tracking-wider">
                {{ $evento->movimento->des_sigla }}
            </flux:badge>
        </div>

        <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-zinc-500 dark:text-zinc-400">
            {{-- Tipo de Evento via Enum --}}
            <div class="flex items-center gap-3">
                <flux:icon.tag class="size-4" />
                <span>{{ $evento->tip_evento?->label() ?? 'Evento' }}</span>
            </div>

            {{-- Datas do Evento --}}
            <div class="flex items-center gap-3">
                <flux:icon.calendar class="size-4" />
                <span>
                    @if($evento->dat_inicio->format('Y-m-d') === $evento->dat_termino->format('Y-m-d'))
                        {{ $evento->dat_inicio->format('d/m/Y') }}
                    @else
                        {{ $evento->dat_inicio->format('d/m') }} a {{ $evento->dat_termino->format('d/m/Y') }}
                    @endif
                </span>
            </div>
        </div>

        <flux:separator variant="subtle" />

        {{-- Barra de Navegação Horizontal Simplificada com Dropdown (Tab Selector) --}}
        <div class="relative w-full border-b border-zinc-200 dark:border-zinc-700 mt-2 pb-4 flex items-center justify-between">
            @php
                $tabs = $this->tabs;
                $activeTabMeta = $tabs[$activeTab] ?? null;
            @endphp
            @if ($activeTabMeta)
                <div class="flex gap-3 w-full md:w-auto">
                    <flux:dropdown class="w-full md:w-auto">
                        <flux:button 
                            icon="{{ $activeTabMeta['icon'] }}" 
                            icon-trailing="chevron-down" 
                            class="w-full md:min-w-64 justify-between"
                        >
                            {{ $activeTabMeta['label'] }}
                        </flux:button>
                        <flux:menu class="w-64">
                            @foreach ($tabs as $tab => $meta)
                                <flux:menu.item 
                                    wire:click="setTab('{{ $tab }}')"
                                    icon="{{ $meta['icon'] }}"
                                    class="cursor-pointer transition-colors {{ $activeTab === $tab ? 'bg-indigo-50 dark:bg-indigo-900/30 font-semibold text-indigo-600 dark:text-indigo-400' : '' }}"
                                    aria-label="Aba {{ $meta['label'] }}"
                                >
                                    {{ $meta['label'] }}
                                </flux:menu.item>
                            @endforeach
                        </flux:menu>
                    </flux:dropdown>
                </div>
            @endif
        </div>
    </header>

    <main class="w-full bg-white dark:bg-zinc-800 p-6 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm relative">
        {{-- Loading Overlay --}}
        <div wire:loading wire:target="setTab" class="absolute inset-0 z-10 flex items-center justify-center rounded-xl bg-white/80 dark:bg-zinc-800/80 backdrop-blur-sm">
            <div class="flex items-center gap-3 text-zinc-500 dark:text-zinc-400">
                <flux:icon.arrow-path class="h-5 w-5 animate-spin" />
                <span class="text-sm font-medium">Processando...</span>
            </div>
        </div>

        {{-- Renderização Dinâmica Segura --}}
        @if(array_key_exists($activeTab, $this->tabs))
            @switch($activeTab)
                @case('resumo') <livewire:evento.partials.resumo :evento="$evento" lazy /> @break
                @case('fichas') <livewire:evento.partials.fichas :evento="$evento" lazy /> @break
                @case('participantes') <livewire:evento.partials.participantes :evento="$evento" lazy /> @break
                @case('voluntarios') <livewire:evento.partials.voluntarios :evento="$evento" lazy /> @break
                @case('trabalhadores') <livewire:evento.partials.trabalhadores :evento="$evento" lazy /> @break
                @case('presenca') <livewire:evento.partials.presenca :evento="$evento" lazy /> @break
                @case('quadrante') <livewire:evento.partials.quadrante :evento="$evento" lazy /> @break
                @case('crachas') <livewire:evento.partials.crachas :evento="$evento" lazy /> @break
                @case('contas') <livewire:evento.partials.contas :evento="$evento" lazy /> @break
                @case('restricoes') <livewire:evento.partials.restricoes :evento="$evento" lazy /> @break
            @endswitch
        @else
            <div class="p-4 text-zinc-500 italic">
                Esta funcionalidade não está disponível para este tipo de evento.
            </div>
        @endif
    </main>
</section>