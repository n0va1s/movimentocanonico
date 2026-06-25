<?php

use App\Models\Evento;
use App\Models\Voluntario;
use App\Models\TipoEquipe;
use App\Models\Trabalhador;
use App\Models\Pessoa;
use Illuminate\Support\Facades\DB;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public Evento $evento;
    public string $search = '';
    public array $selectedEquipes = [];
    public array $indCoordenador = [];
    public array $indPrimeiraVez = [];

    public function mount(Evento $evento): void
    {
        $this->evento = $evento;
    }

    public function confirmarTrabalhador(int $idtPessoa): void
    {
        $idtEquipe     = $this->selectedEquipes[$idtPessoa] ?? null;
        $coordenador   = (bool) ($this->indCoordenador[$idtPessoa] ?? false);
        $primeiraVez   = (bool) ($this->indPrimeiraVez[$idtPessoa] ?? false);

        if (!$idtEquipe) {
            $this->dispatch('notify', message: 'Selecione uma equipe antes de aprovar.', type: 'info');
            return;
        }

        // Evita duplicidade: verifica se já existe trabalhador para esta pessoa neste evento
        $jaExiste = Trabalhador::where('idt_evento', $this->evento->idt_evento)
            ->where('idt_pessoa', $idtPessoa)
            ->exists();

        if ($jaExiste) {
            $this->dispatch('notify', message: 'Este voluntário já foi confirmado como trabalhador.', type: 'info');
            return;
        }

        DB::transaction(function () use ($idtPessoa, $idtEquipe, $coordenador, $primeiraVez) {
            // Usa firstOrCreate para garantir idempotência mesmo em cliques rápidos
            $trabalhador = Trabalhador::firstOrCreate(
                [
                    'idt_evento' => $this->evento->idt_evento,
                    'idt_pessoa' => $idtPessoa,
                ],
                [
                    'idt_equipe'       => $idtEquipe,
                    'ind_coordenador'  => $coordenador,
                    'ind_primeira_vez' => $primeiraVez,
                ]
            );

            // Vincula todos os voluntários pendentes desta pessoa neste evento ao trabalhador criado
            Voluntario::where('idt_evento', $this->evento->idt_evento)
                ->where('idt_pessoa', $idtPessoa)
                ->whereNull('idt_trabalhador')
                ->update(['idt_trabalhador' => $trabalhador->idt_trabalhador]);
        });

        // Limpa a seleção para este voluntário
        unset($this->selectedEquipes[$idtPessoa]);
        unset($this->indCoordenador[$idtPessoa]);
        unset($this->indPrimeiraVez[$idtPessoa]);

        $this->dispatch('notify', message: 'Voluntário confirmado como trabalhador!', type: 'sucesso');
    }

    public function with(): array
    {
        return [
            'voluntarios' => \App\Models\Pessoa::query()
                ->whereHas('voluntarios', function ($query) {
                    $query->where('idt_evento', $this->evento->idt_evento)
                        ->whereNull('idt_trabalhador');
                })
                ->whereDoesntHave('voluntarios', function ($query) {
                    $query->where('idt_evento', $this->evento->idt_evento)
                        ->whereNotNull('idt_trabalhador');
                })
                ->with(['voluntarios' => function ($query) {
                    $query->where('idt_evento', $this->evento->idt_evento)
                        ->whereNull('idt_trabalhador')
                        ->with('equipe');
                }])
                ->when($this->search, function ($query) {
                    $query->where(function ($q) {
                        $q->where('nom_pessoa', 'like', '%' . $this->search . '%')
                            ->orWhere('nom_apelido', 'like', '%' . $this->search . '%');
                    });
                })
                ->orderBy('nom_pessoa')
                ->paginate(12),
            'equipes' => TipoEquipe::where('idt_movimento', $this->evento->movimento->idt_movimento)->get(),
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <flux:heading size="lg">Triagem de Voluntários</flux:heading>
            <flux:subheading>Analise os candidatos e aloque-os nas equipes correspondentes.</flux:subheading>
        </div>

        <flux:input
            wire:model.live.debounce.300ms="search"
            icon="magnifying-glass"
            placeholder="Buscar por nome ou apelido..."
            class="w-full md:max-w-xs"
        />
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @forelse ($voluntarios as $pessoa)
            <div class="flex flex-col bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">

                <div class="p-5 flex flex-col items-center text-center border-b border-zinc-100 dark:border-zinc-700/50">
                    <flux:avatar
                        src="{{ $pessoa->foto?->url_foto ? asset('storage/'.$pessoa->foto->url_foto) : '' }}"
                        :initials="substr($pessoa->nom_pessoa, 0, 2)"
                        size="sm"
                    />
                    <h3 class="font-bold text-zinc-900 dark:text-white leading-tight mt-2">{{ $pessoa->nom_pessoa }}</h3>
                    @if($pessoa->nom_apelido)
                        <p class="text-sm text-zinc-500 italic">"{{ $pessoa->nom_apelido }}"</p>
                    @endif
                </div>

                <div class="p-4 space-y-3 flex-grow bg-zinc-50/50 dark:bg-zinc-900/20">
                    <div class="flex items-center gap-3 text-sm text-zinc-600 dark:text-zinc-400">
                        <flux:icon.phone variant="outline" class="size-4" />
                        <span>{{ $pessoa->tel_pessoa ?? 'Não informado' }}</span>
                    </div>
                    <div class="flex items-center gap-3 text-sm text-zinc-600 dark:text-zinc-400">
                        <flux:icon.calendar variant="outline" class="size-4" />
                        <span>{{ $pessoa->dat_nascimento ? $pessoa->dat_nascimento->age . ' anos' : 'Idade não informada' }}</span>
                    </div>
                </div>

                <div class="p-4 border-t border-zinc-100 dark:border-zinc-700">
                    <p class="text-[10px] font-bold uppercase text-zinc-400 mb-2 tracking-wider">Equipes que se candidatou:</p>
                    <div class="space-y-3">
                        @forelse ($pessoa->voluntarios as $inscricao)
                            <div class="bg-white dark:bg-zinc-800 p-2 rounded border border-zinc-200 dark:border-zinc-700 shadow-sm">
                                <span class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase block mb-1">
                                    {{ $inscricao->equipe->des_grupo }}
                                </span>
                                @if($inscricao->txt_habilidade)
                                    <p class="text-xs text-zinc-500 leading-relaxed italic border-t border-zinc-100 dark:border-zinc-700 mt-1 pt-1">
                                        "{{ $inscricao->txt_habilidade }}"
                                    </p>
                                @endif
                            </div>
                        @empty
                            <p class="text-xs text-zinc-400">Nenhuma inscrição registrada para este evento.</p>
                        @endforelse
                    </div>
                </div>

                <div class="p-4 border-t border-zinc-100 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900/50 flex flex-col gap-2">
                    <p class="text-[10px] font-bold uppercase text-zinc-400 mb-0.5 tracking-wider">Aprovar candidato:</p>

                    {{-- Coordenador --}}
                    <label class="flex items-center gap-2 text-xs text-zinc-700 dark:text-zinc-300 cursor-pointer select-none">
                        <input type="hidden" wire:model="indCoordenador.{{ $pessoa->idt_pessoa }}" value="0">
                        <input
                            type="checkbox"
                            wire:model="indCoordenador.{{ $pessoa->idt_pessoa }}"
                            value="1"
                            id="ind_coordenador_{{ $pessoa->idt_pessoa }}"
                            class="w-4 h-4 rounded border-zinc-300 dark:border-zinc-600 text-blue-600 focus:ring-blue-500"
                        >
                        Coordenador(a)
                    </label>

                    {{-- Primeira vez --}}
                    <label class="flex items-center gap-2 text-xs text-zinc-700 dark:text-zinc-300 cursor-pointer select-none">
                        <input type="hidden" wire:model="indPrimeiraVez.{{ $pessoa->idt_pessoa }}" value="0">
                        <input
                            type="checkbox"
                            wire:model="indPrimeiraVez.{{ $pessoa->idt_pessoa }}"
                            value="1"
                            id="ind_primeira_vez_{{ $pessoa->idt_pessoa }}"
                            class="w-4 h-4 rounded border-zinc-300 dark:border-zinc-600 text-blue-600 focus:ring-blue-500"
                        >
                        Primeira vez
                    </label>

                    <select wire:model="selectedEquipes.{{ $pessoa->idt_pessoa }}" class="w-full p-2 text-xs border border-zinc-200 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Selecione a equipe...</option>
                        @foreach ($equipes as $equipe)
                            <option value="{{ $equipe->idt_equipe }}">{{ $equipe->des_grupo }}</option>
                        @endforeach
                    </select>
                    
                    <flux:button 
                        size="sm" 
                        variant="primary" 
                        class="w-full text-xs justify-center" 
                        icon="check"
                        wire:click="confirmarTrabalhador({{ $pessoa->idt_pessoa }})"
                    >
                        Aprovar
                    </flux:button>
                </div>

                <div class="p-3 bg-zinc-50 dark:bg-zinc-900/50 border-t border-zinc-100 dark:border-zinc-700 flex justify-between gap-2">
                    <flux:button size="sm" variant="ghost" color="red" class="w-full text-xs" icon="trash">Recusar</flux:button>
                </div>
            </div>
        @empty
            <div class="col-span-full py-12 flex flex-col items-center justify-center border-2 border-dashed border-zinc-200 dark:border-zinc-700 rounded-xl">
                <flux:icon.users class="size-12 text-zinc-300 mb-4" />
                <p class="text-zinc-500">Nenhum voluntário pendente de triagem.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $voluntarios->links(data: ['scrollTo' => false]) }}
    </div>
</div>