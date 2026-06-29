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
        $eventoId = $this->evento->idt_evento;

        $rows = DB::select("
            SELECT
                f.idt_ficha,
                f.tip_genero,
                f.nom_candidato,
                f.nom_apelido,
                f.dat_nascimento,
                f.tel_candidato,
                f.eml_candidato,
                f.des_endereco,
                f.tam_camiseta,
                f.tip_como_soube,
                f.ind_restricao,
                fv.des_onde_estuda,
                fv.des_mora_quem,
                r.des_responsavel AS falar_com,
                fv.nom_pai,
                fv.tel_pai,
                fv.nom_mae,
                fv.tel_mae,
                f.ind_catolico,
                fv.ind_batizado,
                fv.ind_primeira_comunhao,
                fv.ind_crismado,
                fv.nom_paroquia,
                tr.des_restricao,
                s.txt_complemento
            FROM ficha f
            INNER JOIN ficha_vem fv ON f.idt_ficha = fv.idt_ficha
            LEFT JOIN ficha_saude s ON f.idt_ficha = s.idt_ficha
            LEFT JOIN tipo_responsavel r ON fv.idt_falar_com = r.idt_responsavel
            LEFT JOIN tipo_restricao tr ON s.idt_restricao = tr.idt_restricao
            WHERE f.deleted_at IS NULL
              AND f.idt_evento = ?
        ", [$eventoId]);

        $cabecalho = [
            'ID',
            'Gênero',
            'Nome',
            'Apelido',
            'Data de Nascimento',
            'Telefone',
            'E-mail',
            'Endereço',
            'Tamanho Camiseta',
            'Como Soube',
            'Possui Restrição',
            'Onde Estuda',
            'Mora com Quem',
            'Falar Com',
            'Nome do Pai',
            'Telefone do Pai',
            'Nome da Mãe',
            'Telefone da Mãe',
            'Católico',
            'Batizado',
            'Primeira Communion',
            'Crismado',
            'Paróquia',
            'Tipo de Restrição',
            'Complemento Restrição',
        ];

        $nomeArquivo = 'fichas_' . \Str::slug($this->evento->nom_evento ?? 'evento') . '_' . now()->format('Y-m-d') . '.csv';

        $response = new StreamedResponse(function () use ($rows, $cabecalho) {
            $handle = fopen('php://output', 'w');

            // BOM para o Excel reconhecer UTF-8 corretamente
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, $cabecalho, ';');

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->idt_ficha,
                    $row->tip_genero,
                    $row->nom_candidato,
                    $row->nom_apelido,
                    $row->dat_nascimento,
                    $row->tel_candidato,
                    $row->eml_candidato,
                    $row->des_endereco,
                    $row->tam_camiseta,
                    $row->tip_como_soube,
                    $row->ind_restricao ? 'Sim' : 'Não',
                    $row->des_onde_estuda,
                    $row->des_mora_quem,
                    $row->falar_com,
                    $row->nom_pai,
                    $row->tel_pai,
                    $row->nom_mae,
                    $row->tel_mae,
                    $row->ind_catolico ? 'Sim' : 'Não',
                    $row->ind_batizado ? 'Sim' : 'Não',
                    $row->ind_primeira_comunhao ? 'Sim' : 'Não',
                    $row->ind_crismado ? 'Sim' : 'Não',
                    $row->nom_paroquia,
                    $row->des_restricao,
                    $row->txt_complemento,
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
                ->with(['evento', 'visitador']) // necessário para rotas e visitador
                ->when($this->filtroSituacao, function ($query) {
                    $query->where('tip_situacao', $this->filtroSituacao);
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
                <flux:button wire:click="exportar" icon="arrow-down-tray" variant="outline" size="sm" title="Exportar CSV">
                    Exportar
                </flux:button>
            </div>
            <flux:subheading>Analise e aprove os candidatos para este evento.</flux:subheading>
        </div>

        <div class="w-full md:w-auto flex flex-col md:flex-row items-center gap-3">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Buscar ficha..."
                class="w-full md:max-w-xs" />
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

            <flux:table.column>Candidato</flux:table.column>
            <flux:table.column>Data Nasc</flux:table.column>
            <flux:table.column>Endereço</flux:table.column>
            <flux:table.column>Situação</flux:table.column>
            <flux:table.column align="end">Ações</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($fichas as $ficha)
            <flux:table.row :key="'ficha-'.$ficha->idt_ficha">

                <flux:table.cell>
                    <div class="font-medium text-zinc-900 dark:text-white">{{ $ficha->nom_candidato }}</div>
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

                <flux:table.cell>
                    <div class="text-sm text-zinc-600 dark:text-zinc-400 max-w-xs truncate" title="{{ $ficha->des_endereco }}">
                        {{ $ficha->des_endereco ?: '—' }}
                    </div>
                </flux:table.cell>

                <flux:table.cell>
                    <flux:select
                        wire:change="atualizarSituacao({{ $ficha->idt_ficha }}, $event.target.value)"
                        size="sm"
                        class="w-40">
                        @foreach (\App\Enums\TipoSituacao::cases() as $situacao)
                            <option value="{{ $situacao->value }}" @selected($ficha->tip_situacao === $situacao)>
                                {{ $situacao->label() }}
                            </option>
                        @endforeach
                    </flux:select>
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
