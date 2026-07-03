<?php

use App\Models\Produto;
use Livewire\Volt\Component;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public string $nom_produto = '';
    public string $des_produto = '';
    public string $val_preco = '';
    public string $qtd_produto = '0';
    public bool $ind_favorito = false;
    
    public ?Produto $editingProduct = null;
    public bool $showModal = false;

    public string $search = '';

    protected $rules = [
        'nom_produto' => 'required|string|max:100',
        'des_produto' => 'nullable|string|max:255',
        'val_preco' => 'required|numeric|min:0',
        'qtd_produto' => 'required|integer|min:0',
        'ind_favorito' => 'boolean',
    ];

    #[Computed]
    public function produtos()
    {
        return Produto::query()
            ->when($this->search, function($query) {
                $query->where(function($q) {
                    $q->where('nom_produto', 'like', '%' . $this->search . '%')
                      ->orWhere('des_produto', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy('ind_favorito', 'desc')
            ->orderBy('nom_produto', 'asc')
            ->get();
    }

    public function openCreateModal(): void
    {
        $this->resetValidation();
        $this->editingProduct = null;
        $this->nom_produto = '';
        $this->des_produto = '';
        $this->val_preco = '';
        $this->qtd_produto = '0';
        $this->ind_favorito = false;
        $this->showModal = true;
    }

    public function edit(Produto $produto): void
    {
        $this->resetValidation();
        $this->editingProduct = $produto;
        $this->nom_produto = $produto->nom_produto;
        $this->des_produto = $produto->des_produto ?? '';
        $this->val_preco = (string) $produto->val_preco;
        $this->qtd_produto = (string) $produto->qtd_produto;
        $this->ind_favorito = (bool) $produto->ind_favorito;
        $this->showModal = true;
    }

    public function salvar(): void
    {
        $validated = $this->validate();

        if ($this->editingProduct) {
            $this->editingProduct->update(array_merge($validated, [
                'usu_alteracao' => Auth::id(),
            ]));
            \Flux::toast(__('messages.alerts.success.product_updated'), variant: 'success');
        } else {
            Produto::create(array_merge($validated, [
                'usu_inclusao' => Auth::id(),
            ]));
            \Flux::toast(__('messages.alerts.success.product_created'), variant: 'success');
        }

        $this->showModal = false;
    }

    public function excluir(Produto $produto): void
    {
        $produto->update([
            'usu_alteracao' => Auth::id(),
        ]);
        $produto->delete();
        \Flux::toast(__('messages.alerts.success.product_deleted'), variant: 'success');
    }
}; ?>

<div class="space-y-6">
    <style>
        /* Estilo para descolar tabelas das paredes do container no desktop */
        .vendas-table [data-flux-column]:first-child,
        .vendas-table [data-flux-cell]:first-child {
            padding-left: 1.25rem !important;
        }
        .vendas-table [data-flux-column]:last-child,
        .vendas-table [data-flux-cell]:last-child {
            padding-right: 1.25rem !important;
        }
    </style>

    <div class="flex flex-col md:flex-row justify-between md:items-center gap-4">
        <div>
            <flux:heading size="lg">Catálogo de Produtos</flux:heading>
            <flux:subheading>Gerencie os produtos disponíveis para venda e seus estoques.</flux:subheading>
        </div>
        <flux:button variant="primary" icon="plus" class="w-full mt-4 md:w-auto md:mt-0" wire:click="openCreateModal">
            Cadastrar Produto
        </flux:button>
    </div>

    @if (session()->has('success'))
        <div class="p-4 bg-green-50 border border-green-200 text-green-700 dark:bg-green-950/20 dark:border-green-900/50 dark:text-green-400 rounded-xl text-sm font-medium">
            {{ session('success') }}
        </div>
    @endif

    {{-- Filtro de Busca --}}
    <div class="w-full md:w-96">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar produto..." icon="magnifying-glass" />
    </div>

    {{-- Tabela de Produtos (Desktop) --}}
    <div class="hidden md:block border border-zinc-200 dark:border-zinc-700 rounded-xl overflow-hidden bg-white dark:bg-zinc-800">
        @if($this->produtos->isEmpty())
            <div class="p-8 text-center text-zinc-500 italic">
                Nenhum produto cadastrado no catálogo.
            </div>
        @else
            <flux:table class="vendas-table">
                <flux:table.columns>
                    <flux:table.column class="px-4 py-3 align-middle">Nome</flux:table.column>
                    <flux:table.column class="px-4 py-3 align-middle">Descrição</flux:table.column>
                    <flux:table.column class="px-4 py-3 align-middle">Preço Unitário</flux:table.column>
                    <flux:table.column class="px-4 py-3 align-middle text-center" align="center">Estoque</flux:table.column>
                    <flux:table.column class="px-4 py-3 align-middle text-right" align="end">Ações</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach($this->produtos as $prod)
                        <flux:table.row :key="$prod->idt_produto">
                            <flux:table.cell class="px-4 py-3 align-middle font-semibold text-zinc-900 dark:text-white">
                                <div class="flex items-center gap-1.5">
                                    @if($prod->ind_favorito)
                                        <flux:icon name="star" variant="solid" class="text-yellow-400 size-4 shrink-0" title="Favorito" />
                                    @endif
                                    <span>{{ $prod->nom_produto }}</span>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell class="px-4 py-3 align-middle text-zinc-500 max-w-xs truncate">
                                {{ $prod->des_produto ?? '-' }}
                            </flux:table.cell>
                            <flux:table.cell class="px-4 py-3 align-middle font-medium">
                                R$ {{ number_format($prod->val_preco, 2, ',', '.') }}
                            </flux:table.cell>
                            <flux:table.cell class="px-4 py-3 align-middle text-center font-bold" align="center">
                                <span class="px-2.5 py-0.5 rounded-full text-xs {{ $prod->qtd_produto > 0 ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/30 dark:text-blue-400' : 'bg-red-50 text-red-700 dark:bg-red-950/30 dark:text-red-400' }}">
                                    {{ $prod->qtd_produto }}
                                </span>
                            </flux:table.cell>
                            <flux:table.cell class="px-4 py-3 align-middle text-right space-x-2" align="end">
                                <flux:button variant="ghost" size="sm" icon="pencil-square" wire:click="edit({{ $prod->idt_produto }})"></flux:button>
                                <flux:button variant="ghost" size="sm" icon="trash" class="text-red-600 hover:text-red-700" wire:confirm="Deseja realmente remover este produto do catálogo?" wire:click="excluir({{ $prod->idt_produto }})"></flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </div>

    {{-- Lista de Produtos (Mobile - Cards) --}}
    <div class="flex flex-col gap-4 md:hidden">
        @if($this->produtos->isEmpty())
            <div class="p-8 text-center text-zinc-500 italic bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700">
                Nenhum produto cadastrado no catálogo.
            </div>
        @else
            @foreach($this->produtos as $prod)
                <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl p-4 shadow-xs space-y-2">
                    <div class="flex justify-between items-start gap-4">
                        <div class="font-bold text-zinc-950 dark:text-white text-base flex items-center gap-1.5">
                            @if($prod->ind_favorito)
                                <flux:icon name="star" variant="solid" class="text-yellow-400 size-4 shrink-0" title="Favorito" />
                            @endif
                            <span>{{ $prod->nom_produto }}</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <flux:button variant="ghost" size="sm" icon="pencil-square" wire:click="edit({{ $prod->idt_produto }})"></flux:button>
                            <flux:button variant="ghost" size="sm" icon="trash" class="text-red-600 hover:text-red-700" wire:confirm="Deseja realmente remover este produto do catálogo?" wire:click="excluir({{ $prod->idt_produto }})"></flux:button>
                        </div>
                    </div>

                    <div class="text-zinc-500 dark:text-zinc-400 text-sm">
                        {{ $prod->des_produto ?? '-' }}
                    </div>

                    <div class="flex justify-between items-center pt-3 mt-3 border-t border-zinc-100 dark:border-zinc-700">
                        <div class="font-medium text-sm text-zinc-900 dark:text-white">
                            R$ {{ number_format($prod->val_preco, 2, ',', '.') }}
                        </div>
                        <div class="font-bold">
                            <span class="px-2.5 py-0.5 rounded-full text-xs {{ $prod->qtd_produto > 0 ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/30 dark:text-blue-400' : 'bg-red-50 text-red-700 dark:bg-red-950/30 dark:text-red-400' }}">
                                Estoque: {{ $prod->qtd_produto }}
                            </span>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    {{-- Modal Cadastro/Edição --}}
    @if($showModal)
        <div class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
            <div class="w-full max-w-lg bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 shadow-2xl rounded-2xl overflow-hidden p-6 space-y-6">
                <div>
                    <flux:heading size="lg">{{ $editingProduct ? 'Editar Produto' : 'Cadastrar Novo Produto' }}</flux:heading>
                    <flux:subheading>Insira as informações do item do Mercadinho.</flux:subheading>
                </div>

                <form wire:submit.prevent="salvar" class="space-y-4">
                    <flux:input wire:model="nom_produto" label="Nome do Produto" placeholder="Ex: Pão de Queijo" required />
                    <flux:input wire:model="des_produto" label="Descrição (Opcional)" placeholder="Ex: Assado na hora" />
                    
                    <div class="grid grid-cols-2 gap-4">
                        <flux:input wire:model="val_preco" label="Preço de Venda (R$)" type="number" step="0.01" min="0" required />
                        <flux:input wire:model="qtd_produto" label="Quantidade em Estoque" type="number" min="0" required />
                    </div>

                    <flux:switch wire:model="ind_favorito" label="Favorito (Exibir no topo)" />

                    <flux:separator />

                    <div class="flex justify-end gap-3">
                        <flux:button variant="ghost" wire:click="$set('showModal', false)">Cancelar</flux:button>
                        <flux:button variant="primary" type="submit">Salvar</flux:button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
