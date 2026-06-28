<?php

use App\Models\Ficha;
use App\Models\Pessoa;
use App\Models\User;
use App\Models\TipoMovimento;
use App\Models\Evento;
use App\Enums\TipoSituacao;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;

new class extends Component {
    use WithPagination;

    public ?Evento $evento = null;
    public ?int $eventoId = null;
    public string $situacao = '';
    public string $search = '';
    public string $visitadorSearch = '';
    public array $selectedFichas = [];
    public ?int $pessoaVisitacaoId = null;

    public function mount(?Evento $evento = null): void
    {
        if (!auth()->check() || !auth()->user()->hasRole('admin', 'visit')) {
            abort(403, 'Acesso não autorizado.');
        }

        if ($evento && $evento->exists) {
            $this->evento = $evento;
            $this->eventoId = $evento->idt_evento;
        } else {
            $this->evento = null;
            $this->eventoId = null;
        }
    }

    public function selectEvento(int $eventoId): void
    {
        $evento = Evento::find($eventoId);
        if ($evento) {
            $this->evento = $evento;
            $this->eventoId = $evento->idt_evento;
            $this->resetPage();
            $this->selectedFichas = [];
        }
    }

    public function alterarEvento(): void
    {
        $this->evento = null;
        $this->eventoId = null;
    }

    public function updatedSituacao(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedVisitadorSearch(): void
    {
        $this->resetPage();
    }

    public function alterarSituacao(int $fichaId, string $novaSituacao): void
    {
        try {
            $situacaoEnum = TipoSituacao::from($novaSituacao);
            \App\Services\FichaService::atualizarSituacaoFicha($fichaId, $situacaoEnum);
            \Flux::toast(__('messages.alerts.success.ficha_updated'), variant: 'success');
        } catch (\Exception $e) {
            \Flux::toast(__('messages.alerts.error.ficha_update_error', ['error' => $e->getMessage()]), variant: 'danger');
        }
    }

    private function getVisitadores()
    {
        if (!$this->eventoId) {
            return collect();
        }

        $visitadoresRaw = \App\Models\Pessoa::where(function ($query) {
            $query->whereHas('trabalhadores', function ($q) {
                $q->where('idt_evento', $this->eventoId)
                  ->whereHas('equipe', function ($qe) {
                      $qe->where('des_grupo', 'like', '%Visitação%');
                  });
            })
            ->orWhereHas('parceiro.trabalhadores', function ($q) {
                $q->where('idt_evento', $this->eventoId)
                  ->whereHas('equipe', function ($qe) {
                      $qe->where('des_grupo', 'like', '%Visitação%');
                  });
            });
        })->with('parceiro')->orderBy('nom_pessoa', 'asc')->get();

        $processed = [];
        return $visitadoresRaw->reject(function ($v) use (&$processed) {
            if (in_array($v->idt_pessoa, $processed)) {
                return true;
            }

            // Ocultar casal/pessoa caso já tenha 3 ou mais fichas atribuídas no evento selecionado
            $fichaCount = \App\Models\Ficha::where('idt_evento', $this->eventoId)
                ->where(function ($q) use ($v) {
                    $q->where('idt_pessoa_visitacao', $v->idt_pessoa)
                      ->when($v->idt_parceiro, function ($q2) use ($v) {
                          $q2->orWhere('idt_pessoa_visitacao', $v->idt_parceiro);
                      });
                })
                ->count();

            if ($fichaCount >= 3) {
                return true;
            }

            if ($v->idt_parceiro) {
                $processed[] = $v->idt_parceiro;
            }
            return false;
        });
    }

    public function abrirModalVisitacao(): void
    {
        if (count($this->selectedFichas) === 0) {
            return;
        }
        $this->pessoaVisitacaoId = null;
        $this->modal('modal-visitacao')->show();
    }

    public function designarVisitacao(): void
    {
        abort_if(!auth()->user()->isAdmin(), 403);

        $this->validate([
            'pessoaVisitacaoId' => 'required|exists:pessoa,idt_pessoa',
        ]);

        \App\Models\Ficha::whereIn('idt_ficha', $this->selectedFichas)
            ->update([
                'idt_pessoa_visitacao' => $this->pessoaVisitacaoId,
                'tip_situacao' => \App\Enums\TipoSituacao::SELECIONADA->value,
            ]);

        $this->modal('modal-visitacao')->close();
        $this->selectedFichas = [];
        $this->pessoaVisitacaoId = null;

        session()->flash('success', 'Visitação designada com sucesso e fichas marcadas como Selecionada.');
    }

    public function with(): array
    {
        $user = auth()->user();
        $movimentoId = $user->idt_movimento;
        $pessoa = $user->pessoa;
        $pessoaId = $pessoa?->idt_pessoa;
        $parceiroId = $pessoa?->idt_parceiro;
        $hoje = now()->startOfDay();

        // Get all active events for the filter
        $eventosAtivos = Evento::where(function ($q) use ($hoje) {
                $q->where('dat_inicio', '>=', $hoje)
                    ->orWhere('dat_termino', '>=', $hoje)
                    ->orWhereNull('dat_termino');
            })
            ->when($movimentoId, function ($q) use ($movimentoId) {
                $q->where('idt_movimento', $movimentoId);
            })
            ->orderBy('dat_inicio', 'asc')
            ->get();

        $fichasQuery = Ficha::with(['fichaVem', 'fichaEcc', 'fichaSGM', 'evento', 'visitador', 'visitador.parceiro']);

        // A ficha só pode aparecer para a pessoa logada se for o visitador designado (ou seu parceiro/cônjuge),
        // exceto se for administrador (admin), caso em que vê todas as fichas de visitação do evento.
        if (!$user->isAdmin()) {
            if (!$pessoaId) {
                $fichasQuery->whereRaw('1 = 0');
            } else {
                if ($parceiroId) {
                    $fichasQuery->whereIn('idt_pessoa_visitacao', [$pessoaId, $parceiroId]);
                } else {
                    $fichasQuery->where('idt_pessoa_visitacao', $pessoaId);
                }
            }
        }

        $fichasQuery
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('nom_candidato', 'like', '%' . $this->search . '%')
                      ->orWhere('nom_apelido', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->visitadorSearch, function ($query) {
                $query->whereHas('visitador', function ($q) {
                    $q->where(function ($inner) {
                        $inner->where('nom_pessoa', 'like', '%' . $this->visitadorSearch . '%')
                              ->orWhere('nom_apelido', 'like', '%' . $this->visitadorSearch . '%')
                              ->orWhereHas('parceiro', function ($sp) {
                                  $sp->where('nom_pessoa', 'like', '%' . $this->visitadorSearch . '%')
                                    ->orWhere('nom_apelido', 'like', '%' . $this->visitadorSearch . '%');
                              });
                    });
                });
            })
            ->when($this->eventoId, function ($query) {
                $query->where('idt_evento', $this->eventoId);
            }, function ($query) {
                $query->whereRaw('1 = 0');
            })
            ->when($this->situacao, function ($query) {
                $query->where('tip_situacao', $this->situacao);
            }, function ($query) {
                $query->whereIn('tip_situacao', [
                    TipoSituacao::SELECIONADA,
                    TipoSituacao::CONTATO,
                    TipoSituacao::AGUARDANDO
                ]);
            });

        return [
            'fichas' => $fichasQuery->orderBy('created_at', 'desc')->paginate(12),
            'eventosAtivos' => $eventosAtivos,
            'visitadores' => $this->getVisitadores(),
        ];
    }
}; ?>

<div class="space-y-6 w-full max-w-7xl mx-auto p-4 md:p-8">
    {{-- Cabeçalho --}}
    <header class="mb-8 flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-zinc-900 dark:text-zinc-100 font-sans tracking-tight">Minhas Fichas</h1>
            <p class="text-zinc-500 mt-1 dark:text-zinc-400 text-sm">Gerencie suas visitas e contatos.</p>
        </div>
    </header>

    {{-- Alerts --}}

    @if($evento && $evento->exists)
        {{-- Cabeçalho do Evento Selecionado --}}
        <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 bg-zinc-50 dark:bg-zinc-900/50 p-4 rounded-2xl border border-zinc-200 dark:border-zinc-700">
            <div>
                <flux:heading size="lg">{{ $evento->des_evento }}</flux:heading>
                <flux:subheading class="uppercase font-bold text-xs text-blue-600 dark:text-blue-400">
                    Minhas Fichas &bull; {{ $evento->movimento->des_sigla }}
                </flux:subheading>
            </div>
            <flux:button variant="ghost" size="sm" icon="arrow-left" wire:click="alterarEvento">
                Alterar Evento
            </flux:button>
        </div>

        {{-- Barra de Filtros e Busca (Apenas Admin) --}}
        @if (auth()->user()->isAdmin())
            <div class="flex flex-col gap-4">
                <div class="flex flex-col sm:flex-row gap-3 w-full items-center">
                    {{-- Busca --}}
                    <div class="w-full sm:flex-1 max-w-md">
                        <flux:input 
                            wire:model.live.debounce.300ms="search" 
                            icon="magnifying-glass" 
                            placeholder="Buscar contatos..." 
                            class="bg-white dark:bg-zinc-800"
                        />
                    </div>

                    {{-- Filtro de Casal Designado --}}
                    <div class="w-full sm:w-64">
                        <flux:input 
                            wire:model.live.debounce.300ms="visitadorSearch" 
                            icon="users" 
                            placeholder="Buscar por casal designado..." 
                            class="bg-white dark:bg-zinc-800"
                        />
                    </div>

                    {{-- Situação --}}
                    <div class="w-full sm:w-60">
                        <flux:select wire:model.live="situacao" placeholder="Todas as Situações">
                            <flux:select.option value="">Todas as Situações</flux:select.option>
                            @foreach ([
                                App\Enums\TipoSituacao::SELECIONADA, 
                                App\Enums\TipoSituacao::CONTATO, 
                                App\Enums\TipoSituacao::AGUARDANDO,
                                App\Enums\TipoSituacao::VISITADA,
                                App\Enums\TipoSituacao::CANCELADA
                            ] as $sit)
                                <flux:select.option value="{{ $sit->value }}">{{ $sit->label() }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>

                    {{-- Botão de Designar Visitação --}}
                    @if (count($selectedFichas) > 0)
                        <flux:button wire:click="abrirModalVisitacao" icon="user-group" variant="primary" class="shrink-0">
                            Designar Visitação ({{ count($selectedFichas) }})
                        </flux:button>
                    @endif
                </div>
            </div>
        @endif

    {{-- Grid de Fichas --}}
    @if ($fichas->isNotEmpty())
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($fichas as $ficha)
                @php
                    $siglaMovimento = $ficha->evento?->movimento?->des_sigla ?? 'N/A';
                    
                    // Configuração de Badge e Estilos baseada no status atual
                    $badgeConfig = match ($ficha->tip_situacao) {
                        \App\Enums\TipoSituacao::AGUARDANDO => [
                            'bg' => 'bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-900 text-amber-700 dark:text-amber-400',
                            'icon' => 'clock',
                            'label' => 'Aguardando'
                        ],
                        \App\Enums\TipoSituacao::SELECIONADA => [
                            'bg' => 'bg-blue-50 dark:bg-blue-950/20 border border-blue-200 dark:border-blue-900 text-blue-700 dark:text-blue-400',
                            'icon' => 'check-circle',
                            'label' => 'Selecionada'
                        ],
                        \App\Enums\TipoSituacao::VISITADA => [
                            'bg' => 'bg-green-50 dark:bg-green-950/20 border border-green-200 dark:border-green-900 text-green-700 dark:text-green-400',
                            'icon' => 'book-open',
                            'label' => 'Visitado'
                        ],
                        \App\Enums\TipoSituacao::CONTATO => [
                            'bg' => 'bg-cyan-50 dark:bg-cyan-950/20 border border-cyan-200 dark:border-cyan-900 text-cyan-700 dark:text-cyan-400',
                            'icon' => 'phone',
                            'label' => 'Contato Feito'
                        ],
                        \App\Enums\TipoSituacao::CANCELADA => [
                            'bg' => 'bg-rose-50 dark:bg-rose-950/20 border border-rose-200 dark:border-rose-900 text-rose-700 dark:text-rose-400',
                            'icon' => 'x-circle',
                            'label' => 'Cancelada'
                        ],
                        default => [
                            'bg' => 'bg-zinc-50 dark:bg-zinc-950/20 border border-zinc-200 dark:border-zinc-900 text-zinc-700 dark:text-zinc-400',
                            'icon' => 'document-text',
                            'label' => $ficha->tip_situacao->label()
                        ]
                    };
                @endphp
                <div class="relative bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700 p-5 shadow-sm hover:shadow-md transition duration-200 flex flex-col h-full justify-between">
                    @if (auth()->user()->isAdmin())
                        <div class="absolute top-4 right-4 z-10" wire:click.stop>
                            <input 
                                type="checkbox" 
                                wire:model.live="selectedFichas" 
                                value="{{ $ficha->idt_ficha }}" 
                                class="w-5 h-5 rounded border-zinc-300 text-blue-600 shadow-sm focus:ring-blue-500 cursor-pointer"
                            />
                        </div>
                    @endif
                    <div class="flex flex-col flex-1">
                        {{-- Top row: Badges --}}
                        <div class="flex justify-start items-center gap-1.5 mb-4">
                            <span class="bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider">
                                {{ $ficha->evento->des_evento ?? 'Sem Evento' }}
                            </span>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold {{ $badgeConfig['bg'] }}">
                                <flux:icon :icon="$badgeConfig['icon']" class="size-3" />
                                {{ $badgeConfig['label'] }}
                            </span>
                        </div>

                        {{-- Nome --}}
                        <div class="mt-2">
                            <h3 class="text-xl font-bold text-zinc-900 dark:text-zinc-100 leading-snug">
                                <a href="{{ $ficha->getShowRoute() }}" wire:navigate class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                                    {{ $ficha->nom_candidato }}
                                </a>
                            </h3>
                            @if ($ficha->nom_apelido)
                                <p class="text-sm text-zinc-500 dark:text-zinc-400 font-medium">({{ $ficha->nom_apelido }})</p>
                            @endif
                        </div>

                        {{-- Endereço --}}
                        <div class="flex items-start gap-2 text-zinc-500 dark:text-zinc-400 text-sm mt-4">
                            <flux:icon.map-pin class="size-4 shrink-0 text-zinc-400 dark:text-zinc-500 mt-0.5" />
                            <span class="leading-relaxed">{{ $ficha->des_endereco ?? 'Endereço não informado' }}</span>
                        </div>

                        {{-- Informações extras para admin --}}
                        @if (auth()->user()->isAdmin() && $ficha->visitador)
                            @php
                                $v = $ficha->visitador;
                                $nomeLabel = $v->nom_pessoa;
                                if ($v->parceiro) {
                                    $nomeLabel .= ' & ' . $v->parceiro->nom_pessoa;
                                }
                            @endphp
                            <div class="flex items-start gap-2 text-zinc-500 dark:text-zinc-400 text-xs mt-3 bg-zinc-50 dark:bg-zinc-900/30 p-2 rounded-lg border border-zinc-100 dark:border-zinc-800/40">
                                <flux:icon.user-circle class="size-4 shrink-0 text-zinc-400 mt-0.5" />
                                <span>Designado: <strong class="text-zinc-700 dark:text-zinc-300 font-semibold">{{ $nomeLabel }}</strong></span>
                            </div>
                        @endif

                        {{-- Contacts Box --}}
                        @php
                            $resp = $ficha->responsavel_info;
                        @endphp
                        <div class="bg-zinc-50 dark:bg-zinc-900/40 rounded-xl p-4 mt-5 border border-zinc-100 dark:border-zinc-800/60">
                            <div class="text-[10px] font-extrabold uppercase tracking-widest text-zinc-400 dark:text-zinc-500">
                                {{ strtoupper($resp['tipo']) }}
                            </div>
                            <div class="text-base font-semibold text-zinc-800 dark:text-zinc-200 mt-1">
                                {{ $resp['nome'] }}
                            </div>
                            <div class="text-sm text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5 mt-2.5">
                                <flux:icon.phone class="size-4 text-zinc-400 dark:text-zinc-500" />
                                <span class="font-medium">{{ $resp['telefone'] }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- CTA: Visualizar Ficha Completa --}}
                    <div class="mt-5">
                        <a 
                            href="{{ $ficha->getShowRoute() }}" 
                            wire:navigate
                            class="flex items-center justify-center w-full py-2.5 px-4 bg-blue-600 hover:bg-blue-700 text-white dark:bg-blue-600 dark:hover:bg-blue-500 font-semibold rounded-xl text-sm transition duration-150 shadow-sm shadow-blue-100 dark:shadow-none hover:shadow-md cursor-pointer"
                        >
                            <flux:icon.eye class="size-4 mr-2" />
                            <span>Visualizar Ficha Completa</span>
                        </a>
                    </div>

                    {{-- Actions Footer --}}
                    <div class="mt-6 pt-4 border-t border-zinc-100 dark:border-zinc-700">
                        <div class="text-[10px] text-zinc-400 dark:text-zinc-500 font-semibold tracking-wider text-center uppercase mb-3">
                            ATUALIZAR STATUS
                        </div>
                        
                        <div class="grid grid-cols-4 gap-2">
                            {{-- Contato Feito --}}
                            @php $isActive = $ficha->tip_situacao->value === 'F'; @endphp
                            <button 
                                wire:click="alterarSituacao({{ $ficha->idt_ficha }}, 'F')" 
                                @disabled($isActive)
                                class="flex flex-col items-center justify-center py-2.5 px-1.5 rounded-xl transition duration-150 cursor-pointer text-center text-[10px] font-medium leading-tight tracking-tight border w-full
                                {{ $isActive 
                                    ? 'bg-cyan-50/70 border-cyan-100 text-cyan-600 dark:bg-cyan-950/20 dark:border-cyan-900/60 dark:text-cyan-400 opacity-60 cursor-not-allowed' 
                                    : 'bg-white border-zinc-200 text-zinc-500 dark:bg-zinc-800 dark:border-zinc-700 dark:text-zinc-400 hover:bg-cyan-50 hover:text-cyan-700 hover:border-cyan-300 dark:hover:bg-cyan-950/20 dark:hover:text-cyan-400 dark:hover:border-cyan-800' }}"
                            >
                                <flux:icon.phone class="size-4 mb-1" />
                                <span>Contato Feito</span>
                            </button>

                            {{-- Visitado --}}
                            @php $isActive = $ficha->tip_situacao->value === 'V'; @endphp
                            <button 
                                wire:click="alterarSituacao({{ $ficha->idt_ficha }}, 'V')" 
                                @disabled($isActive)
                                class="flex flex-col items-center justify-center py-2.5 px-1.5 rounded-xl transition duration-150 cursor-pointer text-center text-[10px] font-medium leading-tight tracking-tight border w-full
                                {{ $isActive 
                                    ? 'bg-emerald-50/70 border-emerald-100 text-emerald-600 dark:bg-emerald-950/20 dark:border-emerald-900/60 dark:text-emerald-400 opacity-60 cursor-not-allowed' 
                                    : 'bg-white border-zinc-200 text-zinc-500 dark:bg-zinc-800 dark:border-zinc-700 dark:text-zinc-400 hover:bg-emerald-50 hover:text-emerald-700 hover:border-emerald-300 dark:hover:bg-emerald-950/20 dark:hover:text-emerald-400 dark:hover:border-emerald-800' }}"
                            >
                                <flux:icon.book-open class="size-4 mb-1" />
                                <span>Visitado</span>
                            </button>

                            {{-- Aguardando --}}
                            @php $isActive = $ficha->tip_situacao->value === 'W'; @endphp
                            <button 
                                wire:click="alterarSituacao({{ $ficha->idt_ficha }}, 'W')" 
                                @disabled($isActive)
                                class="flex flex-col items-center justify-center py-2.5 px-1.5 rounded-xl transition duration-150 cursor-pointer text-center text-[10px] font-medium leading-tight tracking-tight border w-full
                                {{ $isActive 
                                    ? 'bg-amber-50/70 border-amber-100 text-amber-600 dark:bg-amber-950/20 dark:border-amber-900/60 dark:text-amber-400 opacity-60 cursor-not-allowed' 
                                    : 'bg-white border-zinc-200 text-zinc-500 dark:bg-zinc-800 dark:border-zinc-700 dark:text-zinc-400 hover:bg-amber-50 hover:text-amber-700 hover:border-amber-300 dark:hover:bg-amber-950/20 dark:hover:text-amber-400 dark:hover:border-amber-800' }}"
                            >
                                <flux:icon.clock class="size-4 mb-1" />
                                <span>Aguardando</span>
                            </button>

                            {{-- Desistência --}}
                            @php $isActive = $ficha->tip_situacao->value === 'C'; @endphp
                            <button 
                                wire:click="alterarSituacao({{ $ficha->idt_ficha }}, 'C')" 
                                @disabled($isActive)
                                wire:confirm="Tem certeza de que deseja marcar esta ficha como desistência/cancelada?"
                                class="flex flex-col items-center justify-center py-2.5 px-1.5 rounded-xl transition duration-150 cursor-pointer text-center text-[10px] font-medium leading-tight tracking-tight border w-full
                                {{ $isActive 
                                    ? 'bg-rose-50/70 border-rose-100 text-rose-600 dark:bg-rose-950/20 dark:border-rose-900/60 dark:text-rose-400 opacity-60 cursor-not-allowed' 
                                    : 'bg-white border-zinc-200 text-zinc-500 dark:bg-zinc-800 dark:border-zinc-700 dark:text-zinc-400 hover:bg-rose-50 hover:text-rose-700 hover:border-rose-300 dark:hover:bg-rose-950/20 dark:hover:text-rose-400 dark:hover:border-rose-800' }}"
                            >
                                <flux:icon.x-circle class="size-4 mb-1" />
                                <span>Desistência</span>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $fichas->links(data: ['scrollTo' => false]) }}
        </div>
    @else
        <div class="flex flex-col items-center justify-center text-center p-12 bg-white dark:bg-zinc-800 rounded-xl border border-dashed border-zinc-300 dark:border-zinc-600 shadow-sm">
            <flux:icon.document-text class="w-12 h-12 text-zinc-400 dark:text-zinc-500 mb-4" />
            <flux:heading size="lg" class="text-zinc-700 dark:text-zinc-300">
                Nenhuma ficha encontrada
            </flux:heading>
            <flux:subheading class="mt-1">
                Não existem fichas designadas para a sua conta ou compatíveis com os filtros selecionados.
            </flux:subheading>
        </div>
    @endif

    {{-- Modal de Designação de Visitação --}}
    @if (auth()->user()->isAdmin())
        <flux:modal name="modal-visitacao" class="min-w-[20rem] md:min-w-[30rem]">
            <form 
                wire:submit="designarVisitacao" 
                x-data="{
                    open: false,
                    selectedId: @entangle('pessoaVisitacaoId'),
                    selectedLabel: '',
                    selectedAddress: '',
                    visitadores: {{ json_encode($visitadores->map(fn($v) => [
                        'id' => $v->idt_pessoa,
                        'label' => $v->nom_pessoa . ($v->parceiro ? ' e ' . $v->parceiro->nom_pessoa : ''),
                        'address' => $v->des_endereco ?: 'Endereço não cadastrado'
                    ])) }},
                    init() {
                        this.updateSelection();
                        this.$watch('selectedId', () => this.updateSelection());
                    },
                    updateSelection() {
                        let found = this.visitadores.find(v => v.id == this.selectedId);
                        if (found) {
                            this.selectedLabel = found.label;
                            this.selectedAddress = found.address;
                        } else {
                            this.selectedLabel = 'Selecione um visitador...';
                            this.selectedAddress = '';
                        }
                    }
                }"
                class="space-y-6 transition-all duration-200"
                :class="open ? 'pb-52' : ''"
            >
                <div>
                    <flux:heading size="lg">Designar Visitação</flux:heading>
                    <flux:subheading>Selecione o visitador (ou casal) para as {{ count($selectedFichas) }} ficha(s) selecionada(s).</flux:subheading>
                </div>

                <div class="relative">
                    <flux:label>Visitador(es)</flux:label>
                    
                    <!-- Botão do Dropdown -->
                    <button 
                        type="button" 
                        @click="open = !open" 
                        @click.away="open = false"
                        class="mt-1 w-full flex items-center justify-between rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-left p-3 cursor-pointer"
                    >
                        <div class="flex-1 min-w-0 pr-4">
                            <div class="font-medium text-sm text-zinc-900 dark:text-zinc-100 truncate text-left" x-text="selectedLabel"></div>
                            <div x-show="selectedAddress" class="text-xs text-zinc-400 dark:text-zinc-500 truncate text-left mt-0.5" x-text="selectedAddress"></div>
                        </div>
                        <flux:icon.chevron-down class="size-4 text-zinc-400 dark:text-zinc-500 shrink-0" />
                    </button>

                    <!-- Lista de Opções -->
                    <div 
                        x-show="open" 
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        class="absolute z-[110] mt-1 w-full rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 shadow-xl max-h-60 overflow-y-auto"
                        style="display: none;"
                    >
                        <ul class="p-1 divide-y divide-zinc-100 dark:divide-zinc-700/50">
                            <template x-for="item in visitadores" :key="item.id">
                                <li>
                                    <button 
                                        type="button"
                                        @click="selectedId = item.id; open = false;"
                                        class="w-full text-left p-3 rounded-md hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition flex flex-col cursor-pointer"
                                        :class="selectedId == item.id ? 'bg-blue-50/50 dark:bg-blue-950/20' : ''"
                                    >
                                        <span class="font-semibold text-sm text-zinc-900 dark:text-zinc-100" x-text="item.label"></span>
                                        <span class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5" x-text="item.address"></span>
                                    </button>
                                </li>
                            </template>
                            <li x-show="visitadores.length === 0" class="p-4 text-center text-sm text-zinc-500 italic">
                                Nenhum visitador disponível
                            </li>
                        </ul>
                    </div>

                    @error('pessoaVisitacaoId') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="flex gap-2 justify-end">
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancelar</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary">Confirmar</flux:button>
                </div>
            </form>
        </flux:modal>
    @endif
    @else
        {{-- TELA DE SELEÇÃO DO EVENTO --}}
        <div class="max-w-7xl mx-auto space-y-6 py-6">
            @if($eventosAtivos->isEmpty())
                <div class="p-8 text-center bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-2xl shadow-sm italic text-zinc-500">
                    Nenhum evento ativo cadastrado no momento.
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($eventosAtivos as $evt)
                        <article 
                            wire:click="selectEvento({{ $evt->idt_evento }})"
                            class="group flex flex-col bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 shadow-sm overflow-hidden hover:shadow-md hover:border-blue-500 hover:ring-1 hover:ring-blue-500 cursor-pointer transition-all duration-300"
                        >
                            <div class="px-5 pt-5 flex justify-between items-start">
                                <span class="px-2 py-1 bg-gray-100 dark:bg-zinc-700 rounded text-[10px] font-black uppercase text-gray-400">
                                    Nº {{ $evt->num_evento }}
                                </span>
                                <x-badge-movimento :sigla="$evt->movimento->des_sigla" />
                            </div>

                            <div class="p-5 flex-grow">
                                <h2 class="text-lg font-bold text-gray-800 dark:text-white mb-3 line-clamp-2 min-h-[3rem] group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                    {{ $evt->des_evento }}
                                </h2>

                                <div class="space-y-3">
                                    <div class="flex items-center text-gray-600 dark:text-gray-300 text-sm">
                                        <x-heroicon-o-calendar class="w-4 h-4 mr-2 text-blue-500" />
                                        <span>{{ $evt->getDataInicioFormatada() }} a {{ $evt->getDataTerminoFormatada() }}</span>
                                    </div>

                                    <div class="flex items-center text-gray-500 dark:text-gray-400 text-xs font-bold uppercase tracking-wider">
                                        <x-heroicon-o-tag class="w-4 h-4 mr-2 shrink-0" />
                                        <span class="flex-1">{{ $evt->tip_evento->label() }}</span>
                                    </div>
                                </div>
                            </div>

                            <footer class="p-4 bg-gray-50 dark:bg-zinc-800/50 border-t border-gray-100 dark:border-zinc-700 mt-auto">
                                <flux:button variant="filled" color="blue" class="w-full pointer-events-none group-hover:bg-blue-700 dark:group-hover:bg-blue-600 transition-colors">
                                    Selecionar Evento
                                </flux:button>
                            </footer>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    @endif
</div>
