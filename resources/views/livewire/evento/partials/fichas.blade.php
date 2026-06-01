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

    public function mount(Evento $evento): void
    {
        $this->evento = $evento;
    }

    // Reseta a paginação quando a busca muda
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function atualizarAprovacao(int $fichaId): void
    {
        try {
            $ficha = \App\Services\FichaService::atualizarAprovacaoFicha($fichaId);

            $status = $ficha->ind_aprovado ? 'aprovada' : 'pendente';
            $visual = $ficha->ind_aprovado ? 'sucesso' : 'info';

            $this->dispatch('notify',
                message: "A ficha de {$ficha->nom_apelido} foi {$status}.",
                type: $visual
            );
        } catch (\RuntimeException $e) {
            // CPF ausente ou outra regra de negócio impediu a aprovação
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
            \App\Models\TipoMovimento::SegueMe => ['show' => 'sgm.show',  'edit' => 'sgm.edit',  'destroy' => 'sgm.destroy'],
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
            'Primeira Comunhão',
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

    public function with(): array
    {
        return [
            'fichas' => \App\Models\Ficha::where('idt_evento', $this->evento->idt_evento)
                ->with('evento') // necessário para rotasPorMovimento()
                ->when($this->search, function ($query) {
                    $query->where('nom_candidato', 'like', '%' . $this->search . '%')
                        ->orWhere('nom_apelido', 'like', '%' . $this->search . '%');
                })
                ->paginate(10),
        ];
    }
}; ?>

<div class="space-y-4">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <flux:heading size="lg">Fichas de Inscrição</flux:heading>
            <flux:subheading>Analise e aprove os candidatos para este evento.</flux:subheading>
        </div>

        <div class="flex items-center gap-2 w-full md:w-auto">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Buscar ficha..."
                class="w-full md:max-w-xs" />

            <flux:button wire:click="exportar" icon="arrow-down-tray" variant="outline" size="sm" title="Exportar CSV">
                Exportar
            </flux:button>
        </div>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>Candidato</flux:table.column>
            <flux:table.column>Data Nasc</flux:table.column>
            <flux:table.column>Aprovado</flux:table.column>
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
                    <flux:switch wire:click="atualizarAprovacao({{ $ficha->idt_ficha }})"
                        :checked="$ficha->ind_aprovado" color="green" />
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
                <flux:table.cell colspan="4" class="text-center py-12">
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
