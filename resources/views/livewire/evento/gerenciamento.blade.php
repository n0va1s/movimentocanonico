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
        $isEncontro = $this->evento->tip_evento === TipoEvento::ENCONTRO;
        $evento     = $this->evento;

        $todasAbas = [
            'resumo'       => ['icon' => 'chart-bar',      'label' => 'Resumo'],
            'fichas'       => ['icon' => 'document-text', 'label' => 'Fichas',        'encontro_only' => true],
            'participantes'=> ['icon' => 'user-group',    'label' => 'Participantes'],
            'voluntarios'  => ['icon' => 'hand-raised',   'label' => 'Voluntários',   'encontro_only' => true],
            'trabalhadores'=> ['icon' => 'briefcase',     'label' => 'Trabalhadores', 'encontro_only' => true],
            'crachas'      => ['icon' => 'identification','label' => 'Crachás'],
            'presenca'     => ['icon' => 'finger-print',  'label' => 'Presença'],
            'quadrante'    => ['icon' => 'table-cells',   'label' => 'Quadrante',     'encontro_only' => true],            
            'contas'       => ['icon' => 'banknotes',     'label' => 'Prestação de Contas'],
            'mercadinho'   => ['icon' => 'shopping-cart', 'label' => 'Mercadinho'],
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

        <div class="flex items-center gap-3">
            <flux:heading size="xl">{{ $evento->des_evento }}</flux:heading>
            
            {{-- Badge de Movimento --}}
            @php
                $color = match(strtoupper($evento->movimento->des_sigla)) {
                    'VEM'      => 'blue',
                    'ECC'      => 'green',
                    'SEGUE-ME' => 'orange',
                    default    => 'zinc',
                };
            @endphp
            <flux:badge :color="$color" inset="top bottom" size="sm" class="uppercase font-bold">
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

        {{-- Barra de Navegação Horizontal (Navbar/Tabs) - Desktop Only --}}
        <div class="hidden md:block pt-2">
            <nav class="flex flex-row items-center gap-1 overflow-x-auto whitespace-nowrap pb-1 no-scrollbar border-b border-zinc-200 dark:border-zinc-700">
                @foreach ($this->tabs as $tab => $meta)
                    <button 
                        type="button"
                        wire:click="setTab('{{ $tab }}')"
                        wire:loading.attr="disabled"
                        class="flex items-center gap-2 px-4 py-3 text-sm font-medium border-b-2 -mb-[1px] transition-colors cursor-pointer focus:outline-none {{ $activeTab === $tab ? 'border-blue-600 text-blue-600 dark:text-blue-400 font-semibold' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}"
                    >
                        <flux:icon :name="$meta['icon']" class="size-4" />
                        <span>{{ $meta['label'] }}</span>
                    </button>
                @endforeach
            </nav>
        </div>
        {{-- Menu de Navegação Local (Mobile - Expansível) --}}
        <div x-data="{ isOpen: false }" class="w-full md:hidden space-y-2 pt-2">
            {{-- Botão de Toggle Mobile --}}
            @php
                $tabMeta = $this->tabs[$activeTab] ?? (array_values($this->tabs)[0] ?? ['icon' => 'chart-bar', 'label' => 'Painel']);
            @endphp
            <button 
                type="button"
                x-on:click="isOpen = !isOpen"
                class="w-full flex items-center justify-between px-4 py-2.5 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-sm font-medium text-zinc-700 dark:text-zinc-200 shadow-sm cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors"
            >
                <span class="flex items-center gap-2">
                    <flux:icon :name="$tabMeta['icon']" class="size-4 text-zinc-500" />
                    <span>{{ $tabMeta['label'] }}</span>
                </span>
                <flux:icon.chevron-down class="size-4 text-zinc-500 transition-transform duration-200" x-bind:class="isOpen ? 'rotate-180' : ''" />
            </button>

            <nav 
                x-show="isOpen"
                x-collapse
                class="flex flex-col gap-1"
                style="display: none;"
            >
                <flux:navlist>
                    @foreach ($this->tabs as $tab => $meta)
                        <flux:navlist.item
                            wire:click="setTab('{{ $tab }}')"
                            wire:loading.attr="disabled"
                            :variant="$activeTab === '{{ $tab }}' ? 'bullet' : 'ghost'"
                            icon="{{ $meta['icon'] }}"
                            class="cursor-pointer"
                            x-on:click="isOpen = false"
                        >
                            {{ $meta['label'] }}
                        </flux:navlist.item>
                    @endforeach
                </flux:navlist>
            </nav>
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
                @case('resumo') <livewire:evento.partials.resumo :evento="$evento" /> @break
                @case('fichas') <livewire:evento.partials.fichas :evento="$evento" /> @break
                @case('participantes') <livewire:evento.partials.participantes :evento="$evento" /> @break
                @case('voluntarios') <livewire:evento.partials.voluntarios :evento="$evento" /> @break
                @case('trabalhadores') <livewire:evento.partials.trabalhadores :evento="$evento" /> @break
                @case('presenca') <livewire:evento.partials.presenca :evento="$evento" /> @break
                @case('quadrante') <livewire:evento.partials.quadrante :evento="$evento" /> @break
                @case('crachas') <livewire:evento.partials.crachas :evento="$evento" /> @break
                @case('contas') <livewire:evento.partials.contas :evento="$evento" /> @break
                @case('mercadinho') <livewire:vendas.index :evento="$evento" /> @break
            @endswitch
        @else
            <div class="p-4 text-zinc-500 italic">
                Esta funcionalidade não está disponível para este tipo de evento.
            </div>
        @endif
    </main>
</section>