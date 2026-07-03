<?php

use App\Models\Pessoa;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Illuminate\Support\Facades\Log;

new #[Title('Gerenciar Pessoas')] class extends Component {
    use WithPagination;

    #[Url(history: true)]
    public string $search = '';

    public function mount(): void
    {
        if (!auth()->check() || !auth()->user()->isAdmin()) {
            abort(403, 'Acesso não autorizado.');
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function excluirPessoa(int $id): void
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Acesso não autorizado.');
        }

        try {
            $pessoa = Pessoa::findOrFail($id);
            $nome = $pessoa->nom_pessoa;
            $pessoa->delete();
            $this->dispatch('notify', message: "Pessoa {$nome} excluída com sucesso!");
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() === '23000') {
                $this->dispatch('notify', message: 'Não é possível excluir esta pessoa. É preciso apagar os dados associados.', type: 'error');
            } else {
                $this->dispatch('notify', message: 'Erro ao tentar excluir a pessoa.', type: 'error');
            }
        }
    }

    public function with(): array
    {
        $start = microtime(true);
        $context = [
            'user_id' => auth()->id(),
            'search_term' => $this->search,
        ];

        Log::info('Requisição de listagem de pessoas iniciada via Volt', $context);

        $pessoas = Pessoa::select(
            'idt_pessoa',
            'idt_usuario',
            'idt_parceiro',
            'nom_pessoa',
            'nom_apelido',
            'tel_pessoa',
            'eml_pessoa',
            'tip_estado_civil',
            'tip_habilidade',
            'created_at'
        )
            ->with([
                'foto:idt_pessoa,med_foto',
            ])
            ->when($this->search, function ($query, $search) {
                return $query->searchByName($search);
            })
            ->orderBy('nom_pessoa')
            ->paginate(10);

        $duration = round((microtime(true) - $start) * 1000, 2);
        Log::notice('Listagem de pessoas concluída com sucesso via Volt', array_merge($context, [
            'total_pessoas' => $pessoas->total(),
            'duration_ms' => $duration,
        ]));

        return [
            'pessoas' => $pessoas,
        ];
    }
}; ?>

<div class="space-y-6 w-full max-w-7xl mx-auto p-4 md:p-8">
    {{-- Alerts --}}

    {{-- Header --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 mb-6">
        <div>
            <flux:heading size="xl" class="flex items-center gap-2">
                <flux:icon.user-group class="size-6 text-zinc-500" />
                Lista de Pessoas
            </flux:heading>
            <flux:subheading>Visualize e gerencie os dados básicos dos participantes ou trabalhadores do sistema.</flux:subheading>
        </div>

        <flux:button href="{{ route('pessoas.create') }}" variant="primary" icon="plus" class="font-bold">
            Nova Pessoa
        </flux:button>
    </div>

    {{-- Filtros --}}
    <div class="flex flex-col sm:flex-row gap-4 bg-zinc-50 dark:bg-zinc-900 p-4 rounded-xl border border-zinc-200 dark:border-zinc-700 items-center justify-between">
        <div class="w-full sm:w-96">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar por nome ou apelido..." icon="magnifying-glass" />
        </div>
    </div>

    {{-- Tabela de Pessoas --}}
    @if ($pessoas->isNotEmpty())
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Foto</flux:table.column>
                <flux:table.column>Nome</flux:table.column>
                <flux:table.column>Apelido</flux:table.column>
                <flux:table.column>Telefone</flux:table.column>
                <flux:table.column>Casal</flux:table.column>
                <flux:table.column align="end">Ações</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($pessoas as $pessoa)
                    <flux:table.row :key="'pessoa-'.$pessoa->idt_pessoa">
                        {{-- Foto --}}
                        <flux:table.cell>
                            @if ($pessoa->foto && $pessoa->foto->med_foto)
                                <img src="{{ asset('storage/' . $pessoa->foto->med_foto) }}"
                                    alt="Foto de {{ $pessoa->nom_pessoa }}"
                                    class="w-10 h-10 rounded-full object-cover border border-zinc-200 dark:border-zinc-700 shadow-sm">
                            @else
                                <flux:avatar
                                    :initials="mb_substr($pessoa->nom_pessoa ?? '??', 0, 2)"
                                    size="sm"
                                />
                            @endif
                        </flux:table.cell>

                        {{-- Nome --}}
                        <flux:table.cell class="font-medium text-zinc-900 dark:text-white">
                            {{ $pessoa->nom_pessoa }}
                        </flux:table.cell>

                        {{-- Apelido --}}
                        <flux:table.cell>
                            {{ $pessoa->nom_apelido ?? '—' }}
                        </flux:table.cell>

                        {{-- Telefone --}}
                        <flux:table.cell>
                            {{ $pessoa->tel_pessoa ?? '—' }}
                        </flux:table.cell>

                        {{-- Estado Civil / Casal --}}
                        <flux:table.cell>
                            @if ($pessoa->tip_estado_civil)
                                <flux:badge size="sm" color="zinc">
                                    {{ $pessoa->tip_estado_civil->label() }}
                                </flux:badge>
                            @else
                                <flux:text size="sm" class="text-zinc-400">Não informado</flux:text>
                            @endif
                        </flux:table.cell>

                        {{-- Ações --}}
                        <flux:table.cell>
                            <div class="flex justify-end gap-2">
                                <flux:button
                                    href="{{ route('pessoas.edit', $pessoa) }}"
                                    icon="pencil-square"
                                    size="sm"
                                    variant="ghost"
                                    tooltip="Editar"
                                />
                                <flux:button
                                    icon="trash"
                                    size="sm"
                                    variant="ghost"
                                    color="red"
                                    wire:click="excluirPessoa({{ $pessoa->idt_pessoa }})"
                                    wire:confirm="Tem certeza que deseja excluir esta pessoa?"
                                    tooltip="Excluir"
                                />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>

        <div class="mt-6">
            {{ $pessoas->links(data: ['scrollTo' => false]) }}
        </div>
    @else
        <div class="flex flex-col items-center justify-center text-center p-12 bg-white dark:bg-zinc-800 rounded-xl border border-dashed border-zinc-300 dark:border-zinc-600 shadow-sm">
            <flux:icon.user-group class="w-12 h-12 text-zinc-400 dark:text-zinc-500 mb-4" />
            <flux:heading size="lg" class="text-zinc-700 dark:text-zinc-300">Nenhuma pessoa encontrada</flux:heading>
            <flux:subheading class="mt-1">
                Não existem pessoas cadastradas ou compatíveis com a busca informada.
            </flux:subheading>
        </div>
    @endif
</div>
