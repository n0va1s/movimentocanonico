<?php

use App\Models\Evento;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

new class extends Component {
    use WithPagination;

    public Evento $evento;
    public string $search = '';
    public string $situacao = '';
    public string $generoFiltro = '';
    public bool $vinculoFiltroParoquiano = false;
    public bool $vinculoFiltroRegiao = false;
    public array $selectedFichas = [];
    public ?int $pessoaVisitacaoId = null;
    public bool $selectAll = false;
    public ?string $filtroSituacao = null;

    public function mount(Evento $evento): void
    {
        $this->evento = $evento;
    }

    // Reseta a paginação quando a busca muda
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    // Reseta a paginação quando o gênero muda
    public function updatedGeneroFiltro(): void
    {
        $this->resetPage();
    }

    // Reseta a paginação quando o filtro de paroquiano muda
    public function updatedVinculoFiltroParoquiano(): void
    {
        $this->resetPage();
    }

    // Reseta a paginação quando o filtro de região muda
    public function updatedVinculoFiltroRegiao(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedFichas($value): void
    {
        if (count($this->selectedFichas) > 3) {
            $this->selectedFichas = array_slice($this->selectedFichas, 0, 3);
            $this->dispatch('notify',
                message: "Você pode selecionar no máximo 3 fichas para designação.",
                type: 'erro'
            );
        }
    }

    public function updatedSelectAll($value): void
    {
        if ($value) {
            $query = \App\Models\Ficha::where('idt_evento', $this->evento->idt_evento)
                ->when($this->filtroSituacao, function ($query) {
                    $query->where('tip_situacao', $this->filtroSituacao);
                })
                ->when($this->search, function ($query) {
                    $query->where(function ($q) {
                        $q->where('nom_candidato', 'like', '%' . $this->search . '%')
                            ->orWhere('nom_apelido', 'like', '%' . $this->search . '%');
                    });
                });

            $total = $query->count();

            $this->selectedFichas = $query->limit(3)
                ->pluck('idt_ficha')
                ->map(fn($id) => (string)$id)
                ->toArray();

            if ($total > 3) {
                $this->selectAll = false;
                $this->dispatch('notify',
                    message: "Apenas as 3 primeiras fichas foram selecionadas devido ao limite máximo.",
                    type: 'info'
                );
            }
        } else {
            $this->selectedFichas = [];
        }
    }

    public function toggleFiltroSituacao(?string $status): void
    {
        if ($this->filtroSituacao === $status) {
            $this->filtroSituacao = null;
        } else {
            $this->filtroSituacao = $status;
        }
        $this->resetPage();
        
        $this->selectedFichas = [];
        $this->selectAll = false;
    }

    public function abrirModalVisitacao(): void
    {
        if (count($this->selectedFichas) === 0) {
            return;
        }

        if (count($this->selectedFichas) > 3) {
            $this->selectedFichas = array_slice($this->selectedFichas, 0, 3);
            $this->dispatch('notify',
                message: "Você pode selecionar no máximo 3 fichas para designação.",
                type: 'erro'
            );
            return;
        }

        $this->pessoaVisitacaoId = null;
        $this->modal('modal-visitacao')->show();
    }

    public function designarVisitacao(): void
    {
        $this->validate([
            'pessoaVisitacaoId' => 'required|exists:pessoa,idt_pessoa',
        ]);

        $v = \App\Models\Pessoa::with('parceiro')->findOrFail($this->pessoaVisitacaoId);

        $partnerId = $v->idt_parceiro ?: \App\Models\Pessoa::where('idt_parceiro', $v->idt_pessoa)->value('idt_pessoa');

        $currentCount = \App\Models\Ficha::where('idt_evento', $this->evento->idt_evento)
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
        $this->selectAll = false;

        $this->dispatch('notify',
            message: "Visitação designada com sucesso e fichas marcadas como Selecionada.",
            type: 'sucesso'
        );
    }

    private function getVisitadores()
    {
        if (!$this->evento?->idt_evento) {
            return collect();
        }

        $visitadoresRaw = \App\Models\Pessoa::where(function ($query) {
            $query->whereHas('trabalhadores', function ($q) {
                $q->where('idt_evento', $this->evento->idt_evento)
                  ->whereHas('equipe', function ($qe) {
                      $qe->where('des_grupo', 'like', '%Visitação%');
                  });
            })
            ->orWhereHas('parceiro.trabalhadores', function ($q) {
                $q->where('idt_evento', $this->evento->idt_evento)
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

            $partnerId = $v->idt_parceiro ?: \App\Models\Pessoa::where('idt_parceiro', $v->idt_pessoa)->value('idt_pessoa');

            // Ocultar casal/pessoa caso já tenha 3 ou mais fichas atribuídas no evento atual
            $fichaCount = \App\Models\Ficha::where('idt_evento', $this->evento->idt_evento)
                ->where(function ($q) use ($v, $partnerId) {
                    $q->where('idt_pessoa_visitacao', $v->idt_pessoa)
                      ->when($partnerId, function ($q2) use ($partnerId) {
                          $q2->orWhere('idt_pessoa_visitacao', $partnerId);
                      });
                })
                ->count();

            if ($fichaCount >= 3) {
                return true;
            }

            if ($partnerId) {
                $processed[] = $partnerId;
            }
            return false;
        });
    }

    public function atualizarSituacao(int $fichaId, string $situacaoValor): void
    {
        try {
            $situacao = \App\Enums\TipoSituacao::from($situacaoValor);
            $ficha = \App\Services\FichaService::atualizarSituacaoFicha($fichaId, $situacao);

            $this->dispatch('notify',
                message: "A situação da ficha de {$ficha->nom_apelido} foi alterada para {$situacao->label()}.",
                type: 'sucesso'
            );
        } catch (\RuntimeException $e) {
            $this->dispatch('notify',
                message: $e->getMessage(),
                type: 'erro'
            );
        }
    }

    public function excluirFicha(int $fichaId): void
    {
        $ficha = \App\Models\Ficha::findOrFail($fichaId);
        $nome  = $ficha->nom_candidato;

        $ficha->delete();

        $this->dispatch('notify',
            message: "A ficha de {$nome} foi excluída.",
            type: 'info'
        );
    }

    /**
     * Resolve as rotas de show, edit e destroy de acordo com o movimento do evento.
     */
    private function rotasPorMovimento(\App\Models\Ficha $ficha): array
    {
        return match ((int) $ficha->evento->idt_movimento) {
            \App\Models\TipoMovimento::VEM     => ['show' => 'vem.show',  'edit' => 'vem.edit',  'destroy' => 'vem.destroy'],
            \App\Models\TipoMovimento::SGM => ['show' => 'sgm.show',  'edit' => 'sgm.edit',  'destroy' => 'sgm.destroy'],
            \App\Models\TipoMovimento::ECC     => ['show' => 'ecc.show',  'edit' => 'ecc.edit',  'destroy' => 'ecc.destroy'],
            default                            => ['show' => 'vem.show',  'edit' => 'vem.edit',  'destroy' => 'vem.destroy'],
        };
    }

    public function exportar(): StreamedResponse
    {
        $fichas = \App\Models\Ficha::with(['visitador', 'fichaVem', 'fichaEcc', 'fichaSGM'])
            ->where('idt_evento', $this->evento->idt_evento)
            ->when($this->situacao, function ($query) {
                $query->where('tip_situacao', $this->situacao);
            })
            ->when($this->generoFiltro, function ($query) {
                $query->where('tip_genero', $this->generoFiltro);
            })
            ->when($this->vinculoFiltroParoquiano, function ($query) {
                $movimentoId = (int) $this->evento->idt_movimento;
                $query->where(function ($q) use ($movimentoId) {
                    if ($movimentoId === \App\Models\TipoMovimento::VEM) {
                        $q->whereHas('fichaVem', function ($q2) {
                            $q2->where('nom_paroquia', 'like', '%PNSL%')
                               ->orWhere('nom_paroquia', 'like', '%Nossa Senhora do Lago%');
                        });
                    } elseif ($movimentoId === \App\Models\TipoMovimento::ECC) {
                        $q->whereHas('fichaEcc', function ($q2) {
                            $q2->where('nom_paroquia', 'like', '%PNSL%')
                               ->orWhere('nom_paroquia', 'like', '%Nossa Senhora do Lago%');
                        });
                    } elseif ($movimentoId === \App\Models\TipoMovimento::SGM) {
                        $q->whereHas('fichaSGM', function ($q2) {
                            $q2->where('nom_paroquia', 'like', '%PNSL%')
                               ->orWhere('nom_paroquia', 'like', '%Nossa Senhora do Lago%');
                        });
                    }
                });
            })
            ->when($this->vinculoFiltroRegiao, function ($query) {
                $query->where(function ($q) {
                    $q->where('des_endereco', 'like', '%Lago Norte%')
                      ->orWhere('des_endereco', 'like', '%SHIN%')
                      ->orWhere('des_endereco', 'like', '%Setor de Mansões%')
                      ->orWhere('des_endereco', 'like', '%Taquari%')
                      ->orWhere('des_endereco', 'like', '%Varjão%');
                });
            })
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('nom_candidato', 'like', '%' . $this->search . '%')
                        ->orWhere('nom_apelido', 'like', '%' . $this->search . '%');
                });
            })
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

        $nomeArquivo = 'fichas_' . \Str::slug($this->evento->nom_evento ?? 'evento') . '_' . now()->format('Y-m-d') . '.csv';

        $response = new StreamedResponse(function () use ($fichas, $cabecalho) {
            $handle = fopen('php://output', 'w');

            // BOM para o Excel reconhecer UTF-8 corretamente
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, $cabecalho, ';');

            foreach ($fichas as $ficha) {
                // Recuperar nome da paróquia dependendo estritamente do movimento do evento
                $movimentoId = (int) $ficha->evento->idt_movimento;
                $nomParoquia = match ($movimentoId) {
                    \App\Models\TipoMovimento::VEM => $ficha->fichaVem?->nom_paroquia,
                    \App\Models\TipoMovimento::ECC => $ficha->fichaEcc?->nom_paroquia,
                    \App\Models\TipoMovimento::SGM => $ficha->fichaSGM?->nom_paroquia,
                    default => '',
                } ?? '';

                // Paroquiano: PNSL ou Nossa Senhora do Lago
                $isParoquiano = preg_match('/PNSL|Nossa Senhora do Lago/i', (string) $nomParoquia) ? 'Sim' : 'Não';

                // Região: Lago Norte, SHIN, Setor de Mansões, Taquari, Varjão
                $isRegiao = preg_match('/Lago Norte|SHIN|Setor de Mans.es|Taquari|Varj.o/iu', (string) $ficha->des_endereco) ? 'Sim' : 'Não';

                fputcsv($handle, [
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
                ], ';');
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $nomeArquivo . '"');

        return $response;
    }

    #[Computed]
    public function contadores(): array
    {
        $counts = \App\Models\Ficha::where('idt_evento', $this->evento->idt_evento)
            ->select('tip_situacao', DB::raw('count(*) as total'))
            ->groupBy('tip_situacao')
            ->pluck('total', 'tip_situacao')
            ->toArray();

        return [
            \App\Enums\TipoSituacao::NOVA->value => $counts[\App\Enums\TipoSituacao::NOVA->value] ?? 0,
            \App\Enums\TipoSituacao::AGUARDANDO->value => $counts[\App\Enums\TipoSituacao::AGUARDANDO->value] ?? 0,
            \App\Enums\TipoSituacao::VISITADA->value => $counts[\App\Enums\TipoSituacao::VISITADA->value] ?? 0,
            \App\Enums\TipoSituacao::SELECIONADA->value => $counts[\App\Enums\TipoSituacao::SELECIONADA->value] ?? 0,
            \App\Enums\TipoSituacao::APROVADA->value => $counts[\App\Enums\TipoSituacao::APROVADA->value] ?? 0,
            \App\Enums\TipoSituacao::CANCELADA->value => $counts[\App\Enums\TipoSituacao::CANCELADA->value] ?? 0,
        ];
    }

    public function with(): array
    {
        return [
            'fichas' => \App\Models\Ficha::where('idt_evento', $this->evento->idt_evento)
                ->with(['evento', 'fichaVem', 'fichaEcc', 'fichaSGM', 'visitador'])
                ->when($this->filtroSituacao, function ($query) {
                    $query->where('tip_situacao', $this->filtroSituacao);
                })
                ->when($this->generoFiltro, function ($query) {
                    $query->where('tip_genero', $this->generoFiltro);
                })
                ->when($this->vinculoFiltroParoquiano, function ($query) {
                    $movimentoId = (int) $this->evento->idt_movimento;
                    $query->where(function ($q) use ($movimentoId) {
                        if ($movimentoId === \App\Models\TipoMovimento::VEM) {
                            $q->whereHas('fichaVem', function ($q2) {
                                $q2->where('nom_paroquia', 'like', '%PNSL%')
                                   ->orWhere('nom_paroquia', 'like', '%Nossa Senhora do Lago%');
                            });
                        } elseif ($movimentoId === \App\Models\TipoMovimento::ECC) {
                            $q->whereHas('fichaEcc', function ($q2) {
                                $q2->where('nom_paroquia', 'like', '%PNSL%')
                                   ->orWhere('nom_paroquia', 'like', '%Nossa Senhora do Lago%');
                            });
                        } elseif ($movimentoId === \App\Models\TipoMovimento::SGM) {
                            $q->whereHas('fichaSGM', function ($q2) {
                                $q2->where('nom_paroquia', 'like', '%PNSL%')
                                   ->orWhere('nom_paroquia', 'like', '%Nossa Senhora do Lago%');
                            });
                        }
                    });
                })
                ->when($this->vinculoFiltroRegiao, function ($query) {
                    $query->where(function ($q) {
                        $q->where('des_endereco', 'like', '%Lago Norte%')
                          ->orWhere('des_endereco', 'like', '%SHIN%')
                          ->orWhere('des_endereco', 'like', '%Setor de Mansões%')
                          ->orWhere('des_endereco', 'like', '%Taquari%')
                          ->orWhere('des_endereco', 'like', '%Varjão%');
                    });
                })
                ->when($this->search, function ($query) {
                    $query->where(function ($q) {
                        $q->where('nom_candidato', 'like', '%' . $this->search . '%')
                            ->orWhere('nom_apelido', 'like', '%' . $this->search . '%');
                    });
                })
                ->paginate(10),
            'visitadores' => $this->getVisitadores(),
        ];
    }
}; ?>

<div class="space-y-4">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <div class="flex items-center gap-3">
                <flux:heading size="lg">Fichas de Inscrição</flux:heading>
                <flux:badge size="sm" color="zinc" inset="top bottom" title="Total filtrado">{{ $fichas->total() }}</flux:badge>
                <flux:button wire:click="exportar" icon="arrow-down-tray" variant="outline" size="sm" title="Exportar CSV">
                    Exportar
                </flux:button>
            </div>
            <flux:subheading>Analise e aprove os candidatos para este evento.</flux:subheading>
        </div>

        <div class="flex flex-col md:flex-row gap-2 w-full md:w-auto items-end md:items-center">
            @if(count($selectedFichas) > 0)
                <flux:button wire:click="abrirModalVisitacao" icon="user-group" variant="primary" class="w-full md:w-auto shrink-0">
                    Designar Visitação ({{ count($selectedFichas) }})
                </flux:button>
            @endif

            <div class="flex flex-col sm:flex-row gap-2 w-full md:w-auto items-end">
                <flux:select wire:model.live="generoFiltro" icon="funnel" placeholder="Todos os sexos" class="w-full sm:w-36">
                    <option value="">Todos os sexos</option>
                    <option value="M">Masculino</option>
                    <option value="F">Feminino</option>
                </flux:select>

                <flux:dropdown>
                    <flux:button icon="link" variant="outline" class="w-full sm:w-44 justify-between">
                        <span>Vínculos</span>
                        @php
                            $count = ($vinculoFiltroParoquiano ? 1 : 0) + ($vinculoFiltroRegiao ? 1 : 0);
                        @endphp
                        @if ($count > 0)
                            <flux:badge size="sm" color="indigo" class="ml-2 shrink-0">{{ $count }}</flux:badge>
                        @endif
                    </flux:button>
                    <flux:menu class="p-3 space-y-3 min-w-[14rem]">
                        <flux:checkbox wire:model.live="vinculoFiltroParoquiano" label="Paroquialidade" />
                        <flux:checkbox wire:model.live="vinculoFiltroRegiao" label="Região" />
                    </flux:menu>
                </flux:dropdown>

                <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" 
                    placeholder="Buscar..." class="w-full sm:w-44" />
            </div>
        </div>
    </div>

    {{-- Dashboard de Status --}}
    @php
        $statusCards = [
            [
                'status' => \App\Enums\TipoSituacao::NOVA->value,
                'label' => 'Novas',
                'color' => 'blue',
                'icon' => 'document-text',
                'textClass' => 'text-blue-600 dark:text-blue-400',
                'activeClass' => 'ring-2 ring-blue-500 bg-blue-50 dark:bg-blue-950/20 border-blue-500',
            ],
            [
                'status' => \App\Enums\TipoSituacao::AGUARDANDO->value,
                'label' => 'Aguardando',
                'color' => 'amber',
                'icon' => 'clock',
                'textClass' => 'text-amber-500 dark:text-amber-400',
                'activeClass' => 'ring-2 ring-amber-500 bg-amber-50 dark:bg-amber-950/20 border-amber-500',
            ],
            [
                'status' => \App\Enums\TipoSituacao::VISITADA->value,
                'label' => 'Visitadas',
                'color' => 'purple',
                'icon' => 'map-pin',
                'textClass' => 'text-purple-600 dark:text-purple-400',
                'activeClass' => 'ring-2 ring-purple-500 bg-purple-50 dark:bg-purple-950/20 border-purple-500',
            ],
            [
                'status' => \App\Enums\TipoSituacao::SELECIONADA->value,
                'label' => 'Selecionadas',
                'color' => 'teal',
                'icon' => 'check-badge',
                'textClass' => 'text-teal-600 dark:text-teal-400',
                'activeClass' => 'ring-2 ring-teal-500 bg-teal-50 dark:bg-teal-950/20 border-teal-500',
            ],
            [
                'status' => \App\Enums\TipoSituacao::APROVADA->value,
                'label' => 'Aprovadas',
                'color' => 'emerald',
                'icon' => 'check-circle',
                'textClass' => 'text-emerald-600 dark:text-emerald-400',
                'activeClass' => 'ring-2 ring-emerald-500 bg-emerald-50 dark:bg-emerald-950/20 border-emerald-500',
            ],
            [
                'status' => \App\Enums\TipoSituacao::CANCELADA->value,
                'label' => 'Canceladas',
                'color' => 'rose',
                'icon' => 'x-circle',
                'textClass' => 'text-rose-600 dark:text-rose-400',
                'activeClass' => 'ring-2 ring-rose-500 bg-rose-50 dark:bg-rose-950/20 border-rose-500',
            ],
        ];
    @endphp

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
        @foreach ($statusCards as $card)
            @php
                $isActive = $filtroSituacao === $card['status'];
                $count = $this->contadores[$card['status']] ?? 0;
            @endphp
            <div 
                wire:click="toggleFiltroSituacao('{{ $card['status'] }}')"
                class="cursor-pointer transition-all duration-200 rounded-xl p-3 flex flex-col border shadow-sm hover:shadow-md hover:-translate-y-0.5 {{ $isActive ? $card['activeClass'] : 'bg-white dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700' }}"
            >
                <div class="flex items-center gap-2">
                    <flux:icon name="{{ $card['icon'] }}" variant="outline" class="size-5 {{ $card['textClass'] }} shrink-0" />
                    <span class="text-xs font-semibold text-zinc-500 dark:text-zinc-400">{{ $card['label'] }}</span>
                </div>
                <h4 class="text-xl font-bold text-zinc-900 dark:text-white mt-2">{{ $count }}</h4>
            </div>
        @endforeach
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-10">
                <flux:checkbox wire:model.live="selectAll" wire:key="checkbox-select-all" />
            </flux:table.column>
            <flux:table.column>Candidato</flux:table.column>
            <flux:table.column>Data Nasc</flux:table.column>
            <flux:table.column>Endereço</flux:table.column>
            <flux:table.column>Situação</flux:table.column>
            <flux:table.column>Vínculo</flux:table.column>
            <flux:table.column align="end">Ações</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($fichas as $ficha)
                @php
                    $movimentoId = (int) $ficha->evento->idt_movimento;
                    $nomParoquia = match ($movimentoId) {
                        \App\Models\TipoMovimento::VEM => $ficha->fichaVem?->nom_paroquia,
                        \App\Models\TipoMovimento::ECC => $ficha->fichaEcc?->nom_paroquia,
                        \App\Models\TipoMovimento::SGM => $ficha->fichaSGM?->nom_paroquia,
                        default => '',
                    } ?? '';
                    $isParoquiano = preg_match('/PNSL|Nossa Senhora do Lago/i', (string) $nomParoquia);
                    $isRegiao = preg_match('/Lago Norte|SHIN|Setor de Mans.es|Taquari|Varj.o/iu', (string) $ficha->des_endereco);
                @endphp
            <flux:table.row :key="'ficha-'.$ficha->idt_ficha">
                <flux:table.cell>
                    <flux:checkbox wire:model.live="selectedFichas" value="{{ $ficha->idt_ficha }}" wire:key="'checkbox-'.$ficha->idt_ficha" />
                </flux:table.cell>
                <flux:table.cell>
                    <div class="font-medium text-zinc-900 dark:text-white flex items-center gap-1.5">
                        <span>{{ $ficha->nom_candidato }}</span>
                        @if ($ficha->tip_genero === \App\Enums\Genero::MASCULINO)
                            <svg class="w-2.5 h-2.5 text-blue-500 dark:text-blue-400 shrink-0 cursor-default" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="4.5" stroke-linecap="round" stroke-linejoin="round" title="Masculino">
                                <circle cx="10" cy="14" r="6" />
                                <path d="M16 8L21 3" />
                                <path d="M15 3H21V9" />
                            </svg>
                        @elseif ($ficha->tip_genero === \App\Enums\Genero::FEMININO)
                            <svg class="w-2.5 h-2.5 text-pink-500 dark:text-pink-400 shrink-0 cursor-default" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="4.5" stroke-linecap="round" stroke-linejoin="round" title="Feminino">
                                <circle cx="12" cy="9" r="6" />
                                <path d="M12 15V22" />
                                <path d="M8 18H16" />
                            </svg>
                        @endif
                    </div>
                    <div class="text-xs text-zinc-500">{{ $ficha->nom_apelido }}</div>
                    @if(!$ficha->num_cpf_candidato)
                        <div class="flex items-center gap-1 mt-1">
                            <flux:icon name="exclamation-triangle" variant="outline" class="size-3 text-amber-500" />
                            <span class="text-[10px] text-amber-600 font-semibold">CPF não informado</span>
                        </div>
                    @endif
                    @if($ficha->visitador)
                        <div class="flex items-center gap-1 mt-1">
                            <flux:icon name="user-group" class="size-3 text-zinc-500" />
                            <span class="text-[11px] text-zinc-500 font-medium">
                                Visitação: {{ $ficha->visitador->nom_pessoa }}
                                @if($ficha->visitador->parceiro)
                                    e {{ $ficha->visitador->parceiro->nom_pessoa }}
                                @endif
                            </span>
                        </div>
                    @endif
                </flux:table.cell>

                <flux:table.cell>
                    <div class="text-sm">
                        {{ \Carbon\Carbon::parse($ficha->dat_nascimento)->format('d/m/Y') }}
                        <span class="text-zinc-400 text-xs ml-1">
                            ({{ \Carbon\Carbon::parse($ficha->dat_nascimento)->age }} anos)
                        </span>
                    </div>
                </flux:table.cell>

                <flux:table.cell class="max-w-xs truncate" title="{{ $ficha->des_endereco }}">
                    <flux:text size="sm" class="truncate block">{{ $ficha->des_endereco ?: '—' }}</flux:text>
                </flux:table.cell>

                <flux:table.cell>
                    <select
                        wire:change="atualizarSituacao({{ $ficha->idt_ficha }}, $event.target.value)"
                        class="w-40 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-800 dark:text-zinc-200 px-2.5 py-1.5 text-xs font-semibold shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 border-l-[5px] {{ $ficha->tip_situacao?->badge()['border-l'] ?? 'border-l-zinc-200 dark:border-l-zinc-700' }}">
                        @foreach (\App\Enums\TipoSituacao::cases() as $situacao)
                            <option value="{{ $situacao->value }}" @selected($ficha->tip_situacao === $situacao) class="text-zinc-800 bg-white dark:bg-zinc-900 dark:text-zinc-200">
                                {{ $situacao->label() }}
                            </option>
                        @endforeach
                    </select>
                </flux:table.cell>

                <flux:table.cell>
                    <div class="space-y-1 text-xs">
                        <div class="flex items-center gap-1" title="Paróquia: {{ $nomParoquia ?: 'Não informada' }}">
                            @if ($isParoquiano)
                                <span class="text-green-600 dark:text-green-400 font-medium">Paroquiano</span>
                                <flux:icon.check class="size-3 text-green-500 shrink-0" />
                            @else
                                <span class="text-red-600 dark:text-red-400 font-medium">Não Paroquiano</span>
                                <flux:icon.x-mark class="size-3 text-red-500 shrink-0" />
                            @endif
                        </div>
                        <div class="flex items-center gap-1">
                            @if ($isRegiao)
                                <span class="text-green-600 dark:text-green-400 font-medium">Região</span>
                                <flux:icon.check class="size-3 text-green-500 shrink-0" />
                            @else
                                <span class="text-red-600 dark:text-red-400 font-medium">Região</span>
                                <flux:icon.x-mark class="size-3 text-red-500 shrink-0" />
                            @endif
                        </div>
                    </div>
                </flux:table.cell>

                <flux:table.cell align="end">
                    <div class="flex justify-end gap-2">
                        @php
                            $rotas = $this->rotasPorMovimento($ficha);
                        @endphp

                        {{-- Ver detalhes --}}
                        <flux:button
                            variant="ghost"
                            size="sm"
                            icon="eye"
                            href="{{ route($rotas['show'], $ficha) }}"
                            title="Ver Detalhes"
                        />

                        <flux:dropdown>
                            <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />
                            <flux:menu>

                                {{-- Alterar --}}
                                <flux:menu.item
                                    icon="pencil-square"
                                    href="{{ route($rotas['edit'], $ficha) }}"
                                >
                                    Alterar
                                </flux:menu.item>

                                {{-- Imprimir — abre show com ?print=1 em nova aba --}}
                                <flux:menu.item
                                    icon="printer"
                                    href="{{ route($rotas['show'], $ficha) }}?print=1"
                                    target="_blank"
                                >
                                    Imprimir
                                </flux:menu.item>

                                <flux:menu.separator />

                                {{-- Excluir --}}
                                <flux:menu.item
                                    variant="danger"
                                    icon="trash"
                                    wire:click="excluirFicha({{ $ficha->idt_ficha }})"
                                    wire:confirm="Tem certeza que deseja excluir a ficha de {{ $ficha->nom_candidato }}? Esta ação não pode ser desfeita."
                                >
                                    Excluir
                                </flux:menu.item>

                            </flux:menu>
                        </flux:dropdown>
                    </div>
                </flux:table.cell>
            </flux:table.row>
            @empty
            <flux:table.row>
                <flux:table.cell colspan="7" class="text-center py-12">
                    <div class="flex flex-col items-center">
                        <x-heroicon-o-document-magnifying-glass class="w-12 h-12 text-zinc-300 mb-2" />
                        <flux:text>Nenhuma ficha encontrada para este critério.</flux:text>
                    </div>
                </flux:table.cell>
            </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <div class="mt-4">
        {{ $fichas->links(data: ['scrollTo' => false]) }}
    </div>

    {{-- Modal de Designação de Visitação --}}
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
                        @foreach ($visitadores as $v)
                            @php
                                $label = $v->nom_pessoa . ($v->parceiro ? ' e ' . $v->parceiro->nom_pessoa : '');
                                $address = $v->des_endereco ?: 'Endereço não cadastrado';
                            @endphp
                            <li>
                                <button 
                                    type="button"
                                    @click="selectedId = {{ $v->idt_pessoa }}; selectedLabel = '{{ addslashes($label) }}'; selectedAddress = '{{ addslashes($address) }}'; open = false;"
                                    class="w-full text-left p-3 rounded-md hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition flex flex-col cursor-pointer"
                                    :class="selectedId == {{ $v->idt_pessoa }} ? 'bg-blue-50/50 dark:bg-blue-950/20' : ''"
                                >
                                    <span class="font-semibold text-sm text-zinc-900 dark:text-zinc-100">{{ $label }}</span>
                                    <span class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">{{ $address }}</span>
                                </button>
                            </li>
                        @endforeach
                        @if ($visitadores->isEmpty())
                            <li class="p-4 text-center text-sm text-zinc-500 italic">
                                Nenhum visitador disponível
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
</div>
