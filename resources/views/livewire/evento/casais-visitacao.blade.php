<?php

use App\Models\Evento;
use App\Models\Pessoa;
use App\Models\Trabalhador;
use App\Enums\EstadoCivil;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public Evento $evento;
    public string $search = '';
    
    // Edição de endereço
    public ?int $pessoaEnderecoId = null;
    public string $enderecoForm = '';

    // Vínculo de cônjuge
    public ?int $pessoaConjugeId = null;
    public string $searchConjuge = '';
    public ?int $conjugeSelecionadoId = null;

    public function mount(Evento $evento): void
    {
        $this->evento = $evento;
    }

    public function abrirEndereco(int $idPessoa): void
    {
        $pessoa = Pessoa::findOrFail($idPessoa);
        $this->pessoaEnderecoId = $pessoa->idt_pessoa;
        $this->enderecoForm = $pessoa->des_endereco ?? '';
        $this->modal('modal-endereco')->show();
    }

    public function salvarEndereco(): void
    {
        $pessoa = Pessoa::findOrFail($this->pessoaEnderecoId);
        $pessoa->update([
            'des_endereco' => $this->enderecoForm
        ]);

        if ($pessoa->parceiro) {
            $pessoa->parceiro->update([
                'des_endereco' => $this->enderecoForm
            ]);
        }

        $this->modal('modal-endereco')->close();
        $this->pessoaEnderecoId = null;
        $this->enderecoForm = '';
        $this->dispatch('notify', message: 'Endereço de ' . $pessoa->nom_pessoa . ' atualizado!');
    }

    public function abrirVincularConjuge(int $idPessoa): void
    {
        $pessoa = Pessoa::findOrFail($idPessoa);
        $this->pessoaConjugeId = $pessoa->idt_pessoa;
        $this->searchConjuge = '';
        $this->conjugeSelecionadoId = null;
        $this->modal('modal-vincular-conjuge')->show();
    }

    public function selecionarConjuge(int $idConjuge): void
    {
        $pessoa1 = Pessoa::findOrFail($this->pessoaConjugeId);
        $pessoa2 = Pessoa::findOrFail($idConjuge);

        $pessoa1->setParceiro($pessoa2);

        $pessoa1->update(['tip_estado_civil' => EstadoCivil::CASADO]);
        $pessoa2->update(['tip_estado_civil' => EstadoCivil::CASADO]);

        // Inteligência: Copiar o endereço do cônjuge que possui cadastro, se houver
        if ($pessoa1->des_endereco && !$pessoa2->des_endereco) {
            $pessoa2->update(['des_endereco' => $pessoa1->des_endereco]);
        } elseif (!$pessoa1->des_endereco && $pessoa2->des_endereco) {
            $pessoa1->update(['des_endereco' => $pessoa2->des_endereco]);
        }

        $this->modal('modal-vincular-conjuge')->close();
        $this->pessoaConjugeId = null;
        $this->searchConjuge = '';
        $this->dispatch('notify', message: 'Cônjuge vinculado com sucesso!');
    }

    public function desvincularConjuge(int $idPessoa): void
    {
        $pessoa = Pessoa::findOrFail($idPessoa);
        $parceiro = $pessoa->parceiro;

        $pessoa->removeParceiro();

        $pessoa->update(['tip_estado_civil' => null]);
        if ($parceiro) {
            $parceiro->update(['tip_estado_civil' => null]);
        }

        $this->dispatch('notify', message: 'Vínculo do casal removido.');
    }

    public function alterarEstadoCivilDirect(int $idPessoa, string $estadoCivilVal): void
    {
        $pessoa = Pessoa::findOrFail($idPessoa);
        $novoEstadoCivil = EstadoCivil::tryFrom($estadoCivilVal);

        $pessoa->update(['tip_estado_civil' => $novoEstadoCivil]);

        if ($novoEstadoCivil && !$novoEstadoCivil->precisaDeConjuge() && $pessoa->idt_parceiro) {
            $pessoa->removeParceiro();
        }

        $this->dispatch('notify', message: 'Estado civil de ' . $pessoa->nom_pessoa . ' atualizado!');
    }

    public function with(): array
    {
        // Buscar pessoas da equipe de visitação
        $trabalhadores = Trabalhador::query()
            ->select('trabalhador.*')
            ->join('pessoa', 'trabalhador.idt_pessoa', '=', 'pessoa.idt_pessoa')
            ->where('trabalhador.idt_evento', $this->evento->idt_evento)
            ->whereHas('equipe', function ($q) {
                $q->whereRaw('LOWER(des_grupo) LIKE ?', ['%visita%']);
            })
            ->with(['pessoa.parceiro', 'equipe'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('pessoa.nom_pessoa', 'like', '%' . $this->search . '%')
                      ->orWhere('pessoa.nom_apelido', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy('pessoa.nom_pessoa', 'asc')
            ->paginate(15);

        // Busca dinâmica de cônjuge disponível
        $pessoasDisponiveis = [];
        if (strlen($this->searchConjuge) >= 2) {
            $pessoasDisponiveis = Pessoa::query()
                ->whereNull('idt_parceiro')
                ->where('idt_pessoa', '!=', $this->pessoaConjugeId)
                ->where(function ($q) {
                    $q->where('nom_pessoa', 'like', '%' . $this->searchConjuge . '%')
                      ->orWhere('nom_apelido', 'like', '%' . $this->searchConjuge . '%');
                })
                ->orderBy('nom_pessoa')
                ->limit(10)
                ->get();
        }

        return [
            'trabalhadores' => $trabalhadores,
            'pessoasDisponiveis' => $pessoasDisponiveis,
            'estadosCivis' => EstadoCivil::cases(),
        ];
    }
}; ?>

<section class="w-full">
    {{-- Cabeçalho --}}
    <header class="mb-8 space-y-4">
        <flux:button href="{{ route('eventos.gerenciamento', ['evento' => $evento->idt_evento]) }}" icon="arrow-left" variant="ghost" class="cursor-pointer text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">
            Voltar para Gerenciamento
        </flux:button>

        <div class="flex items-center gap-3">
            <flux:heading size="xl" class="text-indigo-900 dark:text-indigo-100 font-bold tracking-tight">
                Casais de Visitação
            </flux:heading>
            <flux:badge size="sm" class="uppercase font-bold tracking-wider" color="indigo">
                {{ $evento->des_evento }}
            </flux:badge>
        </div>
        <flux:subheading>
            Ajuste rápido de estado civil, cônjuges e endereços dos trabalhadores da equipe de visitação.
        </flux:subheading>
        <flux:separator variant="subtle" />
    </header>

    {{-- Filtros e Busca --}}
    <div class="mb-6 flex justify-end gap-3">
        <flux:input
            wire:model.live.debounce.300ms="search"
            icon="magnifying-glass"
            placeholder="Buscar trabalhador..."
            class="w-full sm:w-80"
        />
    </div>

    {{-- Tabela de Dados --}}
    <main class="bg-white dark:bg-zinc-800 p-6 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm relative">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Trabalhador</flux:table.column>
                <flux:table.column>Estado Civil</flux:table.column>
                <flux:table.column>Cônjuge / Parceiro</flux:table.column>
                <flux:table.column>Endereço</flux:table.column>
                <flux:table.column align="end">Ações</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($trabalhadores as $trabalhador)
                    @php
                        $pessoa = $trabalhador->pessoa;
                    @endphp
                    <flux:table.row :key="'casal-row-'.$trabalhador->idt_trabalhador">
                        {{-- Trabalhador --}}
                        <flux:table.cell>
                            <div class="flex items-center gap-3">
                                <flux:avatar :initials="mb_substr($pessoa->nom_pessoa ?? '??', 0, 2)" size="sm" />
                                <div>
                                    <div class="font-medium text-zinc-900 dark:text-white">{{ $pessoa->nom_pessoa }}</div>
                                    <div class="text-xs text-zinc-500">{{ $pessoa->nom_apelido ? '"'.$pessoa->nom_apelido.'"' : '' }} • {{ $pessoa->num_cpf_pessoa ?? 'CPF Não Informado' }}</div>
                                </div>
                            </div>
                        </flux:table.cell>

                        {{-- Estado Civil --}}
                        <flux:table.cell>
                            <flux:select 
                                wire:change="alterarEstadoCivilDirect({{ $pessoa->idt_pessoa }}, $event.target.value)" 
                                class="w-48"
                                size="sm"
                            >
                                <option value="">Não Informado</option>
                                @foreach ($estadosCivis as $estado)
                                    <option value="{{ $estado->value }}" @selected(optional($pessoa->tip_estado_civil)->value === $estado->value)>
                                        {{ $estado->label() }}
                                    </option>
                                @endforeach
                            </flux:select>
                        </flux:table.cell>

                        {{-- Cônjuge --}}
                        <flux:table.cell>
                            @if ($pessoa->parceiro)
                                <div class="flex items-center gap-2">
                                    <flux:avatar :initials="mb_substr($pessoa->parceiro->nom_pessoa, 0, 2)" size="xs" />
                                    <div>
                                        <div class="text-sm font-medium text-zinc-900 dark:text-white">{{ $pessoa->parceiro->nom_pessoa }}</div>
                                        <button 
                                            wire:click="desvincularConjuge({{ $pessoa->idt_pessoa }})"
                                            class="text-xs text-red-500 hover:text-red-700 underline font-semibold transition-colors cursor-pointer"
                                        >
                                            Desvincular
                                        </button>
                                    </div>
                                </div>
                            @else
                                <flux:button 
                                    wire:click="abrirVincularConjuge({{ $pessoa->idt_pessoa }})" 
                                    size="xs" 
                                    icon="user-plus" 
                                    variant="subtle"
                                    class="cursor-pointer"
                                >
                                    Vincular Cônjuge
                                </flux:button>
                            @endif
                        </flux:table.cell>

                        {{-- Endereço --}}
                        <flux:table.cell class="max-w-[20rem] truncate">
                            @if ($pessoa->des_endereco)
                                <span class="text-sm text-zinc-800 dark:text-zinc-200" title="{{ $pessoa->des_endereco }}">
                                    {{ $pessoa->des_endereco }}
                                </span>
                            @else
                                <span class="text-xs text-red-500 italic font-medium">Endereço em branco</span>
                            @endif
                        </flux:table.cell>

                        {{-- Ações --}}
                        <flux:table.cell>
                            <div class="flex justify-end gap-2">
                                <flux:button 
                                    wire:click="abrirEndereco({{ $pessoa->idt_pessoa }})"
                                    icon="map-pin" 
                                    size="sm" 
                                    variant="ghost"
                                    tooltip="Cadastrar / Editar Endereço"
                                    class="cursor-pointer"
                                />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="text-center py-10 text-zinc-500 italic">
                            Nenhum trabalhador da equipe de visitação localizado neste evento.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        <div class="mt-4">
            {{ $trabalhadores->links(data: ['scrollTo' => false]) }}
        </div>
    </main>

    {{-- Modal para Edição de Endereço --}}
    <flux:modal name="modal-endereco" class="min-w-[20rem] md:min-w-[30rem]">
        <form wire:submit="salvarEndereco" class="space-y-6">
            <div>
                <flux:heading size="lg">Gerenciar Endereço</flux:heading>
                <flux:subheading>Insira ou atualize o endereço do trabalhador. Se houver cônjuge vinculado, o endereço dele será atualizado junto.</flux:subheading>
            </div>

            <flux:input 
                wire:model="enderecoForm" 
                label="Endereço Completo" 
                placeholder="Rua, Número, Bairro, Cidade - UF" 
                required 
                autofocus
            />

            <div class="flex gap-2 justify-end">
                <flux:modal.close>
                    <flux:button variant="ghost" class="cursor-pointer">Cancelar</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" class="cursor-pointer">Salvar Endereço</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Modal para Vincular Cônjuge --}}
    <flux:modal name="modal-vincular-conjuge" class="min-w-[20rem] md:min-w-[30rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Vincular Cônjuge</flux:heading>
                <flux:subheading>Busque por um cônjuge cadastrado na base de dados para associar a esta pessoa.</flux:subheading>
            </div>

            <flux:input 
                wire:model.live.debounce.300ms="searchConjuge" 
                label="Buscar Cônjuge" 
                placeholder="Digite o nome ou apelido do cônjuge..."
                icon="magnifying-glass"
                autofocus
            />

            @if (strlen($searchConjuge) >= 2)
                <div class="space-y-3">
                    <flux:text size="sm" weight="semibold" class="uppercase tracking-wider text-zinc-500">Resultados Encontrados</flux:text>
                    <div class="max-h-60 overflow-y-auto border border-zinc-200 dark:border-zinc-700 rounded-lg divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse ($pessoasDisponiveis as $disponivel)
                            <div class="p-3 flex items-center justify-between hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                <div>
                                    <div class="font-medium text-sm text-zinc-900 dark:text-white">{{ $disponivel->nom_pessoa }}</div>
                                    <div class="text-xs text-zinc-500">{{ $disponivel->nom_apelido ? '"'.$disponivel->nom_apelido.'"' : '' }} • {{ $disponivel->num_cpf_pessoa }}</div>
                                </div>
                                <flux:button 
                                    wire:click="selecionarConjuge({{ $disponivel->idt_pessoa }})"
                                    size="xs" 
                                    variant="filled" 
                                    color="indigo"
                                    class="cursor-pointer"
                                >
                                    Vincular
                                </flux:button>
                            </div>
                        @empty
                            <div class="p-4 text-center text-zinc-500 text-sm italic">
                                Nenhuma pessoa disponível localizada.
                            </div>
                        @endforelse
                    </div>
                </div>
            @endif

            <div class="flex justify-end">
                <flux:modal.close>
                    <flux:button variant="ghost" class="cursor-pointer">Cancelar</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>
</section>
