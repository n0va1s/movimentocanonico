<?php

use App\Models\Evento;
use Livewire\Volt\Component;
use Livewire\WithPagination;
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

    public function mount(Evento $evento): void
    {
        $this->evento = $evento;
    }

    // Reseta a paginação quando a busca muda
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    // Reseta a paginação quando a situação muda
    public function updatedSituacao(): void
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

    /**
     * Retorna a contagem de fichas agrupada por situação para o evento.
     */
    public function getQuantidadePorSituacao(): array
    {
        return \App\Models\Ficha::where('idt_evento', $this->evento->idt_evento)
            ->select('tip_situacao', DB::raw('count(*) as total'))
            ->groupBy('tip_situacao')
            ->pluck('total', 'tip_situacao')
            ->toArray();
    }

    public function with(): array
    {
        return [
            'fichas' => \App\Models\Ficha::where('idt_evento', $this->evento->idt_evento)
                ->with(['evento', 'fichaVem', 'fichaEcc', 'fichaSGM']) // necessário para rotasPorMovimento() e vínculos
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
                ->paginate(10),
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

        @php
            $quantidades = $this->getQuantidadePorSituacao();
            $totalFichas = array_sum($quantidades);
        @endphp

        <div class="flex flex-col sm:flex-row gap-2 w-full md:w-auto items-end">
            <flux:select wire:model.live="situacao" icon="funnel" placeholder="Todas as situações" class="w-full sm:w-44">
                <option value="">Todas as situações ({{ $totalFichas }})</option>
                @foreach (\App\Enums\TipoSituacao::cases() as $sit)
                    @php
                        $qtd = $quantidades[$sit->value] ?? 0;
                    @endphp
                    <option value="{{ $sit->value }}">{{ $sit->label() }} ({{ $qtd }})</option>
                @endforeach
            </flux:select>

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
                <flux:table.cell colspan="6" class="text-center py-12">
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
