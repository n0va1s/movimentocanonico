<?php

use App\Models\Ficha;
use App\Models\Pessoa;
use App\Models\User;
use App\Models\TipoMovimento;
use App\Models\Evento;
use App\Enums\TipoSituacao;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public ?int $eventoId = null;
    public string $situacao = '';
    public string $search = '';
    public string $visitadorSearch = '';
    public bool $showEventFilter = false;
    public array $selectedFichas = [];
    public ?int $pessoaVisitacaoId = null;

    public function mount(): void
    {
        if (!auth()->check() || !auth()->user()->hasRole('admin', 'visit')) {
            abort(403, 'Acesso não autorizado.');
        }

        $user = auth()->user();
        $movimentoId = $user->idt_movimento;
        $hoje = now()->startOfDay();

        $primeiroEventoAtivo = Evento::where(function ($q) use ($hoje) {
                $q->where('dat_inicio', '>=', $hoje)
                    ->orWhere('dat_termino', '>=', $hoje)
                    ->orWhereNull('dat_termino');
            })
            ->when($movimentoId, function ($q) use ($movimentoId) {
                $q->where('idt_movimento', $movimentoId);
            })
            ->orderBy('dat_inicio', 'asc')
            ->first();

        if ($primeiroEventoAtivo) {
            $this->eventoId = $primeiroEventoAtivo->idt_evento;
        }
    }

    public function updatedEventoId(): void
    {
        $this->resetPage();
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

    public function toggleEventFilter(): void
    {
        $this->showEventFilter = !$this->showEventFilter;
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

                {{-- Botão de Eventos --}}
                <flux:button 
                    icon="funnel" 
                    variant="ghost" 
                    class="shrink-0 border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700/50" 
                    wire:click="toggleEventFilter"
                    title="Filtrar por Evento"
                />

                {{-- Botão de Designar Visitação --}}
                @if (count($selectedFichas) > 0)
                    <flux:button wire:click="abrirModalVisitacao" icon="user-group" variant="primary" class="shrink-0">
                        Designar Visitação ({{ count($selectedFichas) }})
                    </flux:button>
                @endif
            </div>

            {{-- Filtro Avançado de Evento (Expansível) --}}
            @if ($showEventFilter || !$eventoId)
                <div class="p-4 bg-zinc-50 dark:bg-zinc-900/50 rounded-xl border border-zinc-200/50 dark:border-zinc-700/50 flex flex-col gap-2 max-w-md transition duration-200">
                    <span class="text-xs font-semibold text-zinc-500 dark:text-zinc-400">Filtrar por Evento Ativo</span>
                    <flux:select wire:model.live="eventoId" placeholder="Selecione um Evento Ativo">
                        <flux:select.option value="">Selecione um Evento Ativo</flux:select.option>
                        @foreach ($eventosAtivos as $ev)
                            <flux:select.option value="{{ $ev->idt_evento }}">{{ $ev->des_evento }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            @endif
        </div>
    @elseif (!$eventoId)
        {{-- Selecionar Evento Ativo para Visitador sem Evento pré-selecionado --}}
        <div class="p-4 bg-zinc-50 dark:bg-zinc-900/50 rounded-xl border border-zinc-200/50 dark:border-zinc-700/50 flex flex-col gap-2 max-w-md">
            <span class="text-xs font-semibold text-zinc-500 dark:text-zinc-400">Selecionar Evento Ativo</span>
            <flux:select wire:model.live="eventoId" placeholder="Selecione um Evento Ativo">
                <flux:select.option value="">Selecione um Evento Ativo</flux:select.option>
                @foreach ($eventosAtivos as $ev)
                    <flux:select.option value="{{ $ev->idt_evento }}">{{ $ev->des_evento }}</flux:select.option>
                @endforeach
            </flux:select>
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
                @if (!$eventoId)
                    Selecione um Evento Ativo
                @else
                    Nenhuma ficha encontrada
                @endif
            </flux:heading>
            <flux:subheading class="mt-1">
                @if (!$eventoId)
                    Selecione um dos eventos ativos no filtro acima para visualizar suas fichas designadas.
                @else
                    Não existem fichas designadas para a sua conta ou compatíveis com os filtros selecionados.
                @endif
            </flux:subheading>
        </div>
    @endif

    {{-- Modal de Designação de Visitação --}}
    @if (auth()->user()->isAdmin())
        <flux:modal name="modal-visitacao" class="min-w-[20rem] md:min-w-[30rem]">
            <form wire:submit="designarVisitacao" class="space-y-6">
                <div>
                    <flux:heading size="lg">Designar Visitação</flux:heading>
                    <flux:subheading>Selecione o visitador (ou casal) para as {{ count($selectedFichas) }} ficha(s) selecionada(s).</flux:subheading>
                </div>

                <div>
                    <flux:label>Visitador(es)</flux:label>
                    <select wire:model.live="pessoaVisitacaoId" class="mt-1 block w-full rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2">
                        <option value="">Selecione um visitador...</option>
                        @foreach ($visitadores as $visitador)
                            <option value="{{ $visitador->idt_pessoa }}">
                                {{ $visitador->nom_pessoa }}
                                @if ($visitador->parceiro)
                                    e {{ $visitador->parceiro->nom_pessoa }}
                                @endif
                            </option>
                        @endforeach
                    </select>
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
</div>
