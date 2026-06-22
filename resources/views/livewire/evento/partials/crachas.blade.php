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

<div class="space-y-6">
    {{-- Controles --}}
    <div
        class="flex justify-between items-center print:hidden bg-white p-4 rounded-xl border border-zinc-200 shadow-sm">
        <div>
            <flux:heading size="lg">Impressão de Crachás</flux:heading>
            <flux:subheading>{{ count($this->pessoas) }} registros encontrados</flux:subheading>
        </div>
        <flux:button icon="printer" variant="primary" onclick="window.print()">Imprimir Tudo</flux:button>
    </div>

    {{-- Grid de Crachás --}}
    <div id="crachas-grid" class="flex flex-wrap gap-4 justify-center">
        @forelse ($this->pessoas as $pessoa)
        <div class="cracha-container bg-white border-2 rounded-lg flex overflow-hidden shadow-sm print:shadow-none"
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
