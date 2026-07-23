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
use Symfony\Component\HttpFoundation\StreamedResponse;

new class extends Component {
    use WithPagination;

    public ?Evento $evento = null;
    public ?int $eventoId = null;
    public string $situacao = '';
    public string $search = '';
    public string $visitadorSearch = '';
    public string $modalSearch = '';
    public bool $apenasSemDesignacao = false;
    public array $selectedFichas = [];
    public ?int $pessoaVisitacaoId = null;
    public bool $readyToLoad = false;
    public string $activeTab = 'gerenciar';

    public function loadData(): void
    {
        $this->readyToLoad = true;
    }

    public function updatedActiveTab(): void
    {
        if ($this->activeTab === 'consulta' && !$this->podeDesignar()) {
            $this->activeTab = 'gerenciar';
        }
        $this->resetPage();
    }

    public function toggleSituacao(string $novaSituacao): void
    {
        if ($this->situacao === $novaSituacao) {
            $this->situacao = '';
        } else {
            $this->situacao = $novaSituacao;
        }
        $this->resetPage();
    }

    #[Computed]
    public function statusCounts(): array
    {
        if (!$this->eventoId || !$this->podeDesignar()) {
            return [];
        }

        $query = Ficha::query()->where('idt_evento', $this->eventoId);

        $counts = $query->select('tip_situacao', \DB::raw('count(*) as total'))
            ->whereIn('tip_situacao', [
                TipoSituacao::SELECIONADA->value,
                TipoSituacao::CONTATO->value,
                TipoSituacao::AGUARDANDO->value,
                TipoSituacao::VISITADA->value
            ])
            ->groupBy('tip_situacao')
            ->pluck('total', 'tip_situacao')
            ->toArray();

        return [
            TipoSituacao::SELECIONADA->value => $counts[TipoSituacao::SELECIONADA->value] ?? 0,
            TipoSituacao::CONTATO->value => $counts[TipoSituacao::CONTATO->value] ?? 0,
            TipoSituacao::AGUARDANDO->value => $counts[TipoSituacao::AGUARDANDO->value] ?? 0,
            TipoSituacao::VISITADA->value => $counts[TipoSituacao::VISITADA->value] ?? 0,
        ];
    }

    public function mount(?Evento $evento = null): void
    {
        $user = auth()->user();
        if (!$user || !$user->autorizaVisit()) {
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

    public function updatedApenasSemDesignacao(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedFichas($value): void
    {
        if (count($this->selectedFichas) > 3) {
            $this->selectedFichas = array_slice($this->selectedFichas, 0, 3);
            $this->addError('selectedFichas', 'Você pode selecionar no máximo 3 fichas para designação.');
            $this->dispatch('notify', message: 'Você pode selecionar no máximo 3 fichas para designação.', type: 'erro');
        }
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
        $visitadores = $visitadoresRaw->reject(function ($v) use (&$processed) {
            if (in_array($v->idt_pessoa, $processed)) {
                return true;
            }

            $partnerId = $v->idt_parceiro ?: \App\Models\Pessoa::where('idt_parceiro', $v->idt_pessoa)->value('idt_pessoa');

            // Calcular quantidade de fichas atribuídas ao casal/pessoa no evento selecionado
            $fichaCount = \App\Models\Ficha::where('idt_evento', $this->eventoId)
                ->where(function ($q) use ($v, $partnerId) {
                    $q->where('idt_pessoa_visitacao', $v->idt_pessoa)
                      ->when($partnerId, function ($q2) use ($partnerId) {
                          $q2->orWhere('idt_pessoa_visitacao', $partnerId);
                      });
                })
                ->count();

            $v->ficha_count = $fichaCount;

            if ($partnerId) {
                $processed[] = $partnerId;
            }
            return false;
        });

        // Filtrar no backend conforme busca digitada no modal
        $modalSearch = trim($this->modalSearch);
        if ($modalSearch !== '') {
            $visitadores = $visitadores->filter(function ($v) use ($modalSearch) {
                $label = $v->nom_pessoa . ($v->parceiro ? ' e ' . $v->parceiro->nom_pessoa : '');
                $address = $v->des_endereco ?: '';
                return str_contains(Str::lower($label), Str::lower($modalSearch)) 
                    || str_contains(Str::lower($address), Str::lower($modalSearch));
            });
        }

        return $visitadores;
    }

    public function abrirModalVisitacao(): void
    {
        if (count($this->selectedFichas) === 0) {
            $this->addError('selectedFichas', 'Selecione pelo menos uma ficha para designação.');
            $this->dispatch('notify', message: 'Selecione pelo menos uma ficha para designação.', type: 'erro');
            return;
        }

        if (count($this->selectedFichas) > 3) {
            $this->selectedFichas = array_slice($this->selectedFichas, 0, 3);
            $this->addError('selectedFichas', 'Você pode selecionar no máximo 3 fichas para designação.');
            $this->dispatch('notify', message: 'Você pode selecionar no máximo 3 fichas para designação.', type: 'erro');
        }

        // Only allow if user is admin or coordinator
        if (!$this->podeDesignar()) {
            $this->dispatch('notify', message: 'Você não tem permissão para designar visitadores.', type: 'erro');
            return;
        }

        $this->pessoaVisitacaoId = null;
        $this->modalSearch = '';
        $this->modal('modal-visitacao')->show();
    }

    public function designarVisitacao(): void
    {
        abort_if(!$this->podeDesignar(), 403);

        $this->validate([
            'pessoaVisitacaoId' => 'required|exists:pessoa,idt_pessoa',
        ]);

        $v = \App\Models\Pessoa::with('parceiro')->findOrFail($this->pessoaVisitacaoId);

        $partnerId = $v->idt_parceiro ?: \App\Models\Pessoa::where('idt_parceiro', $v->idt_pessoa)->value('idt_pessoa');

        $currentCount = \App\Models\Ficha::where('idt_evento', $this->eventoId)
            ->where(function ($q) use ($v, $partnerId) {
                $q->where('idt_pessoa_visitacao', $v->idt_pessoa)
                  ->when($partnerId, function ($q2) use ($partnerId) {
                      $q2->orWhere('idt_pessoa_visitacao', $partnerId);
                  });
            })
            ->whereNotIn('idt_ficha', $this->selectedFichas)
            ->count();

        $selectedCount = count($this->selectedFichas);
        $totalCountAfterDesignation = $currentCount + $selectedCount;

        if ($totalCountAfterDesignation > 3) {
            $nomeCasal = $v->nom_pessoa . ($v->parceiro ? ' e ' . $v->parceiro->nom_pessoa : '');
            $restantes = 3 - $currentCount;

            if ($restantes <= 0) {
                $msg = "O visitador/casal {$nomeCasal} já possui o limite de 3 fichas designadas.";
            } else {
                $msg = "Não é possível designar as {$selectedCount} fichas para {$nomeCasal}. O limite é de 3 fichas por visitador/casal, e eles já possuem {$currentCount} ficha(s) designada(s) (máximo disponível: {$restantes}).";
            }

            $this->addError('pessoaVisitacaoId', $msg);
            return;
        }

        \App\Models\Ficha::whereIn('idt_ficha', $this->selectedFichas)
            ->update([
                'idt_pessoa_visitacao' => $this->pessoaVisitacaoId,
                'tip_situacao' => \App\Enums\TipoSituacao::SELECIONADA->value,
            ]);

        $this->modal('modal-visitacao')->close();
        $this->selectedFichas = [];
        $this->pessoaVisitacaoId = null;
        $this->modalSearch = '';

        session()->flash('success', 'Visitação designada com sucesso e fichas marcadas como Selecionada.');
    }

    public function limparDesignacao(int $fichaId): void
    {
        abort_if(!$this->podeDesignar(), 403);

        $ficha = \App\Models\Ficha::findOrFail($fichaId);
        $ficha->update([
            'idt_pessoa_visitacao' => null,
        ]);

        $this->dispatch('notify', message: 'Designação de visitador removida com sucesso.', type: 'sucesso');
        \Flux::toast('Designação do visitador removida com sucesso.', variant: 'success');
    }


    public function podeDesignar(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($this->eventoId) {
            return \App\Models\Trabalhador::where('idt_evento', $this->eventoId)
                ->where('idt_pessoa', $user->pessoa?->idt_pessoa)
                ->where('ind_coordenador', true)
                ->whereHas('equipe', function ($q) {
                    $q->where('des_grupo', 'like', '%Visitação%');
                })
                ->exists();
        }

        return false;
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
        // exceto se for administrador (admin) ou se puder designar, caso em que vê todas as fichas de visitação do evento.
        if (!$this->podeDesignar()) {
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
            ->when($this->apenasSemDesignacao, function ($query) {
                $query->whereNull('idt_pessoa_visitacao');
            })
            ->when($this->eventoId, function ($query) {
                $query->where('idt_evento', $this->eventoId);
            }, function ($query) {
                $query->whereRaw('1 = 0');
            })
            ->when($this->situacao, function ($query) {
                $query->where('tip_situacao', $this->situacao);
            }, function ($query) {
                if ($this->activeTab === 'consulta') {
                    $query->whereIn('tip_situacao', [
                        TipoSituacao::SELECIONADA,
                        TipoSituacao::CONTATO,
                        TipoSituacao::AGUARDANDO,
                        TipoSituacao::VISITADA
                    ]);
                } else {
                    $query->whereIn('tip_situacao', [
                        TipoSituacao::SELECIONADA,
                        TipoSituacao::CONTATO,
                        TipoSituacao::AGUARDANDO
                    ]);
                }
            });

        // Garantia de segurança backend
        if ($this->activeTab === 'consulta' && !$this->podeDesignar()) {
            $this->activeTab = 'gerenciar';
        }

        return [
            'fichas' => $fichasQuery->orderBy('created_at', 'desc')->paginate(12),
            'eventosAtivos' => $eventosAtivos,
            'visitadores' => $this->getVisitadores(),
        ];
    }

    public function exportar(): StreamedResponse
    {
        return $this->gerarExportacao(false);
    }

    public function exportarAdmin(): StreamedResponse
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Acesso não autorizado.');
        }
        return $this->gerarExportacao(true);
    }

    private function gerarExportacao(bool $isAdmin): StreamedResponse
    {
        $user = auth()->user();
        $pessoa = $user->pessoa;
        $pessoaId = $pessoa?->idt_pessoa;
        $parceiroId = $pessoa?->idt_parceiro;

        $fichasQuery = Ficha::with(['fichaVem', 'fichaEcc', 'fichaSGM', 'evento', 'visitador', 'visitador.parceiro']);

        if (!$this->podeDesignar()) {
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

        $fichas = $fichasQuery
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
            ->when($this->apenasSemDesignacao, function ($query) {
                $query->whereNull('idt_pessoa_visitacao');
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
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $cabecalho = [
            'Nome',
            'Sexo',
            'Data de Nascimento',
            'Endereço',
            'Casal Visitação',
            'Situação',
            'Tamanho Camiseta',
            'Restrição de Saúde',
            'Paroquiano',
            'Região',
        ];

        if ($isAdmin) {
            $cabecalho = array_merge($cabecalho, [
                'Telefone do Candidato',
                'Nome do Pai',
                'Telefone do Pai',
                'Nome da Mãe',
                'Telefone da Mãe',
                'Falar com (Nome)',
                'Falar com (Telefone)',
            ]);
        }

        $nomeArquivo = 'fichas_' . ($isAdmin ? 'admin_' : '') . \Str::slug($this->evento->des_evento ?? 'evento') . '_' . now()->format('Y-m-d') . '.csv';

        return new StreamedResponse(function () use ($fichas, $cabecalho, $isAdmin) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, $cabecalho, ';');

            foreach ($fichas as $ficha) {
                $movimentoId = (int) $ficha->evento->idt_movimento;
                $nomParoquia = match ($movimentoId) {
                    \App\Models\TipoMovimento::VEM => $ficha->fichaVem?->nom_paroquia,
                    \App\Models\TipoMovimento::ECC => $ficha->fichaEcc?->nom_paroquia,
                    \App\Models\TipoMovimento::SGM => $ficha->fichaSGM?->nom_paroquia,
                    default => '',
                } ?? '';

                $isParoquiano = preg_match('/PNSL|Nossa Senhora do Lago/i', (string) $nomParoquia) ? 'Sim' : 'Não';
                $isRegiao = preg_match('/Lago Norte|SHIN|Setor de Mans.es|Taquari|Varj.o/iu', (string) $ficha->des_endereco) ? 'Sim' : 'Não';

                $row = [
                    $ficha->nom_candidato,
                    $ficha->tip_genero ? $ficha->tip_genero->value : '',
                    $ficha->dat_nascimento ? $ficha->dat_nascimento->format('d/m/Y') : '',
                    $ficha->des_endereco,
                    $ficha->visitador ? $ficha->visitador->nom_pessoa : '',
                    $ficha->tip_situacao ? $ficha->tip_situacao->label() : '',
                    $ficha->tam_camiseta ? $ficha->tam_camiseta->value : '',
                    $ficha->ind_restricao ? 'Sim' : 'Não',
                    $isParoquiano,
                    $isRegiao,
                ];

                if ($isAdmin) {
                    $nomPai = $ficha->fichaVem?->nom_pai ?? $ficha->fichaSGM?->nom_pai ?? '';
                    $telPai = $ficha->fichaVem?->tel_pai ?? $ficha->fichaSGM?->tel_pai ?? '';
                    $nomMae = $ficha->fichaVem?->nom_mae ?? $ficha->fichaSGM?->nom_mae ?? '';
                    $telMae = $ficha->fichaVem?->tel_mae ?? $ficha->fichaSGM?->tel_mae ?? '';
                    
                    $resp = $ficha->responsavel_info;
                    $nomFalarCom = (isset($resp['nome']) && $resp['nome'] !== 'Não informado') ? $resp['nome'] : '';
                    $telFalarCom = (isset($resp['telefone']) && $resp['telefone'] !== 'Não informado') ? $resp['telefone'] : '';

                    $row = array_merge($row, [
                        $ficha->tel_candidato,
                        $nomPai,
                        $telPai,
                        $nomMae,
                        $telMae,
                        $nomFalarCom,
                        $telFalarCom,
                    ]);
                }

                fputcsv($handle, $row, ';');
            }
            fclose($handle);
        });
    }
}; ?>

<div class="space-y-6 w-full max-w-7xl mx-auto p-4 md:p-8">
    {{-- Cabeçalho --}}
    <header class="mb-8 flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" class="text-indigo-900 dark:text-indigo-100 font-bold tracking-tight mb-1">Minhas Fichas</flux:heading>
            <p class="text-zinc-500 mt-1 dark:text-zinc-400 text-sm">Gerencie suas visitas e contatos.</p>
        </div>
    </header>

    {{-- Alerts --}}

    <div wire:init="loadData">
        @if(!$readyToLoad)
            <div class="flex items-center justify-center min-h-[50vh]">
                <div class="animate-pulse flex flex-col items-center">
                    <div class="w-12 h-12 border-4 border-zinc-200 dark:border-zinc-700 border-t-indigo-600 rounded-full animate-spin"></div>
                    <p class="mt-4 text-indigo-600 dark:text-indigo-400 font-medium tracking-tight">Carregando os dados das Fichas...</p>
                </div>
            </div>
        @else

    @if($evento && $evento->exists)
        <div class="space-y-6">
            {{-- Cabeçalho do Evento Selecionado --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-zinc-50 dark:bg-zinc-900/50 p-4 rounded-2xl border border-zinc-200 dark:border-zinc-700">
            <div class="text-left">
                <flux:heading size="lg" class="mb-1.5">{{ $evento->des_evento }}</flux:heading>
                <div class="flex flex-wrap gap-2 items-center">
                    <x-badge-movimento :sigla="$evento->movimento->des_sigla" />
                    
                    {{-- Botões de Exportar na tela Minhas Fichas --}}
                    <flux:button wire:click="exportar" icon="arrow-down-tray" variant="outline" size="xs" title="Exportar CSV">
                        Exportar
                    </flux:button>
                    @if(auth()->user()->isAdmin())
                        <flux:button wire:click="exportarAdmin" icon="arrow-down-tray" variant="outline" size="xs" title="Exportar CSV Admin">
                            Exportar Admin
                        </flux:button>
                    @endif
                </div>
            </div>
            <flux:button variant="ghost" size="sm" icon="arrow-left" wire:click="alterarEvento" class="h-11 shrink-0">
                Alterar Evento
            </flux:button>
        </div>

        @if ($this->podeDesignar())
            {{-- Menu Local de Abas --}}
            <div class="flex overflow-x-auto no-scrollbar border-b border-zinc-200 dark:border-zinc-700 whitespace-nowrap mb-6" role="tablist">
                <button 
                    wire:click="$set('activeTab', 'gerenciar')" 
                    role="tab"
                    aria-selected="{{ $activeTab === 'gerenciar' ? 'true' : 'false' }}"
                    class="px-4 py-2 font-semibold text-sm border-b-2 {{ $activeTab === 'gerenciar' ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }} transition-colors cursor-pointer"
                >
                    Minhas Visitas
                </button>
                <button 
                    wire:click="$set('activeTab', 'consulta')" 
                    role="tab"
                    aria-selected="{{ $activeTab === 'consulta' ? 'true' : 'false' }}"
                    class="px-4 py-2 font-semibold text-sm border-b-2 {{ $activeTab === 'consulta' ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }} transition-colors cursor-pointer"
                >
                    Consulta Geral
                </button>
            </div>
        @endif

        @if ($activeTab === 'gerenciar' || !$this->podeDesignar())
            {{-- Barra de Filtros e Busca --}}
            @if ($this->podeDesignar())
                <div class="flex flex-col gap-4 w-full bg-zinc-50 dark:bg-zinc-900/40 p-5 rounded-2xl border border-zinc-200 dark:border-zinc-700/60 shadow-sm mb-6">
                    <div class="flex flex-col md:flex-row items-center gap-4 w-full">
                        {{-- Busca --}}
                        <div class="w-full md:flex-1">
                            <flux:input 
                                wire:model.live.debounce.300ms="search" 
                                icon="magnifying-glass" 
                                placeholder="Buscar candidatos..." 
                                class="bg-white dark:bg-zinc-800 h-11 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>

                        {{-- Filtro de Casal Designado --}}
                        <div class="w-full md:flex-1">
                            <flux:input 
                                wire:model.live.debounce.300ms="visitadorSearch" 
                                icon="users" 
                                placeholder="Buscar por casal designado..." 
                                class="bg-white dark:bg-zinc-800 h-11 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                :disabled="$apenasSemDesignacao"
                            />
                        </div>

                        {{-- Situação --}}
                        <div class="w-full md:flex-1">
                            <flux:select 
                                wire:model.live="situacao" 
                                placeholder="Todas as Situações"
                                class="bg-white dark:bg-zinc-800 h-11 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            >
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
                    </div>

                    {{-- Linha inferior com opções de visualização / toggles --}}
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-3 border-t border-zinc-200/50 dark:border-zinc-700/50 w-full min-h-[44px]">
                        <div class="flex items-center gap-6">
                            <flux:switch 
                                wire:model.live="apenasSemDesignacao" 
                                label="Apenas fichas sem visitador designado" 
                                class="text-zinc-600 dark:text-zinc-400 font-medium text-sm"
                            />
                        </div>
                        
                        {{-- Botão de Designar Visitação --}}
                        @if (count($selectedFichas) > 0)
                            <div class="w-full sm:w-auto shrink-0 transition-all duration-300">
                                <flux:button wire:click="abrirModalVisitacao" icon="user-group" variant="primary" class="h-10 px-5 w-full justify-center">
                                    Designar Visitação ({{ count($selectedFichas) }})
                                </flux:button>
                            </div>
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
                            $badgeConfig = $ficha->tip_situacao->cardConfig();
                        @endphp
                        <div wire:key="ficha-card-{{ $ficha->idt_ficha }}" class="relative bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700 p-5 shadow-sm hover:shadow-md transition duration-200 flex flex-col h-full justify-between">
                            @if ($this->podeDesignar())
                                <div class="absolute top-4 left-4 z-10" wire:click.stop>
                                    <input 
                                        type="checkbox" 
                                        wire:model.live="selectedFichas" 
                                        value="{{ $ficha->idt_ficha }}" 
                                        wire:key="'checkbox-minhas-fichas-'.$ficha->idt_ficha"
                                        class="w-5 h-5 rounded border-zinc-300 text-blue-600 shadow-sm focus:ring-blue-500 cursor-pointer"
                                    />
                                </div>
                            @endif
                            <div class="flex flex-col flex-1">
                                {{-- Top row: Badges --}}
                                <div class="flex justify-end items-center gap-1.5 mb-4">
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

                                @php
                                    $isDeMaior = $ficha->dat_nascimento && \Carbon\Carbon::parse($ficha->dat_nascimento)->age >= 18;
                                @endphp

                                {{-- Telefone do Jovem (se de maior) --}}
                                @if ($isDeMaior && $ficha->tel_candidato)
                                    <div class="flex items-start gap-2 text-zinc-500 dark:text-zinc-400 text-sm mt-4">
                                        <flux:icon.phone class="size-4 shrink-0 text-zinc-400 dark:text-zinc-500 mt-0.5" />
                                        <a href="https://wa.me/55{{ \App\Services\PhoneService::clean($ficha->tel_candidato) }}" target="_blank" class="hover:underline hover:text-blue-600 dark:hover:text-blue-400 leading-relaxed font-medium">
                                            {{ $ficha->tel_candidato }}
                                        </a>
                                    </div>
                                @endif

                                {{-- Endereço --}}
                                <div class="flex items-start gap-2 text-zinc-500 dark:text-zinc-400 text-sm {{ $isDeMaior && $ficha->tel_candidato ? 'mt-2' : 'mt-4' }}">
                                    <flux:icon.map-pin class="size-4 shrink-0 text-zinc-400 dark:text-zinc-500 mt-0.5" />
                                    <span class="leading-relaxed">{{ $ficha->des_endereco ?? 'Endereço não informado' }}</span>
                                </div>

                                {{-- Informações extras para admin --}}
                                @if ($this->podeDesignar() && $ficha->visitador)
                                    @php
                                        $v = $ficha->visitador;
                                        $nomeLabel = $v->nom_pessoa;
                                        if ($v->parceiro) {
                                            $nomeLabel .= ' & ' . $v->parceiro->nom_pessoa;
                                        }
                                    @endphp
                                    <div class="flex items-center justify-between gap-2 text-zinc-500 dark:text-zinc-400 text-xs mt-3 bg-zinc-50 dark:bg-zinc-900/30 p-2 rounded-lg border border-zinc-100 dark:border-zinc-800/40">
                                        <div class="flex items-start gap-2 min-w-0">
                                            <flux:icon.user-circle class="size-4 shrink-0 text-zinc-400 mt-0.5" />
                                            <span class="truncate">Designado: <strong class="text-zinc-700 dark:text-zinc-300 font-semibold">{{ $nomeLabel }}</strong></span>
                                        </div>
                                        <button 
                                            type="button"
                                            wire:click="limparDesignacao({{ $ficha->idt_ficha }})"
                                            wire:confirm="Tem certeza de que deseja remover a designação de visitador desta ficha?"
                                            class="shrink-0 text-zinc-400 hover:text-red-500 hover:bg-zinc-200 dark:hover:bg-zinc-700 p-0.5 rounded transition-colors cursor-pointer"
                                            title="Limpar Designação"
                                        >
                                            <flux:icon.x-mark class="size-3.5" />
                                        </button>
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
                                        @if ($resp['telefone'] && $resp['telefone'] !== 'Não informado')
                                            <a href="https://wa.me/55{{ \App\Services\PhoneService::clean($resp['telefone']) }}" target="_blank" class="hover:underline hover:text-blue-600 dark:hover:text-blue-400 font-medium">
                                                {{ $resp['telefone'] }}
                                            </a>
                                        @else
                                            <span class="font-medium">{{ $resp['telefone'] }}</span>
                                        @endif
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
                            </div>

                            {{-- Actions Footer --}}
                            <div class="mt-6 pt-4 border-t border-zinc-100 dark:border-zinc-700">
                                <div class="text-[10px] text-zinc-400 dark:text-zinc-500 font-semibold tracking-wider text-center uppercase mb-3">
                                    ATUALIZAR STATUS
                                </div>
                                
                                <div class="flex flex-row gap-2 w-full">
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
                        Nenhuma ficha encontrada
                    </flux:heading>
                    <flux:subheading class="mt-1">
                        Não existem fichas designadas para a sua conta ou compatíveis com os filtros selecionados.
                        Não existem fichas designadas para a sua conta ou compatíveis com os filtros selecionados.
                    </flux:subheading>
                </div>
            @endif
        @else
            {{-- Dashboard de Status --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
                @foreach ([
                    ['status' => App\Enums\TipoSituacao::SELECIONADA, 'bg' => 'bg-blue-500/10 text-blue-500 border-blue-500/20', 'icon' => 'check-circle'],
                    ['status' => App\Enums\TipoSituacao::CONTATO, 'bg' => 'bg-cyan-500/10 text-cyan-500 border-cyan-500/20', 'icon' => 'phone'],
                    ['status' => App\Enums\TipoSituacao::AGUARDANDO, 'bg' => 'bg-amber-500/10 text-amber-500 border-amber-500/20', 'icon' => 'clock'],
                    ['status' => App\Enums\TipoSituacao::VISITADA, 'bg' => 'bg-green-500/10 text-green-500 border-green-500/20', 'icon' => 'book-open'],
                ] as $item)
                    @php
                        $sitEnum = $item['status'];
                        $val = $sitEnum->value;
                        $count = $this->statusCounts[$val] ?? 0;
                        $isActive = $situacao === $val;
                    @endphp
                    <button 
                        type="button"
                        wire:click="toggleSituacao('{{ $val }}')"
                        class="flex items-center gap-2.5 p-3 rounded-xl border text-left transition duration-200 hover:shadow-md cursor-pointer w-full group
                        {{ $isActive 
                            ? 'bg-indigo-50 border-indigo-500 text-indigo-700 dark:bg-indigo-950/20 dark:border-indigo-800 dark:text-indigo-400 ring-2 ring-indigo-500/30' 
                            : 'bg-white border-zinc-200 text-zinc-600 dark:bg-zinc-800 dark:border-zinc-700 dark:text-zinc-300 hover:border-zinc-300 dark:hover:border-zinc-600' }}"
                    >
                        <div class="p-1.5 rounded-lg {{ $item['bg'] }} transition group-hover:scale-110 shrink-0">
                            <flux:icon :icon="$item['icon']" class="size-4" />
                        </div>
                        <div class="min-w-0">
                            <div class="text-xl font-bold text-zinc-900 dark:text-zinc-100 leading-none">{{ $count }}</div>
                            <div class="text-[11px] text-zinc-500 dark:text-zinc-400 font-semibold truncate mt-0.5">{{ $sitEnum->label() }}</div>
                        </div>
                    </button>
                @endforeach
            </div>

            {{-- Barra de Filtros e Busca --}}
            <div class="flex flex-col md:flex-row items-center gap-4 w-full bg-zinc-50 dark:bg-zinc-900/40 p-5 rounded-2xl border border-zinc-200 dark:border-zinc-700/60 shadow-sm mb-6">
                {{-- Busca Jovem --}}
                <div class="w-full md:flex-1">
                    <flux:input 
                        wire:model.live.debounce.300ms="search" 
                        icon="magnifying-glass" 
                        placeholder="Buscar candidatos..." 
                        class="bg-white dark:bg-zinc-800 h-11 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    />
                </div>

                {{-- Busca Casal Designado --}}
                <div class="w-full md:flex-1">
                    <flux:input 
                        wire:model.live.debounce.300ms="visitadorSearch" 
                        icon="users" 
                        placeholder="Buscar por casal designado..." 
                        class="bg-white dark:bg-zinc-800 h-11 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    />
                </div>

                {{-- Situação --}}
                <div class="w-full md:flex-1">
                    <flux:select 
                        wire:model.live="situacao" 
                        placeholder="Todas as Situações"
                        class="bg-white dark:bg-zinc-800 h-11 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    >
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
            </div>

            {{-- Tabela de Fichas --}}
            @if ($fichas->isNotEmpty())
                {{-- Tabela para Desktop --}}
                <div class="hidden md:block w-full border border-zinc-200 dark:border-zinc-700 rounded-xl overflow-hidden bg-white dark:bg-zinc-800">
                    <style>
                        /* Estilo para descolar tabelas das paredes do container no desktop */
                        .fichas-table [data-flux-column]:first-child,
                        .fichas-table [data-flux-cell]:first-child {
                            padding-left: 1.5rem !important;
                        }
                        .fichas-table [data-flux-column]:last-child,
                        .fichas-table [data-flux-cell]:last-child {
                            padding-right: 1.5rem !important;
                        }
                    </style>
                    <flux:table class="w-full fichas-table">
                        <flux:table.columns>
                            <flux:table.column class="pl-6 pr-4">Candidato</flux:table.column>
                            <flux:table.column class="px-4">Casal Designado</flux:table.column>
                            <flux:table.column class="w-36 px-4" align="center">Status</flux:table.column>
                            <flux:table.column class="w-20 pl-4 pr-6" align="end">Ações</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach ($fichas as $ficha)
                                @php
                                    $badgeConfig = $ficha->tip_situacao->cardConfig();
                                    $v = $ficha->visitador;
                                @endphp
                                <flux:table.row :key="'row-'.$ficha->idt_ficha">
                                    <flux:table.cell class="pl-6 pr-4 font-semibold text-zinc-900 dark:text-white">
                                        <div class="flex flex-col font-sans">
                                            <a href="{{ $ficha->getShowRoute() }}" wire:navigate class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                                                {{ $ficha->nom_candidato }}
                                            </a>
                                            @if ($ficha->nom_apelido)
                                                <span class="text-xs text-zinc-400 font-normal">({{ $ficha->nom_apelido }})</span>
                                            @endif
                                        </div>
                                    </flux:table.cell>
                                    <flux:table.cell class="px-4 text-zinc-700 dark:text-zinc-300 font-medium">
                                        @if ($v)
                                            <div class="flex flex-col max-w-[200px] md:max-w-[240px]">
                                                <span class="truncate font-medium text-zinc-700 dark:text-zinc-300" title="{{ $v->nom_pessoa }}">
                                                    {{ $v->nom_pessoa }}
                                                </span>
                                                @if ($v->parceiro)
                                                    <span class="truncate font-medium text-zinc-700 dark:text-zinc-300 mt-0.5" title="& {{ $v->parceiro->nom_pessoa }}">
                                                        & {{ $v->parceiro->nom_pessoa }}
                                                    </span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-zinc-400 italic">Sem designação</span>
                                        @endif
                                    </flux:table.cell>
                                    <flux:table.cell class="w-36 px-4" align="center">
                                        <div class="flex justify-center w-full">
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded text-xs font-bold {{ $badgeConfig['bg'] }} shrink-0">
                                                <flux:icon :icon="$badgeConfig['icon']" class="size-3.5" />
                                                {{ $badgeConfig['label'] }}
                                            </span>
                                        </div>
                                    </flux:table.cell>
                                    <flux:table.cell class="w-20 pl-4 pr-6" align="end">
                                        <flux:button 
                                            size="sm" 
                                            icon="eye" 
                                            variant="ghost" 
                                            href="{{ $ficha->getShowRoute() }}" 
                                            wire:navigate
                                            title="Visualizar"
                                            aria-label="Visualizar"
                                        />
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>

                {{-- Cards para Mobile --}}
                <div class="md:hidden space-y-4">
                    @foreach ($fichas as $ficha)
                        @php
                            $badgeConfig = $ficha->tip_situacao->cardConfig();
                            $nomeLabel = $ficha->visitador 
                                ? $ficha->visitador->nom_pessoa . ($ficha->visitador->parceiro ? ' & ' . $ficha->visitador->parceiro->nom_pessoa : '')
                                : 'Sem designação';
                        @endphp
                        <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl p-4 shadow-sm flex flex-col space-y-3">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h4 class="font-bold text-zinc-900 dark:text-zinc-100">
                                        <a href="{{ $ficha->getShowRoute() }}" wire:navigate class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                                            {{ $ficha->nom_candidato }}
                                        </a>
                                    </h4>
                                    @if ($ficha->nom_apelido)
                                        <p class="text-xs text-zinc-500">({{ $ficha->nom_apelido }})</p>
                                    @endif
                                </div>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold {{ $badgeConfig['bg'] }}">
                                    <flux:icon :icon="$badgeConfig['icon']" class="size-3" />
                                    {{ $badgeConfig['label'] }}
                                </span>
                            </div>
                            
                            <div class="text-sm text-zinc-600 dark:text-zinc-400">
                                <span class="font-semibold text-zinc-700 dark:text-zinc-300">Casal Designado:</span> {{ $nomeLabel }}
                            </div>
                            
                            <div class="pt-2 border-t border-zinc-100 dark:border-zinc-700/50">
                                <flux:button 
                                    size="sm" 
                                    icon="eye" 
                                    variant="primary" 
                                    class="w-full justify-center"
                                    href="{{ $ficha->getShowRoute() }}" 
                                    wire:navigate
                                >
                                    Visualizar Ficha Completa
                                </flux:button>
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
                        Não existem fichas compatíveis com os filtros selecionados.
                    </flux:subheading>
                </div>
            @endif
        @endif

    {{-- Modal de Designação de Visitação --}}
    @if ($this->podeDesignar() && count($selectedFichas) > 0)
        <flux:modal name="modal-visitacao" class="min-w-[20rem] md:min-w-[30rem]">
            <form 
                wire:submit="designarVisitacao" 
                x-data="{
                    open: false,
                    selectedId: @entangle('pessoaVisitacaoId'),
                    selectedLabel: 'Selecione um visitador...',
                    selectedAddress: '',
                    init() {
                        this.$watch('selectedId', (val) => {
                            if (!val) {
                                this.selectedLabel = 'Selecione um visitador...';
                                this.selectedAddress = '';
                            }
                        });
                        this.$watch('open', (val) => {
                            if (val) {
                                this.$nextTick(() => {
                                    this.$refs.searchInput?.focus();
                                });
                            }
                        });
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
                        <!-- Campo de busca Livewire -->
                        <div class="p-2 border-b border-zinc-100 dark:border-zinc-700/50 sticky top-0 bg-white dark:bg-zinc-800 z-[120]" @click.stop>
                            <flux:input 
                                wire:model.live.debounce.250ms="modalSearch"
                                x-ref="searchInput"
                                placeholder="Buscar visitador ou casal..."
                                icon="magnifying-glass"
                                size="sm"
                                class="w-full focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>

                        <ul class="p-1 divide-y divide-zinc-100 dark:divide-zinc-700/50">
                            @foreach ($visitadores as $v)
                                @php
                                    $label = $v->nom_pessoa . ($v->parceiro ? ' e ' . $v->parceiro->nom_pessoa : '');
                                    $address = $v->des_endereco ?: 'Endereço não cadastrado';
                                    $isCompleto = ($v->ficha_count ?? 0) >= 3;
                                @endphp
                                <li>
                                    <button 
                                        type="button"
                                        @if ($isCompleto)
                                            disabled
                                            class="w-full text-left p-3 rounded-md bg-zinc-50/50 dark:bg-zinc-800/50 opacity-40 cursor-not-allowed flex flex-col"
                                        @else
                                            @click="selectedId = {{ $v->idt_pessoa }}; selectedLabel = '{{ addslashes($label) }}'; selectedAddress = '{{ addslashes($address) }}'; open = false;"
                                            class="w-full text-left p-3 rounded-md hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition flex flex-col cursor-pointer"
                                            :class="selectedId == {{ $v->idt_pessoa }} ? 'bg-blue-50/50 dark:bg-blue-950/20' : ''"
                                        @endif
                                    >
                                        <div class="flex items-center justify-between w-full">
                                            <span class="font-semibold text-sm text-zinc-900 dark:text-zinc-100 truncate pr-2">{{ $label }}</span>
                                            @if ($isCompleto)
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-zinc-200 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300 shrink-0">
                                                    Completo (3/3)
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400 shrink-0">
                                                    {{ $v->ficha_count ?? 0 }}/3 fichas
                                                </span>
                                            @endif
                                        </div>
                                        <span class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5 truncate w-full text-left">{{ $address }}</span>
                                    </button>
                                </li>
                            @endforeach
                            @if ($visitadores->isEmpty())
                                <li class="p-4 text-center text-sm text-zinc-500 italic">
                                    Nenhum visitador encontrado para a busca
                                </li>
                            @endif
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
        </div>
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
                                <flux:button variant="primary" class="w-full bg-gradient-to-r from-indigo-500 to-purple-500 hover:from-indigo-600 hover:to-purple-600 border-none shadow-md cursor-pointer">
                                    Selecionar Evento
                                </flux:button>
                            </footer>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    @endif
    @endif
    </div>
</div>
