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
    public bool $casalDesignado = false;

    public function mount(Evento $evento): void
    {
        $this->evento = $evento;
    }

    // Reseta a paginação quando a busca muda
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    // Reseta a paginação quando o filtro muda
    public function updatedCasalDesignado(): void
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

    public function with(): array
    {
        return [
            'fichas' => \App\Models\Ficha::where('idt_evento', $this->evento->idt_evento)
                ->with(['evento', 'fichaEcc']) // necessário para rotasPorMovimento()
                ->when($this->search, function ($query) {
                    $query->where(function ($q) {
                        $q->where('nom_candidato', 'like', '%' . $this->search . '%')
                          ->orWhere('nom_apelido', 'like', '%' . $this->search . '%');
                    });
                })
                ->when(Auth::user()->isAdmin() && $this->casalDesignado, function ($query) {
                    $query->whereHas('fichaEcc')
                          ->whereNotNull('idt_pessoa_visitacao');
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

        <div class="w-full md:w-auto flex items-center gap-4">
            @if (Auth::user()->isAdmin())
                <flux:checkbox wire:model.live="casalDesignado" label="Apenas Casal Designado" />
            @endif
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Buscar ficha..."
                class="w-full md:max-w-xs" />
        </div>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>Candidato</flux:table.column>
            <flux:table.column>Data Nasc</flux:table.column>
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
