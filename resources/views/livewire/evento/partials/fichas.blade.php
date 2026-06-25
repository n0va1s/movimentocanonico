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
    public array $selectedFichas = [];
    public ?int $pessoaVisitacaoId = null;
    public bool $selectAll = false;

    public function mount(Evento $evento): void
    {
        $this->evento = $evento;
    }

    // Reseta a paginação quando a busca muda
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSelectAll($value): void
    {
        if ($value) {
            $this->selectedFichas = \App\Models\Ficha::where('idt_evento', $this->evento->idt_evento)
                ->when($this->search, function ($query) {
                    $query->where('nom_candidato', 'like', '%' . $this->search . '%')
                        ->orWhere('nom_apelido', 'like', '%' . $this->search . '%');
                })
                ->pluck('idt_ficha')
                ->map(fn($id) => (string)$id)
                ->toArray();
        } else {
            $this->selectedFichas = [];
        }
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
        $this->selectAll = false;

        $this->dispatch('notify',
            message: "Visitação designada com sucesso e fichas marcadas como Selecionada.",
            type: 'sucesso'
        );
    }

    private function getVisitadores()
    {
        $visitadoresRaw = \App\Models\Pessoa::whereHas('usuario', function ($q) {
            $q->where('role', \App\Models\User::ROLE_VISITACAO);
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
                ->with(['evento', 'visitador']) // necessário para rotas e visitador
                ->when($this->search, function ($query) {
                    $query->where('nom_candidato', 'like', '%' . $this->search . '%')
                        ->orWhere('nom_apelido', 'like', '%' . $this->search . '%');
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
                <flux:button wire:click="exportar" icon="arrow-down-tray" variant="outline" size="sm" title="Exportar CSV">
                    Exportar
                </flux:button>
            </div>
            <flux:subheading>Analise e aprove os candidatos para este evento.</flux:subheading>
        </div>

        <div class="w-full md:w-auto flex flex-col md:flex-row items-center gap-3">
            @if(count($selectedFichas) > 0)
                <flux:button wire:click="abrirModalVisitacao" icon="user-group" variant="primary" class="w-full md:w-auto">
                    Designar Visitação ({{ count($selectedFichas) }})
                </flux:button>
            @endif
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Buscar ficha..."
                class="w-full md:max-w-xs" />
        </div>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-10">
                <flux:checkbox wire:model.live="selectAll" />
            </flux:table.column>
            <flux:table.column>Candidato</flux:table.column>
            <flux:table.column>Data Nasc</flux:table.column>
            <flux:table.column>Situação</flux:table.column>
            <flux:table.column align="end">Ações</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($fichas as $ficha)
            <flux:table.row :key="'ficha-'.$ficha->idt_ficha">
                <flux:table.cell>
                    <flux:checkbox wire:model.live="selectedFichas" value="{{ $ficha->idt_ficha }}" />
                </flux:table.cell>
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
                <flux:table.cell colspan="5" class="text-center py-12">
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
        <form wire:submit="designarVisitacao" class="space-y-6">
            <div>
                <flux:heading size="lg">Designar Visitação</flux:heading>
                <flux:subheading>Selecione o visitador (ou casal) para as {{ count($selectedFichas) }} ficha(s) selecionada(s).</flux:subheading>
            </div>

            <div>
                <flux:select wire:model="pessoaVisitacaoId" label="Visitador(es)" placeholder="Selecione um visitador...">
                    @foreach ($visitadores as $visitador)
                        <option value="{{ $visitador->idt_pessoa }}">
                            {{ $visitador->nom_pessoa }}
                            @if ($visitador->parceiro)
                                e {{ $visitador->parceiro->nom_pessoa }}
                            @endif
                        </option>
                    @endforeach
                </flux:select>
                @error('pessoaVisitacaoId') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
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
