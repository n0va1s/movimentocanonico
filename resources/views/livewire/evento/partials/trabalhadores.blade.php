<?php

use App\Models\Evento;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public Evento $evento;
    public string $search = '';
    public string $equipeFiltroId = '';

    // Armazena apenas o ID, não o Model inteiro,
    // evitando o erro "Undefined array key" causado pela
    // (de)serialização Livewire de objetos Eloquent como arrays.
    public ?int $trabalhadorSelecionadoId = null;

    public array $formAvaliacao = [
        'ind_recomendado'    => false,
        'ind_lideranca'      => false,
        'ind_destaque'       => false,
        'ind_camiseta_pediu' => false,
        'ind_camiseta_pagou' => false,
        'ind_taxa_pagou'     => false,
    ];

    public ?int $alterarEquipeTrabalhadorId = null;
    public ?int $alterarEquipeId = null;
    public bool $alterarIndCoordenador = false;
    public bool $alterarIndPrimeiraVez = false;

    public function mount(Evento $evento): void
    {
        $this->evento = $evento;
    }

    public function abrirAvaliacao(int $idtTrabalhador): void
    {
        $trabalhador = \App\Models\Trabalhador::findOrFail($idtTrabalhador);

        // Guarda apenas o ID — seguro para serialização Livewire
        $this->trabalhadorSelecionadoId = $trabalhador->idt_trabalhador;

        $this->formAvaliacao = [
            'ind_recomendado'    => (bool) $trabalhador->ind_recomendado,
            'ind_lideranca'      => (bool) $trabalhador->ind_lideranca,
            'ind_destaque'       => (bool) $trabalhador->ind_destaque,
            'ind_camiseta_pediu' => (bool) $trabalhador->ind_camiseta_pediu,
            'ind_camiseta_pagou' => (bool) $trabalhador->ind_camiseta_pagou,
            'ind_taxa_pagou'     => (bool) $trabalhador->ind_taxa_pagou,
        ];

        $this->modal('avaliar-trabalhador')->show();
    }

    public function salvarAvaliacao(): void
    {
        // Usa o ID salvo — não depende mais de array key no objeto serializado
        $trabalhador = \App\Models\Trabalhador::findOrFail($this->trabalhadorSelecionadoId);

        $trabalhador->update(array_merge($this->formAvaliacao, [
            'ind_avaliacao' => true,
        ]));

        $this->modal('avaliar-trabalhador')->close();
        $this->trabalhadorSelecionadoId = null;

        $this->dispatch('notify', message: 'Avaliação de ' . $trabalhador->pessoa->nom_pessoa . ' atualizada!');
    }

    public function abrirAlterarEquipe(int $idtTrabalhador): void
    {
        $trabalhador = \App\Models\Trabalhador::findOrFail($idtTrabalhador);
        $this->alterarEquipeTrabalhadorId = $trabalhador->idt_trabalhador;
        $this->alterarEquipeId = $trabalhador->idt_equipe;
        $this->alterarIndCoordenador = (bool) $trabalhador->ind_coordenador;
        $this->alterarIndPrimeiraVez = (bool) $trabalhador->ind_primeira_vez;

        $this->modal('alterar-equipe-trabalhador')->show();
    }

    public function salvarAlterarEquipe(): void
    {
        $trabalhador = \App\Models\Trabalhador::findOrFail($this->alterarEquipeTrabalhadorId);

        $trabalhador->update([
            'idt_equipe' => $this->alterarEquipeId,
            'ind_coordenador' => $this->alterarIndCoordenador,
            'ind_primeira_vez' => $this->alterarIndPrimeiraVez,
        ]);

        $this->modal('alterar-equipe-trabalhador')->close();
        $this->alterarEquipeTrabalhadorId = null;
        $this->alterarEquipeId = null;
        $this->alterarIndCoordenador = false;
        $this->alterarIndPrimeiraVez = false;

        $this->dispatch('notify', message: 'Alocação de ' . $trabalhador->pessoa->nom_pessoa . ' atualizada!');
    }

    public function removerTrabalhador(int $idtTrabalhador): void
    {
        $trabalhador = \App\Models\Trabalhador::findOrFail($idtTrabalhador);

        \Illuminate\Support\Facades\DB::transaction(function () use ($trabalhador) {
            \App\Models\Voluntario::where('idt_trabalhador', $trabalhador->idt_trabalhador)
                ->update(['idt_trabalhador' => null]);

            $trabalhador->delete();
        });

        $this->dispatch('notify', message: 'Trabalhador removido e retornado para triagem.');
    }

    public function with(): array
    {
        return [
            // Apenas trabalhadores PENDENTES de avaliação aparecem aqui.
            // Os já avaliados (ind_avaliacao = true) vão para a aba Quadrante.
            'trabalhadores' => \App\Models\Trabalhador::query()
                ->select('trabalhador.*')
                ->join('pessoa', 'trabalhador.idt_pessoa', '=', 'pessoa.idt_pessoa')
                ->where('trabalhador.idt_evento', $this->evento->idt_evento)
                ->where('trabalhador.ind_avaliacao', false)
                ->with(['pessoa', 'equipe'])
                ->when($this->equipeFiltroId, function ($query) {
                    $query->where('trabalhador.idt_equipe', $this->equipeFiltroId);
                })
                ->when($this->search, function ($query) {
                    $query->where(function ($q) {
                        $q->where('pessoa.nom_pessoa', 'like', '%' . $this->search . '%')
                            ->orWhere('pessoa.nom_apelido', 'like', '%' . $this->search . '%');
                    });
                })
                ->orderBy('pessoa.nom_pessoa', 'asc')
                ->paginate(10),
            'equipes' => \App\Models\TipoEquipe::where('idt_movimento', $this->evento->movimento->idt_movimento)->get(),
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
        <div>
            <flux:heading size="lg">Equipe de Trabalho</flux:heading>
            <flux:subheading>Trabalhadores pendentes de avaliação. Os já avaliados aparecem no Quadrante.</flux:subheading>
        </div>

        <div class="flex gap-2">
            <flux:button href="{{ route('eventos.casais-visitacao', $evento) }}" icon="user-group" variant="filled" color="indigo" class="cursor-pointer">
                Casais de Visitação
            </flux:button>
        </div>

        <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto sm:items-end">
            <flux:select label="Equipe" wire:model.live="equipeFiltroId" icon="users" placeholder="Todas as equipes" class="w-full sm:w-48">
                <option value="">Todas as equipes</option>
                @foreach ($equipes as $equipe)
                    <option value="{{ $equipe->idt_equipe }}">{{ $equipe->des_grupo }}</option>
                @endforeach
            </flux:select>

            <flux:input
                label="Busca"
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="Buscar trabalhador..."
                class="w-full sm:w-64"
            />
        </div>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>Pessoa</flux:table.column>
            <flux:table.column>Telefone</flux:table.column>
            <flux:table.column>Equipe</flux:table.column>
            <flux:table.column>Menor de Idade</flux:table.column>
            <flux:table.column align="end">Ações</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($trabalhadores as $trabalhador)
                @php
                    $pessoa = $trabalhador->pessoa;
                    $menor  = $pessoa->dat_nascimento && \Carbon\Carbon::parse($pessoa->dat_nascimento)->age < 18;
                @endphp
                <flux:table.row :key="'trabalhador-'.$trabalhador->idt_trabalhador">

                    {{-- Pessoa --}}
                    <flux:table.cell>
                        <div class="flex items-center gap-3">
                            <flux:avatar
                                :initials="mb_substr($pessoa->nom_pessoa ?? '??', 0, 2)"
                                size="sm"
                            />
                            <div>
                                <div class="font-medium text-zinc-900 dark:text-white flex items-center gap-2">
                                    <span>{{ $pessoa->nom_pessoa }}</span>
                                    @if ($trabalhador->ind_coordenador)
                                        <flux:icon.star variant="solid" class="size-4 text-yellow-500 shrink-0 cursor-default" title="Coordenador da Equipe" />
                                    @endif
                                </div>
                                <div class="text-xs text-zinc-500">{{ $pessoa->nom_apelido }}</div>
                            </div>
                        </div>
                    </flux:table.cell>

                    {{-- Telefone --}}
                    <flux:table.cell>
                        <flux:text size="sm">{{ $pessoa->tel_pessoa ?? '—' }}</flux:text>
                    </flux:table.cell>

                    {{-- Equipe --}}
                    <flux:table.cell>
                        <flux:badge size="sm" color="blue">{{ $trabalhador->equipe->des_grupo }}</flux:badge>
                    </flux:table.cell>

                    {{-- Menor de Idade --}}
                    <flux:table.cell>
                        @if($menor)
                            <flux:badge size="sm" color="yellow">Sim</flux:badge>
                        @else
                            <flux:badge size="sm" color="zinc">Não</flux:badge>
                        @endif
                    </flux:table.cell>

                    {{-- Ações --}}
                    <flux:table.cell>
                        <div class="flex justify-end gap-2">
                            <flux:button
                                icon="pencil-square"
                                size="sm"
                                variant="ghost"
                                wire:click="abrirAlterarEquipe({{ $trabalhador->idt_trabalhador }})"
                                tooltip="Alterar Alocação"
                            />
                            <flux:button
                                icon="clipboard-document-check"
                                size="sm"
                                variant="ghost"
                                wire:click="abrirAvaliacao({{ $trabalhador->idt_trabalhador }})"
                                tooltip="Avaliar"
                            />
                            <flux:button
                                icon="trash"
                                size="sm"
                                variant="ghost"
                                color="red"
                                wire:click="removerTrabalhador({{ $trabalhador->idt_trabalhador }})"
                                tooltip="Remover"
                            />
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5" class="text-center py-10 text-zinc-500">
                        Nenhum trabalhador pendente de avaliação.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <div class="mt-4">
        {{ $trabalhadores->links(data: ['scrollTo' => false]) }}
    </div>

    {{-- Modal de Avaliação --}}
    <flux:modal name="avaliar-trabalhador" class="min-w-[20rem] md:min-w-[30rem]">
        <form wire:submit="salvarAvaliacao" class="space-y-6">
            <div>
                <flux:heading size="lg">Avaliação de Desempenho</flux:heading>
                <flux:subheading>Gestão de indicadores e pagamentos para este evento.</flux:subheading>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-4">
                    <flux:text weight="bold" size="sm" class="uppercase">Perfil e Liderança</flux:text>
                    <flux:checkbox wire:model="formAvaliacao.ind_recomendado" label="Recomenda trabalhar novamente?" />
                    <flux:checkbox wire:model="formAvaliacao.ind_lideranca"   label="Potencial para liderança futura?" />
                    <flux:checkbox wire:model="formAvaliacao.ind_destaque"    label="Indicar para Coordenação Geral?" />
                </div>

                <div class="space-y-4">
                    <flux:text weight="bold" size="sm" class="uppercase">Financeiro / Logística</flux:text>
                    <flux:checkbox wire:model="formAvaliacao.ind_camiseta_pediu" label="Pediu Camiseta" />
                    <flux:checkbox wire:model="formAvaliacao.ind_camiseta_pagou" label="Pagou Camiseta" />
                    <flux:checkbox wire:model="formAvaliacao.ind_taxa_pagou"     label="Pagou Taxa de Inscrição" />
                </div>
            </div>

            <div class="flex gap-2 justify-end">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Salvar Avaliação</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Modal de Alteração de Equipe e Função --}}
    <flux:modal name="alterar-equipe-trabalhador" class="min-w-[20rem] md:min-w-[25rem]">
        <form wire:submit="salvarAlterarEquipe" class="space-y-6">
            <div>
                <flux:heading size="lg">Alterar Equipe / Função</flux:heading>
                <flux:subheading>Selecione a nova equipe e atualize as funções do trabalhador.</flux:subheading>
            </div>

            <div class="space-y-4">
                <flux:select label="Nova Equipe" wire:model="alterarEquipeId" placeholder="Selecione a equipe..." required>
                    @foreach ($equipes as $equipe)
                        <option value="{{ $equipe->idt_equipe }}">{{ $equipe->des_grupo }}</option>
                    @endforeach
                </flux:select>

                <div class="space-y-3">
                    <flux:checkbox wire:model="alterarIndCoordenador" label="Coordenador da Equipe" />
                    <flux:checkbox wire:model="alterarIndPrimeiraVez" label="Primeira vez trabalhando no evento" />
                </div>
            </div>

            <div class="flex gap-2 justify-end">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Salvar Alterações</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
