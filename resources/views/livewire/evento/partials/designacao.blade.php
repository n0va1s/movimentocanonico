<?php

use App\Models\Evento;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public Evento $evento;
    public string $search = '';
    public string $visitadorFiltro = '';

    public function mount(Evento $evento): void
    {
        $this->evento = $evento;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedVisitadorFiltro(): void
    {
        $this->resetPage();
    }

    /**
     * Resolve as rotas de show, edit de acordo com o movimento do evento.
     */
    private function rotasPorMovimento(\App\Models\Ficha $ficha): array
    {
        return match ((int) $ficha->evento->idt_movimento) {
            \App\Models\TipoMovimento::VEM => ['show' => 'vem.show', 'edit' => 'vem.edit'],
            \App\Models\TipoMovimento::SGM => ['show' => 'sgm.show', 'edit' => 'sgm.edit'],
            \App\Models\TipoMovimento::ECC => ['show' => 'ecc.show', 'edit' => 'ecc.edit'],
            default => ['show' => 'vem.show', 'edit' => 'vem.edit'],
        };
    }

    public function atualizarVisitador(int $fichaId, ?string $visitadorId): void
    {
        try {
            $ficha = \App\Models\Ficha::findOrFail($fichaId);
            $visitadorIdVal = empty($visitadorId) ? null : (int) $visitadorId;

            $ficha->update([
                'idt_pessoa_visitacao' => $visitadorIdVal,
            ]);

            if ($visitadorIdVal) {
                $visitador = \App\Models\Pessoa::with('parceiro')->find($visitadorIdVal);
                $nomeLabel = $visitador->nom_pessoa;
                if ($visitador->parceiro) {
                    $nomeLabel .= ' & ' . $visitador->parceiro->nom_pessoa;
                }
                $msg = "A visitação de {$ficha->nom_apelido} foi atribuída a {$nomeLabel}.";
            } else {
                $msg = "A visitação de {$ficha->nom_apelido} está sem responsável designado.";
            }

            $this->dispatch('notify',
                message: $msg,
                type: 'sucesso'
            );
        } catch (\Exception $e) {
            $this->dispatch('notify',
                message: 'Erro ao designar visitador: ' . $e->getMessage(),
                type: 'erro'
            );
        }
    }

    public function with(): array
    {
        $visitadoresRaw = \App\Models\Pessoa::whereHas('usuario', function ($q) {
            $q->where('role', \App\Models\User::ROLE_VISITACAO);
        })
        ->with('parceiro')
        ->orderBy('nom_pessoa', 'asc')
        ->get();

        // De-duplicar casais
        $processed = [];
        $visitadores = $visitadoresRaw->reject(function ($v) use (&$processed) {
            if (in_array($v->idt_pessoa, $processed)) {
                return true;
            }
            if ($v->idt_parceiro) {
                $processed[] = $v->idt_parceiro;
            }
            return false;
        });

        $fichasQuery = \App\Models\Ficha::where('idt_evento', $this->evento->idt_evento)
            ->whereIn('tip_situacao', [
                \App\Enums\TipoSituacao::SELECIONADA,
                \App\Enums\TipoSituacao::CONTATO,
                \App\Enums\TipoSituacao::AGUARDANDO,
                \App\Enums\TipoSituacao::VISITADA,
                \App\Enums\TipoSituacao::CANCELADA,
            ])
            ->with(['evento'])
            ->when($this->visitadorFiltro, function ($query) {
                if ($this->visitadorFiltro === 'sem') {
                    return $query->whereNull('idt_pessoa_visitacao');
                }

                $visitadorId = (int) $this->visitadorFiltro;
                $v = \App\Models\Pessoa::find($visitadorId);
                if ($v && $v->idt_parceiro) {
                    return $query->whereIn('idt_pessoa_visitacao', [$visitadorId, $v->idt_parceiro]);
                }

                return $query->where('idt_pessoa_visitacao', $visitadorId);
            })
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('nom_candidato', 'like', '%' . $this->search . '%')
                        ->orWhere('nom_apelido', 'like', '%' . $this->search . '%');
                });
            });

        return [
            'fichas' => $fichasQuery->paginate(10),
            'visitadores' => $visitadores,
        ];
    }
}; ?>

<div class="space-y-4">
    <div>
        <flux:heading size="lg">Designação e Acompanhamento de Visitas</flux:heading>
        <flux:subheading>Atribua os visitadores e acompanhe a situação do contato das fichas selecionadas para o evento.</flux:subheading>
    </div>

    {{-- Filtros e Busca --}}
    <div class="flex flex-col sm:flex-row gap-4 bg-zinc-50 dark:bg-zinc-900/50 p-4 rounded-xl border border-zinc-200 dark:border-zinc-700 items-center justify-between">
        <div class="w-full sm:w-72">
            <flux:select wire:model.live="visitadorFiltro" placeholder="Filtrar por Visitador" size="sm">
                <option value="">Todos os Visitadores</option>
                <option value="sem">Sem responsável designado</option>
                @foreach ($visitadores as $v)
                    @php
                        $nomeLabel = $v->nom_pessoa;
                        if ($v->parceiro) {
                            $nomeLabel .= ' & ' . $v->parceiro->nom_pessoa;
                        }
                    @endphp
                    <option value="{{ $v->idt_pessoa }}">{{ $nomeLabel }}</option>
                @endforeach
            </flux:select>
        </div>

        <div class="w-full sm:w-72">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Buscar candidato..." size="sm" />
        </div>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>Candidato</flux:table.column>
            <flux:table.column>Endereço do Candidato</flux:table.column>
            <flux:table.column>Responsável pela Visitação (Casal / Visitador)</flux:table.column>
            <flux:table.column>Situação</flux:table.column>
            <flux:table.column align="end">Ações</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($fichas as $ficha)
            <flux:table.row :key="'designar-ficha-'.$ficha->idt_ficha">
                {{-- Candidato --}}
                <flux:table.cell>
                    <div class="font-medium text-zinc-900 dark:text-white">{{ $ficha->nom_candidato }}</div>
                    <div class="text-xs text-zinc-500">
                        {{ $ficha->nom_apelido ?: 'Sem apelido' }}
                        @if($ficha->dat_nascimento)
                            <span class="text-zinc-400"> • {{ \Carbon\Carbon::parse($ficha->dat_nascimento)->age }} anos</span>
                        @endif
                    </div>
                </flux:table.cell>

                {{-- Endereço do Candidato --}}
                <flux:table.cell>
                    <div class="text-sm text-zinc-700 dark:text-zinc-300 max-w-sm truncate" title="{{ $ficha->des_endereco }}">
                        {{ $ficha->des_endereco ?: 'Endereço não informado' }}
                    </div>
                </flux:table.cell>

                {{-- Visitador Dropdown --}}
                <flux:table.cell>
                    <div class="w-full max-w-md">
                        <flux:select
                            wire:change="atualizarVisitador({{ $ficha->idt_ficha }}, $event.target.value)"
                            size="sm"
                            placeholder="Sem responsável designado">
                            <option value="">Sem responsável designado</option>
                            @foreach ($visitadores as $v)
                                @php
                                    $nomeLabel = $v->nom_pessoa;
                                    if ($v->parceiro) {
                                        $nomeLabel .= ' & ' . $v->parceiro->nom_pessoa;
                                    }
                                    if ($v->des_endereco) {
                                        $nomeLabel .= ' — ' . $v->des_endereco;
                                    }
                                @endphp
                                <option value="{{ $v->idt_pessoa }}" @selected($ficha->idt_pessoa_visitacao == $v->idt_pessoa || ($v->idt_parceiro && $ficha->idt_pessoa_visitacao == $v->idt_parceiro))>
                                    {{ $nomeLabel }}
                                </option>
                            @endforeach
                        </flux:select>
                    </div>
                </flux:table.cell>

                {{-- Situação --}}
                <flux:table.cell>
                    @php
                        $style = $ficha->tip_situacao->badge();
                    @endphp
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-2xs font-bold {{ $style['light'] }} border">
                        {{ $ficha->tip_situacao->label() }}
                    </span>
                </flux:table.cell>

                {{-- Ações --}}
                <flux:table.cell align="end">
                    <div class="flex justify-end gap-2">
                        @php
                            $rotas = $this->rotasPorMovimento($ficha);
                        @endphp

                        <flux:button
                            variant="ghost"
                            size="sm"
                            icon="eye"
                            href="{{ route($rotas['show'], $ficha) }}"
                            title="Ver Detalhes"
                        />
                        <flux:button
                            variant="ghost"
                            size="sm"
                            icon="pencil-square"
                            href="{{ route($rotas['edit'], $ficha) }}"
                            title="Editar Ficha"
                        />
                    </div>
                </flux:table.cell>
            </flux:table.row>
            @empty
            <flux:table.row>
                <flux:table.cell colspan="5" class="text-center py-12">
                    <div class="flex flex-col items-center">
                        <x-heroicon-o-document-magnifying-glass class="w-12 h-12 text-zinc-300 mb-2" />
                        <flux:text>Nenhuma ficha de visitação encontrada.</flux:text>
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
