<?php

use App\Models\Evento;
use App\Models\Participante;
use App\Models\Trabalhador;
use Livewire\Volt\Component;
use Livewire\Attributes\Computed;

new class extends Component {
    public Evento $evento;

    public function mount(Evento $evento): void
    {
        $this->evento = $evento->load(['movimento', 'foto']); // ajuste 'foto' se o rel tiver outro nome
    }

    #[Computed]
    public function participantes()
    {
        return Participante::where('idt_evento', $this->evento->idt_evento)
            ->where('ind_presente', true)
            ->with('pessoa')
            ->orderBy('tip_cor_troca')
            ->get()
            ->groupBy('tip_cor_troca');
    }

    #[Computed]
    public function trabalhadores()
    {
        return Trabalhador::where('idt_evento', $this->evento->idt_evento)
            ->whereHas('pessoa')
            ->where('ind_presente', true)
            ->with(['pessoa', 'equipe'])
            ->orderBy('idt_equipe')
            ->get()
            ->groupBy(fn($t) => $t->equipe?->des_grupo ?? 'Sem Equipe');
    }

    #[Computed]
    public function totalParticipantes(): int
    {
        return Participante::where('idt_evento', $this->evento->idt_evento)
            ->where('ind_presente', true)
            ->count();
    }

    #[Computed]
    public function totalTrabalhadores(): int
    {
        return Trabalhador::where('idt_evento', $this->evento->idt_evento)
            ->whereHas('pessoa')
            ->where('ind_presente', true)
            ->count();
    }
}; ?>

{{-- Wrapper com id para impressão --}}
<div id="quadrante-print" class="space-y-8">

    {{-- Botão imprimir (oculto na impressão) --}}
    <div class="flex justify-end print:hidden">
        <flux:button icon="printer" variant="ghost" onclick="window.print()">
            Imprimir Quadrante
        </flux:button>
    </div>

    {{-- Aviso quando nenhuma presença foi marcada --}}
    @if($this->totalParticipantes === 0 && $this->totalTrabalhadores === 0)
        <div class="flex items-start gap-3 px-4 py-3 rounded-lg border border-amber-200 bg-amber-50 dark:bg-amber-900/20 dark:border-amber-800 print:hidden">
            <flux:icon name="exclamation-triangle" variant="outline" class="size-5 text-amber-500 shrink-0 mt-0.5" />
            <div>
                <flux:text class="font-semibold text-amber-800 dark:text-amber-300">Nenhuma presença confirmada</flux:text>
                <flux:text size="sm" class="text-amber-700 dark:text-amber-400">
                    O quadrante exibe apenas pessoas marcadas como presentes. Acesse a aba <strong>Presença</strong> para registrar as presenças antes de imprimir.
                </flux:text>
            </div>
        </div>
    @endif

    {{-- ============================================================
         CABEÇALHO DO QUADRANTE
    ============================================================ --}}
    <div class="flex flex-col md:flex-row gap-6 items-start border border-zinc-200 dark:border-zinc-700 rounded-xl p-6 bg-zinc-50 dark:bg-zinc-900/40 print:border-black print:bg-white">

        {{-- Foto oficial do evento --}}
        <div class="shrink-0">
            @if($evento->foto?->med_foto)
                <img
                    src="{{ asset('storage/' . $evento->foto->med_foto) }}"
                    alt="Foto oficial do evento"
                    class="w-32 h-32 object-cover rounded-xl border border-zinc-300 dark:border-zinc-600 shadow-sm print:w-24 print:h-24"
                />
            @else
                <div class="w-32 h-32 flex items-center justify-center rounded-xl border-2 border-dashed border-zinc-300 dark:border-zinc-600 text-zinc-400">
                    <x-heroicon-o-photo class="w-10 h-10" />
                </div>
            @endif
        </div>

        {{-- Dados do evento --}}
        <div class="flex-1 space-y-1">
            <div class="flex items-center gap-3 flex-wrap">
                <flux:heading size="xl">
                    {{ $evento->num_evento }} — {{ $evento->des_evento }}
                </flux:heading>
                @php
                    $tipoLabel = match($evento->tip_evento ?? '') {
                        'E' => 'Encontro',
                        'R' => 'Retiro',
                        'C' => 'Convivência',
                        default => $evento->tip_evento ?? '—',
                    };
                @endphp
                <flux:badge color="blue">{{ $tipoLabel }}</flux:badge>
            </div>

            <flux:subheading>{{ $evento->movimento->des_sigla ?? '' }}</flux:subheading>

            <div class="flex flex-wrap gap-4 mt-3 text-sm text-zinc-600 dark:text-zinc-400">
                <span class="flex items-center gap-1">
                    <x-heroicon-o-calendar class="w-4 h-4" />
                    Início: <strong>{{ \Carbon\Carbon::parse($evento->dat_inicio)->format('d/m/Y') }}</strong>
                </span>
                <span class="flex items-center gap-1">
                    <x-heroicon-o-calendar class="w-4 h-4" />
                    Término: <strong>{{ \Carbon\Carbon::parse($evento->dat_termino)->format('d/m/Y') }}</strong>
                </span>
            </div>

            {{-- Totalizadores --}}
            <div class="flex gap-6 mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-700 print:border-zinc-300">
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $this->totalParticipantes }}</div>
                    <div class="text-xs uppercase tracking-wide text-zinc-500">Participantes</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $this->totalTrabalhadores }}</div>
                    <div class="text-xs uppercase tracking-wide text-zinc-500">Trabalhadores</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-zinc-700 dark:text-zinc-300">{{ $this->totalParticipantes + $this->totalTrabalhadores }}</div>
                    <div class="text-xs uppercase tracking-wide text-zinc-500">Total</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         PARTICIPANTES — agrupados por cor de troca
    ============================================================ --}}
    <div class="space-y-4 print:break-before-page">
        <div class="flex items-center gap-3">
            <flux:heading size="lg">Participantes</flux:heading>
            <div class="flex-1 h-px bg-zinc-200 dark:bg-zinc-700"></div>
            <flux:badge color="blue">{{ $this->totalParticipantes }}</flux:badge>
        </div>

        @forelse ($this->participantes as $cor => $grupo)
            @php
                $corBadge = match(strtolower($cor ?? '')) {
                    'azul'     => 'blue',
                    'verde'    => 'green',
                    'vermelha' => 'red',
                    'amarela'  => 'yellow',
                    'laranja'  => 'orange',
                    default    => 'zinc',
                };
            @endphp

            <div class="border border-zinc-200 dark:border-zinc-700 rounded-xl overflow-hidden print:border-zinc-300">
                {{-- Título da cor --}}
                <div class="flex items-center gap-2 px-4 py-2 bg-zinc-50 dark:bg-zinc-900/30 border-b border-zinc-200 dark:border-zinc-700">
                    <flux:badge color="{{ $corBadge }}">{{ ucfirst($cor ?: 'Sem cor') }}</flux:badge>
                    <flux:text size="sm" class="text-zinc-500">{{ $grupo->count() }} pessoa(s)</flux:text>
                </div>

                {{-- Grid de nomes --}}
                <div class="p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                    @foreach ($grupo->sortBy('pessoa.nom_pessoa') as $p)
                        <div class="flex items-baseline gap-2 text-sm">
                            {{-- Destaque para coordenador (se participante tiver esse campo) --}}
                            @if(!empty($p->ind_coordenador))
                                <x-heroicon-o-star class="w-3.5 h-3.5 text-yellow-500 shrink-0" />
                            @else
                                <span class="w-3.5 shrink-0"></span>
                            @endif
                            <div>
                                <span class="font-medium text-zinc-900 dark:text-white">
                                    {{ $p->pessoa->nom_pessoa }}
                                </span>
                                @if($p->pessoa->dat_nascimento)
                                    <span class="text-xs text-zinc-400 ml-1">
                                        ({{ \Carbon\Carbon::parse($p->pessoa->dat_nascimento)->format('d/m/Y') }})
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="text-center py-10 text-zinc-400">Nenhum participante cadastrado.</div>
        @endforelse
    </div>

    {{-- ============================================================
         TRABALHADORES — agrupados por equipe
    ============================================================ --}}
    <div class="space-y-4 print:break-before-page">
        <div class="flex items-center gap-3">
            <flux:heading size="lg">Trabalhadores</flux:heading>
            <div class="flex-1 h-px bg-zinc-200 dark:bg-zinc-700"></div>
            <flux:badge color="green">{{ $this->totalTrabalhadores }}</flux:badge>
        </div>

        @forelse ($this->trabalhadores as $equipe => $grupo)
            <div class="border border-zinc-200 dark:border-zinc-700 rounded-xl overflow-hidden print:border-zinc-300">
                {{-- Título da equipe --}}
                <div class="flex items-center gap-2 px-4 py-2 bg-zinc-50 dark:bg-zinc-900/30 border-b border-zinc-200 dark:border-zinc-700">
                    <flux:badge color="green">{{ $equipe }}</flux:badge>
                    <flux:text size="sm" class="text-zinc-500">{{ $grupo->count() }} pessoa(s)</flux:text>
                </div>

                {{-- Lista: coordenadores primeiro, depois demais --}}
                <div class="p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                    @foreach ($grupo->sortByDesc('ind_coordenador')->sortBy('pessoa.nom_pessoa') as $t)
                        <div class="flex items-baseline gap-2 text-sm">
                            @if($t->ind_coordenador)
                                <x-heroicon-o-star class="w-3.5 h-3.5 text-yellow-500 shrink-0" title="Coordenador" />
                            @else
                                <span class="w-3.5 shrink-0"></span>
                            @endif
                            <div>
                                <span class="{{ $t->ind_coordenador ? 'font-bold' : 'font-medium' }} text-zinc-900 dark:text-white">
                                    {{ $t->pessoa->nom_nome ?? $t->pessoa->nom_pessoa }}
                                </span>
                                @if($t->pessoa->dat_nascimento)
                                    <span class="text-xs text-zinc-400 ml-1">
                                        ({{ \Carbon\Carbon::parse($t->pessoa->dat_nascimento)->format('d/m/Y') }})
                                    </span>
                                @endif
                                @if($t->ind_coordenador)
                                    <span class="text-xs text-purple-500 ml-1">Coord.</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="text-center py-10 text-zinc-400">Nenhum trabalhador cadastrado.</div>
        @endforelse
    </div>

</div>

{{-- Estilos de impressão --}}
<style>
    @media print {
        /* Oculta toda a UI do sistema, mostra só o quadrante */
        body > *:not([data-livewire]) { display: none !important; }
        nav, aside, header, footer, .print\:hidden { display: none !important; }
        #quadrante-print { display: block !important; font-size: 11pt; }
        .print\:break-before-page { break-before: page; }
        * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    }
</style>
