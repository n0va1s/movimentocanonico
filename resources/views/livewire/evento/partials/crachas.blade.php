<?php
use App\Models\Evento;
use App\Models\Participante;
use App\Models\Trabalhador;
use Livewire\Volt\Component;
use Livewire\Attributes\Computed;

new class extends Component {
    public Evento $evento;

    public function mount(Evento $evento): void {
        $this->evento = $evento;
    }

    #[Computed]
    public function logoUrl(): ?string {
        $this->evento->loadMissing('logo');
        return $this->evento->logo?->med_logo ? asset('storage/' . $this->evento->logo->med_logo) : null;
    }


    #[Computed]
    public function pessoas(): array {
        $lista = [];

        // Participantes
        $participantes = Participante::where('idt_evento', $this->evento->idt_evento)
            ->with(['pessoa.restricoes', 'pessoa.foto'])
            ->get();

        foreach ($participantes as $p) {
            $lista[] = [
                'nome'       => $p->pessoa->nom_apelido ?: $p->pessoa->nom_pessoa,
                'nome_full'  => $p->pessoa->nom_pessoa,
                'tipo'       => 'participante',
                'grupo'      => $p->tip_cor_troca ? ucfirst($p->tip_cor_troca) : 'Geral',
                'grupo_cor'  => $this->corDaFaixa($p->tip_cor_troca),
                'restricoes' => $p->pessoa->restricoes ?? collect(),
            ];
        }

        // Trabalhadores
        $trabalhadores = Trabalhador::where('idt_evento', $this->evento->idt_evento)
            ->whereHas('pessoa')
            ->with(['pessoa.restricoes', 'pessoa.foto', 'equipe'])
            ->get();

        foreach ($trabalhadores as $t) {
            $lista[] = [
                'nome'       => $t->pessoa->nom_apelido ?: $t->pessoa->nom_pessoa,
                'nome_full'  => $t->pessoa->nom_pessoa,
                'tipo'       => 'trabalhador',
                'grupo'      => $t->equipe?->des_grupo ?? 'Equipe',
                'grupo_cor'  => '#6366f1', // Cor padrão para equipe (Indigo)
                'restricoes' => $t->pessoa->restricoes ?? collect(),
            ];
        }

        return $lista;
    }

    private function corDaFaixa(?string $cor): string {
        return match(strtolower($cor ?? '')) {
            'azul'     => '#3b82f6',
            'verde'    => '#22c55e',
            'vermelha' => '#ef4444',
            'amarela'  => '#eab308',
            'laranja'  => '#f97316',
            default    => '#a1a1aa',
        };
    }
}; ?>

<div class="space-y-6"
    x-data="{ 
        selecionados: [], 
        todos: true,
        pessoasIds: [
            @foreach($this->pessoas as $idx => $pessoa)
                '{{ $idx }}',
            @endforeach
        ],
        toggleTodos() {
            this.selecionados = this.todos ? [...this.pessoasIds] : [];
        },
        toggle(idx) {
            if (this.selecionados.includes(idx)) {
                this.selecionados = this.selecionados.filter(i => i !== idx);
            } else {
                this.selecionados.push(idx);
            }
        },
        init() {
            this.selecionados = [...this.pessoasIds];
            this.$watch('selecionados', val => {
                this.todos = val.length === this.pessoasIds.length && this.pessoasIds.length > 0;
            });
        }
    }">

    {{-- Controles --}}
    <div
        class="flex flex-col sm:flex-row justify-between items-start sm:items-center print:hidden bg-white p-4 rounded-xl border border-zinc-200 shadow-sm gap-4">
        <div>
            <flux:heading size="lg">Impressão de Crachás</flux:heading>
            <flux:subheading>{{ count($this->pessoas) }} registros encontrados</flux:subheading>
        </div>
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 sm:gap-6 w-full sm:w-auto">
            <label class="flex items-center gap-2 cursor-pointer text-sm text-zinc-700 font-medium hover:text-zinc-900 transition-colors">
                <input type="checkbox" x-model="todos" @change="toggleTodos" class="rounded border-zinc-300 text-indigo-600 shadow-sm focus:ring-indigo-500 w-4 h-4">
                Selecionar Todos
            </label>
            <div class="text-sm text-zinc-500 font-medium">
                <span x-text="selecionados.length"></span> selecionado(s)
            </div>
            <flux:button icon="printer" variant="primary" onclick="window.print()" x-bind:disabled="selecionados.length === 0" class="w-full sm:w-auto">
                <span x-text="selecionados.length === pessoasIds.length && pessoasIds.length > 0 ? 'Imprimir Tudo' : 'Imprimir Selecionados'"></span>
            </flux:button>
        </div>
    </div>

    {{-- Grid de Crachás --}}
    <div id="crachas-grid" class="flex flex-wrap gap-4 justify-center">
        @forelse ($this->pessoas as $idx => $pessoa)
        <div class="relative group" 
             x-bind:class="selecionados.includes('{{ $idx }}') ? '' : 'print:hidden'">
             
            {{-- Indicador de seleção --}}
            <div class="absolute top-2 left-2 z-10 print:hidden bg-white p-1 rounded-md shadow-sm border transition-colors cursor-pointer"
                 x-bind:class="selecionados.includes('{{ $idx }}') ? 'border-indigo-500 bg-indigo-50' : 'border-zinc-200 opacity-60 group-hover:opacity-100'"
                 @click="toggle('{{ $idx }}')">
                <input type="checkbox" :checked="selecionados.includes('{{ $idx }}')" 
                       class="w-5 h-5 rounded border-zinc-300 text-indigo-600 shadow-sm focus:ring-indigo-500 pointer-events-none">
            </div>

            <div class="cracha-container bg-white border-2 rounded-lg flex overflow-hidden shadow-sm print:shadow-none transition-all cursor-pointer hover:shadow-md"
                @click="toggle('{{ $idx }}')"
                x-bind:class="selecionados.includes('{{ $idx }}') ? '' : 'opacity-40 grayscale-[50%] scale-[0.98] hover:opacity-70'"
                style="border-color: {{ $pessoa['grupo_cor'] }}; width: 8.6cm; height: 5.4cm; page-break-inside: avoid;">

            {{-- Lateral: Imagem --}}
           <div class="shrink-0 bg-zinc-50 border-r" style="width: 2.2cm; border-color: {{ $pessoa['grupo_cor'] }}44;">
                @if($this->logoUrl)
                    <img src="{{ $this->logoUrl }}"
                        class="w-full h-full object-cover object-top grayscale opacity-80"
                        alt="{{ $evento->des_evento }}" />
                @else
                    <div class="w-full h-full flex items-center justify-center bg-zinc-100">
                        <span class="text-[8px] text-zinc-400 text-center px-1 leading-tight">{{ $evento->des_evento }}</span>
                    </div>
                @endif
            </div>

            {{-- Conteúdo Direita --}}
            <div class="flex-1 flex flex-col p-3 overflow-hidden">
                {{-- Informações do Evento --}}
                <div class="mb-1 text-center">
                    <p class="text-[12px] font-black text-zinc-800 leading-tight uppercase truncate">
                        {{ $evento->num_evento }} - {{ $evento->des_evento }}
                    </p>
                    <p class="text-[8px] text-zinc-500 font-medium leading-none mt-0.5">
                        {{ $evento->dat_inicio?->format('d/m/Y') }} a {{ $evento->dat_termino?->format('d/m/Y') }}
                    </p>
                </div>

                {{-- Badge do Grupo --}}
                <div class="mb-1">
                    <span class="text-[9px] font-black uppercase px-2 py-0.5 rounded text-white"
                        style="background-color: {{ $pessoa['grupo_cor'] }};">
                        {{ $pessoa['grupo'] }}
                    </span>
                </div>

                {{-- Nomes --}}
                <div class="flex-1 flex flex-col justify-center">
                    <h2 class="text-xl font-black text-zinc-900 leading-tight uppercase truncate">
                        {{ $pessoa['nome'] }}
                    </h2>
                    @if($pessoa['nome'] != $pessoa['nome_full'])
                    <p class="text-[9px] text-zinc-500 truncate font-medium uppercase tracking-tighter">
                        {{ $pessoa['nome_full'] }}
                    </p>
                    @endif
                </div>

                {{-- Rodapé: Restrições --}}
                <div class="flex flex-wrap gap-1 mt-auto pt-2 border-t border-zinc-100">
                @foreach($pessoa['restricoes'] as $r)
                    @php
                        $tipoEnum = $r->tip_restricao instanceof \App\Enums\TipoRestricao
                            ? $r->tip_restricao
                            : \App\Enums\TipoRestricao::tryFrom($r->tip_restricao);
                        $tipoLabel = $tipoEnum ? $tipoEnum->label() : $r->tip_restricao;
                        $corClass = $r->getCor();
                    @endphp

                    <span class="{{ $corClass }} text-xs px-1.5 py-0.5 rounded flex items-center justify-center font-bold" title="{{ $tipoLabel }}: {{ $r->des_restricao }}">
                        @if($tipoEnum)
                            {{ $tipoEnum->icon() }}
                        @else
                            ⚠️
                        @endif
                    </span>
                @endforeach
                </div>
            </div>
        </div>
        </div>
        @empty
        <div class="w-full text-center py-20 text-zinc-400">Nenhum crachá para gerar.</div>
        @endforelse
    </div>
</div>

<style>
    @media print {
        body {
            background: white !important;
            padding: 0 !important;
        }

        nav,
        header,
        button,
        .print\:hidden {
            display: none !important;
        }

        @page {
            size: A4;
            margin: 1cm;
        }

        #crachas-grid {
            display: grid !important;
            grid-template-columns: 8.6cm 8.6cm;
            gap: 0.5cm;
            justify-content: center;
        }

        .cracha-container {
            border-width: 1px !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
    }
</style>
