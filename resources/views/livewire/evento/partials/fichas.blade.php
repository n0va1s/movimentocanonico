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
     * Resolve as rotas de show, edit de acordo com o movimento do evento.
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
        $fichas = \App\Models\Ficha::with(['visitador', 'fichaVem', 'fichaEcc', 'fichaSGM'])
            ->where('idt_evento', $this->evento->idt_evento)
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

        $response = new StreamedResponse(function () use ($fichas, $cabecalho, $isAdmin) {
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
            \App\Enums\TipoSituacao::RESERVA->value => $counts[\App\Enums\TipoSituacao::RESERVA->value] ?? 0,
            \App\Enums\TipoSituacao::RESERVA->value => $counts[\App\Enums\TipoSituacao::RESERVA->value] ?? 0,
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
        ];
    }
}; ?>

<div class="space-y-4">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <flux:heading size="lg">Fichas de Inscrição</flux:heading>
                <flux:badge size="sm" color="zinc" inset="top bottom" title="Total filtrado">{{ $fichas->total() }}</flux:badge>
                <flux:button wire:click="exportar" icon="arrow-down-tray" variant="outline" size="sm" title="Exportar CSV">
                    Exportar
                </flux:button>
                @if(auth()->user()->isAdmin())
                    <flux:button wire:click="exportarAdmin" icon="arrow-down-tray" variant="outline" size="sm" title="Exportar CSV Completo para Admin">
                        Exportar Admin
                    </flux:button>
                @endif
            </div>
            <flux:subheading class="mt-1">Analise e aprove os candidatos para este evento.</flux:subheading>
        </div>

        @if(count($selectedFichas) > 0)
            <flux:button wire:click="abrirModalVisitacao" icon="user-group" variant="primary" class="w-full md:w-auto shrink-0">
                Designar Visitação ({{ count($selectedFichas) }})
            </flux:button>
        @endif
    </div>

    {{-- Filtros e Busca --}}
    <div class="flex flex-col md:flex-row justify-between gap-3 w-full border-t border-zinc-100 dark:border-zinc-700/50 pt-4">
        <div class="flex gap-2 w-full md:w-auto">
            <flux:select wire:model.live="generoFiltro" icon="funnel" placeholder="Todos os sexos" class="flex-1 md:flex-initial md:w-44">
                <option value="">Todos os sexos</option>
                <option value="M">Masculino</option>
                <option value="F">Feminino</option>
            </flux:select>

            <flux:dropdown class="flex-1 md:flex-initial">
                <flux:button icon="link" variant="outline" class="w-full md:w-44 justify-between">
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
        </div>

        <div class="w-full md:w-72">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" 
                placeholder="Buscar candidato..." class="w-full" />
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
                'status' => \App\Enums\TipoSituacao::RESERVA->value,
                'label' => 'Reservas',
                'color' => 'fuchsia',
                'icon' => 'bookmark',
                'textClass' => 'text-fuchsia-600 dark:text-fuchsia-400',
                'activeClass' => 'ring-2 ring-fuchsia-500 bg-fuchsia-50 dark:bg-fuchsia-950/20 border-fuchsia-500',
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

    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-7 gap-4 mb-6">
        @foreach ($statusCards as $card)
            @php
                $isActive = $filtroSituacao === $card['status'];
                $count = $this->contadores[$card['status']] ?? 0;
            @endphp
            <div 
                wire:click="toggleFiltroSituacao('{{ $card['status'] }}')"
                class="flex-1 min-w-[130px] sm:min-w-[150px] max-w-[220px] cursor-pointer transition-all duration-200 rounded-xl p-3 flex flex-col border shadow-sm hover:shadow-md hover:-translate-y-0.5 {{ $isActive ? $card['activeClass'] : 'bg-white dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700' }}"
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
                        class="w-40 rounded-lg bg-white dark:bg-zinc-900 text-zinc-800 dark:text-zinc-200 px-2.5 py-1.5 text-xs font-semibold shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 border-t border-r border-b border-l-[5px] border-t-zinc-200 border-r-zinc-200 border-b-zinc-200 dark:border-t-zinc-700 dark:border-r-zinc-700 dark:border-b-zinc-700 {{ $ficha->tip_situacao?->badge()['border-l'] ?? 'border-l-zinc-200 dark:border-l-zinc-700' }}">
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


</div>
