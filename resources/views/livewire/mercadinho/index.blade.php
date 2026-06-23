<?php

use App\Models\Evento;
use App\Models\Pessoa;
use App\Models\Conta;
use App\Models\Transacao;
use App\Models\Produto;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

new class extends Component {
    use WithPagination;

    public ?Evento $evento = null;
    public ?int $selectedEventoId = null;
    public string $activeSubTab = 'operacao'; // 'operacao', 'catalogo' ou 'relatorio'
    
    // Filtros e Buscas
    public string $search = '';
    public string $filtroSaldo = 'todos'; // 'todos', 'devedores', 'credores'
    public string $filtroEquipe = '';
    public string $filtroCor = '';
    
    // Modais e Seleções
    public ?int $selectedPessoaId = null;
    public bool $showCompraModal = false;
    public bool $showCreditoModal = false;
    public bool $showExtratoModal = false;

    // Aportes de Crédito / Quitação
    public string $val_aporte = '';
    public string $des_aporte = '';
    public string $tip_transacao_credito = 'D'; // 'D' (Depósito) ou 'P' (Pagamento/Quitação)

    // Carrinho de Compras
    public array $cart = []; // [idt_produto => ['qtd' => X, 'nom' => Y, 'val' => Z]]

    public function mount(?Evento $evento = null): void
    {
        if ($evento && $evento->exists) {
            $this->evento = $evento;
            $this->selectedEventoId = $evento->idt_evento;
        } else {
            $this->evento = null;
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFiltroSaldo(): void
    {
        $this->resetPage();
    }

    public function updatingFiltroEquipe(): void
    {
        $this->resetPage();
    }

    public function updatingFiltroCor(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function eventosAtivos()
    {
        $user = Auth::user();
        return Evento::query()
            ->when($user->idt_movimento, function ($q) use ($user) {
                return $q->where('idt_movimento', $user->idt_movimento);
            })
            ->orderBy('dat_inicio', 'desc')
            ->get();
    }

    public function selectEvento(int $eventoId): void
    {
        $evento = Evento::find($eventoId);
        if ($evento) {
            $this->evento = $evento;
            $this->selectedEventoId = $evento->idt_evento;
            $this->resetPage();
            $this->cart = [];
            $this->selectedPessoaId = null;
        }
    }

    public function alterarEvento(): void
    {
        $this->evento = null;
        $this->selectedEventoId = null;
    }

    #[Computed]
    public function pessoas()
    {
        if (!$this->evento || !$this->evento->exists) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);
        }

        $eventoId = $this->evento->idt_evento;
        $filtro = $this->filtroSaldo;

        return Pessoa::where(function($query) use ($eventoId) {
            $query->whereHas('participantes', fn($q) => $q->where('idt_evento', $eventoId))
                  ->orWhereHas('trabalhadores', fn($q) => $q->where('idt_evento', $eventoId));
        })
        ->when($this->search, function($q) {
            $q->searchByName($this->search);
        })
        ->when($this->filtroCor, function($q) use ($eventoId) {
            $q->whereHas('participantes', fn($qp) => $qp->where('idt_evento', $eventoId)->where('tip_cor_troca', $this->filtroCor));
        })
        ->when($this->filtroEquipe, function($q) use ($eventoId) {
            $q->whereHas('trabalhadores', fn($qt) => $qt->where('idt_evento', $eventoId)->where('idt_equipe', $this->filtroEquipe));
        })
        ->when($filtro !== 'todos', function($q) use ($filtro, $eventoId) {
            $q->whereHas('contas', function($query) use ($eventoId, $filtro) {
                $query->where('idt_evento', $eventoId)
                      ->when($filtro === 'devedores', fn($query) => $query->where('val_saldo', '<', 0))
                      ->when($filtro === 'credores', fn($query) => $query->where('val_saldo', '>', 0));
            });
        })
        ->orderBy('nom_pessoa', 'asc')
        ->paginate(10);
    }

    #[Computed]
    public function produtosDisponiveis()
    {
        return Produto::query()
            ->orderBy('ind_favorito', 'desc')
            ->orderBy('nom_produto', 'asc')
            ->get();
    }

    #[Computed]
    public function equipes()
    {
        if (!$this->evento || !$this->evento->exists) {
            return collect();
        }

        return \App\Models\TipoEquipe::where('idt_movimento', $this->evento->idt_movimento)
            ->orderBy('des_grupo', 'asc')
            ->get();
    }

    #[Computed]
    public function resumoFinanceiro(): array
    {
        if (!$this->evento || !$this->evento->exists) {
            return [
                'faturamento' => 0.00,
                'recebido' => 0.00,
                'devedores' => 0.00,
                'credores' => 0.00,
            ];
        }

        $eventoId = $this->evento->idt_evento;
        
        $contas = Conta::where('idt_evento', $eventoId)->get();

        $faturamento = Transacao::whereHas('conta', fn($q) => $q->where('idt_evento', $eventoId))
            ->where('tip_transacao', 'C')
            ->sum('val_transacao');

        $recebido = Transacao::whereHas('conta', fn($q) => $q->where('idt_evento', $eventoId))
            ->whereIn('tip_transacao', ['D', 'P'])
            ->sum('val_transacao');

        $devedores = $contas->where('val_saldo', '<', 0)->sum('val_saldo');
        $credores = $contas->where('val_saldo', '>', 0)->sum('val_saldo');

        return [
            'faturamento' => (float) $faturamento,
            'recebido' => (float) $recebido,
            'devedores' => abs((float) $devedores),
            'credores' => (float) $credores,
        ];
    }

    #[Computed]
    public function relatorioVendas()
    {
        if (!$this->evento || !$this->evento->exists) {
            return collect();
        }

        $eventoId = $this->evento->idt_evento;

        return Transacao::whereHas('conta', fn($q) => $q->where('idt_evento', $eventoId))
            ->where('tip_transacao', 'C')
            ->select('idt_produto', 'nom_item', DB::raw('SUM(qtd_item) as total_qtd'), DB::raw('SUM(val_transacao) as total_valor'))
            ->groupBy('idt_produto', 'nom_item')
            ->orderBy('total_qtd', 'desc')
            ->get();
    }

    public function getConta(int $idt_pessoa): Conta
    {
        return Conta::firstOrCreate(
            ['idt_pessoa' => $idt_pessoa, 'idt_evento' => $this->evento->idt_evento],
            ['val_saldo' => 0.00, 'usu_inclusao' => Auth::id()]
        );
    }

    // Ações de Modal
    public function openCompra(int $idt_pessoa): void
    {
        $this->selectedPessoaId = $idt_pessoa;
        $this->cart = [];
        $this->showCompraModal = true;
    }

    public function openCredito(int $idt_pessoa): void
    {
        $this->selectedPessoaId = $idt_pessoa;
        $this->val_aporte = '';
        $this->des_aporte = '';
        $this->tip_transacao_credito = 'D';
        $this->showCreditoModal = true;
    }

    public function openExtrato(int $idt_pessoa): void
    {
        $this->selectedPessoaId = $idt_pessoa;
        $this->showExtratoModal = true;
    }

    // Lógicas de Carrinho
    public function adicionarAoCarrinho(int $idt_produto): void
    {
        $produto = Produto::find($idt_produto);
        if (!$produto) return;

        $qtdNoCarrinho = $this->cart[$idt_produto]['qtd'] ?? 0;

        if ($produto->qtd_produto < ($qtdNoCarrinho + 1)) {
            session()->flash('cart_error', "Estoque insuficiente para {$produto->nom_produto} (Disponível: {$produto->qtd_produto}).");
            return;
        }

        if (isset($this->cart[$idt_produto])) {
            $this->cart[$idt_produto]['qtd']++;
        } else {
            $this->cart[$idt_produto] = [
                'nom' => $produto->nom_produto,
                'val' => (float) $produto->val_preco,
                'qtd' => 1
            ];
        }
    }

    public function removerDoCarrinho(int $idt_produto): void
    {
        if (isset($this->cart[$idt_produto])) {
            if ($this->cart[$idt_produto]['qtd'] > 1) {
                $this->cart[$idt_produto]['qtd']--;
            } else {
                unset($this->cart[$idt_produto]);
            }
        }
    }

    public function finalizarCompra(): void
    {
        if (!$this->selectedPessoaId) return;
        
        $conta = $this->getConta($this->selectedPessoaId);

        DB::transaction(function() use ($conta) {
            // Lançar itens do catálogo
            foreach ($this->cart as $idt_produto => $item) {
                $produto = Produto::lockForUpdate()->find($idt_produto);
                if (!$produto || $produto->qtd_produto < $item['qtd']) {
                    throw new \Exception("Estoque esgotado durante o processamento para: " . ($produto->nom_produto ?? 'Produto'));
                }

                Transacao::create([
                    'idt_conta' => $conta->idt_conta,
                    'idt_produto' => $idt_produto,
                    'tip_transacao' => 'C',
                    'nom_item' => $produto->nom_produto,
                    'qtd_item' => $item['qtd'],
                    'val_unitario' => $produto->val_preco,
                    'val_transacao' => $item['qtd'] * $produto->val_preco,
                    'dat_transacao' => now(),
                    'usu_inclusao' => Auth::id(),
                ]);
            }
        });

        $this->showCompraModal = false;
        $this->cart = [];
        session()->flash('success', 'Compra registrada com sucesso!');
    }

    // Lançamento de Crédito/Aporte
    public function registrarCredito(): void
    {
        $this->validate([
            'val_aporte' => 'required|numeric|min:0.01',
            'des_aporte' => 'nullable|string|max:255',
            'tip_transacao_credito' => 'required|in:D,P'
        ]);

        $conta = $this->getConta($this->selectedPessoaId);
        $valor = (float) $this->val_aporte;
        
        $desc = $this->des_aporte;
        if (empty($desc)) {
            $desc = $this->tip_transacao_credito === 'D' ? 'Crédito Antecipado' : 'Pagamento de Conta';
        }

        Transacao::create([
            'idt_conta' => $conta->idt_conta,
            'idt_produto' => null,
            'tip_transacao' => $this->tip_transacao_credito,
            'nom_item' => $desc,
            'val_transacao' => $valor,
            'dat_transacao' => now(),
            'usu_inclusao' => Auth::id(),
        ]);

        $this->showCreditoModal = false;
        session()->flash('success', 'Crédito/Pagamento registrado com sucesso!');
    }

    // Estorno de transação
    public function estornarTransacao(int $idt_transacao): void
    {
        $transacao = Transacao::find($idt_transacao);
        if ($transacao) {
            $transacao->delete();
            session()->flash('success', 'Transação estornada com sucesso!');
        }
    }
}; ?>

<div class="space-y-6 w-full max-w-7xl mx-auto p-4 md:p-8">
    {{-- Cabeçalho --}}
    <header class="mb-8 flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Mercadinho</h1>
            <p class="text-gray-600 mt-1 dark:text-gray-400">Gerencie produtos, estoque e as contas dos participantes e trabalhadores do evento.</p>
        </div>
    </header>
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

    @if($evento && $evento->exists)
        {{-- Cabeçalho do Evento Selecionado --}}
        <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 bg-zinc-50 dark:bg-zinc-900/50 p-4 rounded-2xl border border-zinc-200 dark:border-zinc-700">
            <div>
                <flux:heading size="lg">{{ $evento->des_evento }}</flux:heading>
                <flux:subheading class="uppercase font-bold text-xs text-blue-600 dark:text-blue-400">
                    Mercadinho &bull; {{ $evento->movimento->des_sigla }}
                </flux:subheading>
            </div>
            <flux:button variant="ghost" size="sm" icon="arrow-left" wire:click="alterarEvento">
                Voltar
            </flux:button>
        </div>

        {{-- Menu Local de Abas --}}
        <div class="flex overflow-x-auto no-scrollbar border-b border-zinc-200 dark:border-zinc-700 whitespace-nowrap">
            <button 
                wire:click="$set('activeSubTab', 'operacao')" 
                class="px-4 py-2 font-semibold text-sm border-b-2 {{ $activeSubTab === 'operacao' ? 'border-blue-600 text-blue-600' : 'border-transparent text-zinc-500 hover:text-zinc-700' }}"
            >
                Operar Mercadinho
            </button>
            <button 
                wire:click="$set('activeSubTab', 'catalogo')" 
                class="px-4 py-2 font-semibold text-sm border-b-2 {{ $activeSubTab === 'catalogo' ? 'border-blue-600 text-blue-600' : 'border-transparent text-zinc-500 hover:text-zinc-700' }}"
            >
                Produtos e Estoque
            </button>
            <button 
                wire:click="$set('activeSubTab', 'relatorio')" 
                class="px-4 py-2 font-semibold text-sm border-b-2 {{ $activeSubTab === 'relatorio' ? 'border-blue-600 text-blue-600' : 'border-transparent text-zinc-500 hover:text-zinc-700' }}"
            >
                Relatório de Vendas
            </button>
        </div>

        @if($activeSubTab === 'catalogo')
            {{-- Tela do Catálogo --}}
            <livewire:mercadinho.produtos />
        @elseif($activeSubTab === 'relatorio')
            {{-- Relatório de Vendas --}}
            <div class="space-y-6 px-4 sm:px-6 md:px-0">
                <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700 p-4 sm:p-6 space-y-4">
                    <div>
                        <flux:heading size="lg">Relatório de Vendas</flux:heading>
                        <flux:subheading>Produtos ordenados pela quantidade de vendas realizadas.</flux:subheading>
                    </div>

                    {{-- Tabela do Relatório (Desktop) --}}
                    <div class="hidden md:block border border-zinc-200 dark:border-zinc-700 rounded-xl overflow-hidden bg-white dark:bg-zinc-800">
                        @if($this->relatorioVendas->isEmpty())
                            <div class="p-8 text-center text-zinc-500 italic">
                                Nenhuma venda realizada neste evento.
                            </div>
                        @else
                            <flux:table class="vendas-table">
                                <flux:table.columns>
                                    <flux:table.column class="px-4 py-3 align-middle">Produto</flux:table.column>
                                    <flux:table.column class="px-4 py-3 align-middle">Vendas</flux:table.column>
                                    <flux:table.column class="px-4 py-3 align-middle">Total</flux:table.column>
                                </flux:table.columns>

                                <flux:table.rows>
                                    @foreach($this->relatorioVendas as $item)
                                        <flux:table.row :key="$item->idt_produto">
                                            <flux:table.cell class="px-4 py-3 align-middle">
                                                <span class="font-semibold text-zinc-950 dark:text-white">
                                                    {{ $item->nom_item }}
                                                </span>
                                            </flux:table.cell>
                                            <flux:table.cell class="px-4 py-3 align-middle">
                                                <span class="font-bold text-sm text-zinc-900 dark:text-white">
                                                    {{ $item->total_qtd }}
                                                </span>
                                            </flux:table.cell>
                                            <flux:table.cell class="px-4 py-3 align-middle">
                                                <span class="font-bold text-sm text-zinc-900 dark:text-white">
                                                    R$ {{ number_format($item->total_valor, 2, ',', '.') }}
                                                </span>
                                            </flux:table.cell>
                                        </flux:table.row>
                                    @endforeach
                                </flux:table.rows>
                            </flux:table>
                        @endif
                    </div>

                    {{-- Lista do Relatório (Mobile) --}}
                    <div class="md:hidden">
                        @if($this->relatorioVendas->isEmpty())
                            <div class="p-8 text-center text-zinc-500 italic border border-zinc-200 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-800">
                                Nenhuma venda realizada neste evento.
                            </div>
                        @else
                            <div class="space-y-3">
                                @foreach($this->relatorioVendas as $item)
                                    <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl p-4 shadow-xs">
                                        <div class="font-semibold text-zinc-950 dark:text-white mb-2">
                                            {{ $item->nom_item }}
                                        </div>
                                        <div class="flex justify-between items-center text-sm">
                                            <span class="text-zinc-500 dark:text-zinc-400">Vendas:</span>
                                            <span class="font-bold text-zinc-900 dark:text-white">{{ $item->total_qtd }}</span>
                                        </div>
                                        <div class="flex justify-between items-center text-sm mt-1">
                                            <span class="text-zinc-500 dark:text-zinc-400">Total:</span>
                                            <span class="font-bold text-zinc-900 dark:text-white">
                                                R$ {{ number_format($item->total_valor, 2, ',', '.') }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @else
            {{-- Tela Principal de Operação --}}
            <div class="space-y-6 px-4 sm:px-6 md:px-0">
                {{-- Cards Resumo Financeiro --}}
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div class="p-4 sm:p-5 bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-zinc-200 dark:border-zinc-700">
                        <div class="text-xs text-zinc-400 font-bold uppercase tracking-wider">Consumido</div>
                        <div class="text-base sm:text-2xl font-bold mt-1 text-zinc-950 dark:text-white whitespace-nowrap">
                            R$ {{ number_format($this->resumoFinanceiro['faturamento'], 2, ',', '.') }}
                        </div>
                    </div>
                    <div class="p-4 sm:p-5 bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-zinc-200 dark:border-zinc-700">
                        <div class="text-xs text-zinc-400 font-bold uppercase tracking-wider">Recebido</div>
                        <div class="text-base sm:text-2xl font-bold mt-1 text-green-600 dark:text-green-400 whitespace-nowrap">
                            R$ {{ number_format($this->resumoFinanceiro['recebido'], 2, ',', '.') }}
                        </div>
                    </div>
                    <div class="p-4 sm:p-5 bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-zinc-200 dark:border-zinc-700">
                        <div class="text-xs text-zinc-400 font-bold uppercase tracking-wider">Devido (a receber)</div>
                        <div class="text-base sm:text-2xl font-bold mt-1 text-red-600 dark:text-red-400 whitespace-nowrap">
                            R$ {{ number_format($this->resumoFinanceiro['devedores'], 2, ',', '.') }}
                        </div>
                    </div>
                    <div class="p-4 sm:p-5 bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-zinc-200 dark:border-zinc-700">
                        <div class="text-xs text-zinc-400 font-bold uppercase tracking-wider">Positivo (créditos)</div>
                        <div class="text-base sm:text-2xl font-bold mt-1 text-blue-600 dark:text-blue-400 whitespace-nowrap">
                            R$ {{ number_format($this->resumoFinanceiro['credores'], 2, ',', '.') }}
                        </div>
                    </div>
                </div>

                @if (session()->has('success'))
                    <div class="p-4 bg-green-50 border border-green-200 text-green-700 dark:bg-green-950/20 dark:border-green-900/50 dark:text-green-400 rounded-xl text-sm font-medium">
                        {{ session('success') }}
                    </div>
                @endif

                {{-- Filtros e Lista de Contas --}}
                <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700 p-4 sm:p-6 space-y-4">
                    <div class="flex flex-col lg:flex-row gap-4 items-center justify-between">
                        <div class="flex flex-wrap gap-4 w-full lg:w-auto items-center">
                            <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar pessoa..." icon="magnifying-glass" class="w-full sm:w-64" />
                            
                            <flux:select wire:model.live="filtroEquipe" placeholder="Todas as Equipes" class="w-full sm:w-44">
                                <option value="">Todas as Equipes</option>
                                @foreach($this->equipes as $eq)
                                    <option value="{{ $eq->idt_equipe }}">{{ $eq->des_grupo }}</option>
                                @endforeach
                            </flux:select>
                            
                            <flux:select wire:model.live="filtroCor" placeholder="Todas as Cores" class="w-full sm:w-44">
                                <option value="">Todas as Cores</option>
                                @foreach(\App\Enums\CorTroca::cases() as $cor)
                                    <option value="{{ $cor->value }}">{{ $cor->label() }}</option>
                                @endforeach
                            </flux:select>
                        </div>
                        
                        <div class="flex flex-wrap lg:flex-nowrap gap-2 w-full lg:w-auto justify-start lg:justify-end">
                            <flux:button 
                                size="sm"
                                :variant="$filtroSaldo === 'todos' ? 'primary' : 'ghost'"
                                wire:click="$set('filtroSaldo', 'todos')"
                            >
                                Todos
                            </flux:button>
                            <flux:button 
                                size="sm"
                                :variant="$filtroSaldo === 'devedores' ? 'primary' : 'ghost'"
                                wire:click="$set('filtroSaldo', 'devedores')"
                            >
                                Saldo Devedor
                            </flux:button>
                            <flux:button 
                                size="sm"
                                :variant="$filtroSaldo === 'credores' ? 'primary' : 'ghost'"
                                wire:click="$set('filtroSaldo', 'credores')"
                            >
                                Com Crédito
                            </flux:button>
                        </div>
                    </div>

                    {{-- Tabela de Pessoas e Contas (Desktop) --}}
                    <div class="hidden md:block border border-zinc-200 dark:border-zinc-700 rounded-xl overflow-hidden bg-white dark:bg-zinc-800">
                        @if($this->pessoas->isEmpty())
                            <div class="p-8 text-center text-zinc-500 italic">
                                Nenhum participante ou trabalhador encontrado.
                            </div>
                        @else
                            <flux:table class="vendas-table">
                                <flux:table.columns>
                                    <flux:table.column class="px-4 py-3 align-middle">Nome / Apelido</flux:table.column>
                                    <flux:table.column class="px-4 py-3 align-middle">Tipo</flux:table.column>
                                    <flux:table.column class="px-4 py-3 align-middle">Saldo Atual</flux:table.column>
                                    <flux:table.column class="px-4 py-3 align-middle text-right" align="end">Ações</flux:table.column>
                                </flux:table.columns>

                                <flux:table.rows>
                                    @foreach($this->pessoas as $pessoa)
                                        @php
                                            $conta = $this->getConta($pessoa->idt_pessoa);
                                            $saldo = (float) $conta->val_saldo;
                                            
                                            // Identificar papel no evento
                                            $isTrabalhador = $pessoa->trabalhadores()->where('idt_evento', $evento->idt_evento)->exists();
                                            $tipoLabel = $isTrabalhador ? 'Trabalhador' : 'Participante';
                                            $tipoColor = $isTrabalhador ? 'purple' : 'green';
                                        @endphp
                                        <flux:table.row :key="$pessoa->idt_pessoa">
                                            <flux:table.cell class="px-4 py-3 align-middle">
                                                <div class="font-semibold text-zinc-950 dark:text-white">
                                                    {{ $pessoa->nom_pessoa }}
                                                </div>
                                                @if($pessoa->nom_apelido)
                                                    <div class="text-xs text-zinc-400">
                                                        ({{ $pessoa->nom_apelido }})
                                                    </div>
                                                @endif
                                            </flux:table.cell>
                                            <flux:table.cell class="px-4 py-3 align-middle">
                                                <flux:badge :color="$tipoColor" size="sm" class="font-bold">
                                                    {{ $tipoLabel }}
                                                </flux:badge>
                                            </flux:table.cell>
                                            <flux:table.cell class="px-4 py-3 align-middle">
                                                <span class="font-bold text-sm {{ $saldo < 0 ? 'text-red-600 dark:text-red-400' : ($saldo > 0 ? 'text-blue-600 dark:text-blue-400' : 'text-zinc-500') }}">
                                                    R$ {{ number_format($saldo, 2, ',', '.') }}
                                                </span>
                                            </flux:table.cell>
                                            <flux:table.cell class="px-4 py-3 align-middle text-right space-x-1" align="end">
                                                <flux:button size="sm" icon="shopping-bag" wire:click="openCompra({{ $pessoa->idt_pessoa }})">
                                                    Lançar Compra
                                                </flux:button>
                                                <flux:button size="sm" icon="banknotes" color="green" wire:click="openCredito({{ $pessoa->idt_pessoa }})">
                                                    Crédito / Pgto
                                                </flux:button>
                                                <flux:button size="sm" icon="document-magnifying-glass" variant="ghost" wire:click="openExtrato({{ $pessoa->idt_pessoa }})">
                                                    Extrato
                                                </flux:button>
                                            </flux:table.cell>
                                        </flux:table.row>
                                    @endforeach
                                </flux:table.rows>
                            </flux:table>
                        @endif
                    </div>

                    {{-- Lista de Pessoas (Mobile - Acordeão) --}}
                    <div class="md:hidden">
                        @if($this->pessoas->isEmpty())
                            <div class="p-8 text-center text-zinc-500 italic border border-zinc-200 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-800">
                                Nenhum participante ou trabalhador encontrado.
                            </div>
                        @else
                            <div class="space-y-3">
                                @foreach($this->pessoas as $pessoa)
                                    @php
                                        $conta = $this->getConta($pessoa->idt_pessoa);
                                        $saldo = (float) $conta->val_saldo;
                                        
                                        // Identificar papel no evento
                                        $isTrabalhador = $pessoa->trabalhadores()->where('idt_evento', $evento->idt_evento)->exists();
                                        $tipoLabel = $isTrabalhador ? 'Trabalhador' : 'Participante';
                                        $tipoColor = $isTrabalhador ? 'purple' : 'green';
                                    @endphp
                                    <div 
                                        x-data="{ expanded: false }" 
                                        class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl overflow-hidden shadow-xs transition-all duration-200"
                                        :class="expanded ? 'ring-1 ring-blue-500/50 border-blue-500/50 bg-zinc-50/10 dark:bg-zinc-800/50' : ''"
                                    >
                                        {{-- Cabeçalho do Accordion (Recolhido) --}}
                                        <button 
                                            @click="expanded = !expanded"
                                            type="button"
                                            class="w-full flex items-center justify-between p-4 text-left focus:outline-none"
                                        >
                                            <div class="flex-1 min-w-0 pr-4">
                                                <div class="font-semibold text-zinc-950 dark:text-white truncate">
                                                    {{ $pessoa->nom_pessoa }}
                                                </div>
                                                <div class="flex items-center gap-2 mt-1 flex-wrap">
                                                    @if($pessoa->nom_apelido)
                                                        <span class="text-xs text-zinc-500 dark:text-zinc-400 truncate">
                                                            ({{ $pessoa->nom_apelido }})
                                                        </span>
                                                    @endif
                                                    <flux:badge :color="$tipoColor" size="sm" class="font-bold scale-90 origin-left">
                                                        {{ $tipoLabel }}
                                                    </flux:badge>
                                                </div>
                                            </div>
                                            
                                            {{-- Chevron Icon --}}
                                            <div class="text-zinc-400 dark:text-zinc-500 transition-transform duration-200" :class="expanded ? 'rotate-180' : ''">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                                </svg>
                                            </div>
                                        </button>

                                        {{-- Conteúdo Expandido --}}
                                        <div 
                                            x-show="expanded" 
                                            x-collapse
                                            class="border-t border-zinc-150 dark:border-zinc-700/50 bg-zinc-50/50 dark:bg-zinc-900/20 p-4 space-y-3"
                                        >
                                            <div class="flex justify-between items-center text-sm">
                                                <span class="text-zinc-500 dark:text-zinc-400">Saldo Atual:</span>
                                                <span class="font-bold text-base {{ $saldo < 0 ? 'text-red-600 dark:text-red-400' : ($saldo > 0 ? 'text-blue-600 dark:text-blue-400' : 'text-zinc-500') }}">
                                                    R$ {{ number_format($saldo, 2, ',', '.') }}
                                                </span>
                                            </div>
                                            
                                            <div class="space-y-2 pt-2">
                                                {{-- Botão Principal de Compra --}}
                                                <flux:button 
                                                    icon="shopping-bag" 
                                                    variant="primary" 
                                                    class="w-full justify-center"
                                                    wire:click="openCompra({{ $pessoa->idt_pessoa }})"
                                                >
                                                    Lançar Compra
                                                </flux:button>
                                                
                                                <div class="flex flex-col sm:flex-row gap-2">
                                                    {{-- Botão de Crédito --}}
                                                    <flux:button 
                                                        size="sm" 
                                                        icon="banknotes" 
                                                        color="green" 
                                                        class="w-full sm:flex-1 justify-center"
                                                        wire:click="openCredito({{ $pessoa->idt_pessoa }})"
                                                    >
                                                        Crédito / Pgto
                                                    </flux:button>
                                                    
                                                    {{-- Botão de Extrato --}}
                                                    <flux:button 
                                                        size="sm" 
                                                        icon="document-magnifying-glass" 
                                                        variant="ghost" 
                                                        class="w-full sm:flex-1 justify-center"
                                                        wire:click="openExtrato({{ $pessoa->idt_pessoa }})"
                                                    >
                                                        Extrato
                                                    </flux:button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Paginação --}}
                    <div class="mt-4">
                        {{ $this->pessoas->links() }}
                    </div>
                </div>
            </div>
        @endif

        {{-- MODAL LANÇAR COMPRA --}}
        @if($showCompraModal && $selectedPessoaId)
            @php
                $pessoaSelected = Pessoa::find($selectedPessoaId);
                $totalFinalCompra = collect($cart)->sum(fn($item) => $item['qtd'] * $item['val']);
            @endphp
            <div class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
                <div class="w-full max-w-4xl bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 shadow-2xl rounded-2xl overflow-hidden p-6 space-y-6 flex flex-col max-h-[90vh]">
                    <div class="flex justify-between items-start gap-4">
                        <div>
                            <flux:heading size="lg">Registrar Compra - {{ $pessoaSelected->nom_pessoa }}</flux:heading>
                            <flux:subheading>Selecione os produtos do catálogo para registrar a compra.</flux:subheading>
                        </div>
                        <flux:button variant="ghost" icon="x-mark" wire:click="$set('showCompraModal', false)"></flux:button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 overflow-hidden flex-1">
                        {{-- Lado Esquerdo: Catálogo de Produtos --}}
                        <div class="flex flex-col space-y-3 overflow-hidden">
                            <div class="flex items-center justify-between py-1">
                                <div class="font-bold text-zinc-950 dark:text-white text-sm">Catálogo de Produtos</div>
                            </div>
                            <div class="overflow-y-auto flex-1 border border-zinc-200 dark:border-zinc-700 rounded-xl p-3 space-y-2 bg-zinc-50 dark:bg-zinc-900/50">
                                @if(session()->has('cart_error'))
                                    <div class="p-2.5 bg-red-50 text-red-700 text-xs rounded-lg font-bold border border-red-200">
                                        {{ session('cart_error') }}
                                    </div>
                                @endif

                                @forelse($this->produtosDisponiveis as $prod)
                                    <div class="flex items-center justify-between p-3 bg-white dark:bg-zinc-800 rounded-lg shadow-xs border border-zinc-150 dark:border-zinc-700">
                                        <div>
                                            <div class="font-semibold text-sm text-zinc-900 dark:text-white flex items-center gap-1.5">
                                                @if($prod->ind_favorito)
                                                    <flux:icon name="star" variant="solid" class="text-yellow-400 size-4 shrink-0" title="Favorito" />
                                                @endif
                                                <span>{{ $prod->nom_produto }}</span>
                                            </div>
                                            <div class="text-xs text-zinc-400">R$ {{ number_format($prod->val_preco, 2, ',', '.') }} | Estoque: {{ $prod->qtd_produto }}</div>
                                        </div>
                                        <flux:button 
                                            size="xs" 
                                            variant="filled" 
                                            icon="plus" 
                                            :disabled="$prod->qtd_produto <= 0"
                                            wire:click="adicionarAoCarrinho({{ $prod->idt_produto }})"
                                        >
                                            Add
                                        </flux:button>
                                    </div>
                                @empty
                                    <div class="text-center text-xs text-zinc-500 py-8 italic">
                                        Nenhum produto encontrado.
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        {{-- Lado Direito: Carrinho de Compras --}}
                        <div class="flex flex-col border border-zinc-200 dark:border-zinc-700 rounded-xl p-4 bg-white dark:bg-zinc-800 justify-between overflow-hidden">
                            <div class="flex flex-col overflow-hidden flex-1">
                                <div class="font-bold text-zinc-950 dark:text-white text-sm mb-3">Resumo da Compra</div>
                                
                                <div class="flex-1 overflow-y-auto space-y-2 pr-1">
                                    @if(empty($cart))
                                        <div class="text-center text-zinc-500 py-12 italic text-sm">
                                            Nenhum item selecionado.
                                        </div>
                                    @endif

                                    @foreach($cart as $id_prod => $item)
                                        <div class="flex items-center justify-between p-2.5 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
                                            <div class="text-sm">
                                                <span class="font-semibold text-zinc-900 dark:text-white">{{ $item['nom'] }}</span>
                                                <span class="text-xs text-zinc-400"> (x{{ $item['qtd'] }})</span>
                                            </div>
                                            <div class="flex items-center gap-3">
                                                <span class="text-sm font-bold text-zinc-700 dark:text-zinc-300">
                                                    R$ {{ number_format($item['qtd'] * $item['val'], 2, ',', '.') }}
                                                </span>
                                                <flux:button size="xs" variant="ghost" icon="trash" class="text-red-500" wire:click="removerDoCarrinho({{ $id_prod }})"></flux:button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="border-t border-zinc-200 dark:border-zinc-700 pt-4 mt-4 space-y-4">
                                <div class="flex justify-between items-center font-bold text-lg text-zinc-950 dark:text-white">
                                    <span>Total:</span>
                                    <span>R$ {{ number_format($totalFinalCompra, 2, ',', '.') }}</span>
                                </div>

                                <div class="flex gap-2 justify-end">
                                    <flux:button variant="ghost" wire:click="$set('showCompraModal', false)">Fechar</flux:button>
                                    <flux:button 
                                        variant="primary" 
                                        :disabled="($totalFinalCompra <= 0)"
                                        wire:click="finalizarCompra"
                                    >
                                        Confirmar Compra
                                    </flux:button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- MODAL ADICIONAR CRÉDITO / PAGAMENTO --}}
        @if($showCreditoModal && $selectedPessoaId)
            @php
                $pessoaSelected = Pessoa::find($selectedPessoaId);
                $contaSelected = $this->getConta($selectedPessoaId);
            @endphp
            <div class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
                <div class="w-full max-w-md bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 shadow-2xl rounded-2xl p-6 space-y-6">
                    <div>
                        <flux:heading size="lg">Lançar Crédito / Pagamento - {{ $pessoaSelected->nom_pessoa }}</flux:heading>
                        <flux:subheading>Adicione créditos antecipados ou quitação de saldo pendente.</flux:subheading>
                    </div>

                    <div class="text-sm bg-zinc-50 dark:bg-zinc-900 p-3 rounded-lg flex justify-between">
                        <span class="text-zinc-500">Saldo Atual:</span>
                        <span class="font-bold {{ $contaSelected->val_saldo < 0 ? 'text-red-600' : ($contaSelected->val_saldo > 0 ? 'text-blue-600' : 'text-zinc-500') }}">
                            R$ {{ number_format($contaSelected->val_saldo, 2, ',', '.') }}
                        </span>
                    </div>

                    <form wire:submit.prevent="registrarCredito" class="space-y-4">
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-800 dark:text-zinc-200">Tipo de Lançamento</label>
                            <div class="flex gap-4">
                                <label class="flex items-center gap-2 text-sm cursor-pointer">
                                    <input type="radio" wire:model="tip_transacao_credito" value="D" class="text-blue-600 border-zinc-300 focus:ring-blue-500">
                                    <span>Crédito / Aporte Antecipado</span>
                                </label>
                                <label class="flex items-center gap-2 text-sm cursor-pointer">
                                    <input type="radio" wire:model="tip_transacao_credito" value="P" class="text-blue-600 border-zinc-300 focus:ring-blue-500">
                                    <span>Quitar Saldo Final</span>
                                </label>
                            </div>
                        </div>

                        <flux:input wire:model="val_aporte" label="Valor (R$)" type="number" step="0.01" min="0.01" placeholder="Ex: 50.00" required />
                        
                        <flux:input wire:model="des_aporte" label="Observação (Opcional)" placeholder="Ex: PIX recebido por Maria" />

                        <flux:separator />

                        <div class="flex justify-end gap-3">
                            <flux:button variant="ghost" wire:click="$set('showCreditoModal', false)">Cancelar</flux:button>
                            <flux:button variant="primary" type="submit" color="green">Salvar Lançamento</flux:button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        {{-- MODAL EXTRATO DE TRANSAÇÕES --}}
        @if($showExtratoModal && $selectedPessoaId)
            @php
                $pessoaSelected = Pessoa::find($selectedPessoaId);
                $contaSelected = $this->getConta($selectedPessoaId);
                $transacoesList = $contaSelected->transacoes()->orderBy('dat_transacao', 'desc')->get();
            @endphp
            <div class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
                <div class="w-full max-w-2xl bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 shadow-2xl rounded-2xl p-6 space-y-6 flex flex-col max-h-[85vh]">
                    <div class="flex justify-between items-start">
                        <div>
                            <flux:heading size="lg">Extrato Financeiro - {{ $pessoaSelected->nom_pessoa }}</flux:heading>
                            <flux:subheading>Extrato detalhado do consumo no Mercadinho durante o evento.</flux:subheading>
                        </div>
                        <flux:button variant="ghost" icon="x-mark" wire:click="$set('showExtratoModal', false)"></flux:button>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 p-4 bg-zinc-50 dark:bg-zinc-900 rounded-xl text-center text-sm">
                        <div class="flex flex-col items-center justify-center p-2">
                            <div class="text-xs text-zinc-400 font-bold uppercase tracking-wider">Total Compras</div>
                            <div class="font-bold text-zinc-800 dark:text-zinc-200 text-base mt-1">
                                R$ {{ number_format($transacoesList->where('tip_transacao', 'C')->sum('val_transacao'), 2, ',', '.') }}
                            </div>
                        </div>
                        <div class="flex flex-col items-center justify-center p-2 border-t sm:border-t-0 sm:border-x border-zinc-200 dark:border-zinc-700">
                            <div class="text-xs text-zinc-400 font-bold uppercase tracking-wider">Total Aportado</div>
                            <div class="font-bold text-green-600 dark:text-green-400 text-base mt-1">
                                R$ {{ number_format($transacoesList->whereIn('tip_transacao', ['D', 'P'])->sum('val_transacao'), 2, ',', '.') }}
                            </div>
                        </div>
                        <div class="flex flex-col items-center justify-center p-2 border-t sm:border-t-0 border-zinc-200 dark:border-zinc-700">
                            <div class="text-xs text-zinc-400 font-bold uppercase tracking-wider">Saldo Atual</div>
                            <div class="font-bold text-base mt-1 {{ $contaSelected->val_saldo < 0 ? 'text-red-600' : ($contaSelected->val_saldo > 0 ? 'text-blue-600' : 'text-zinc-500') }}">
                                R$ {{ number_format($contaSelected->val_saldo, 2, ',', '.') }}
                            </div>
                        </div>
                    </div>

                    <div class="overflow-y-auto flex-1 border border-zinc-200 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-800">
                        @if($transacoesList->isEmpty())
                            <div class="p-8 text-center text-zinc-500 italic text-sm">
                                Nenhuma movimentação financeira registrada nesta conta.
                            </div>
                        @else
                            {{-- Tabela de Histórico (Desktop) --}}
                            <div class="hidden md:block">
                                <flux:table class="vendas-table">
                                    <flux:table.columns>
                                        <flux:table.column class="px-3 py-3 align-middle">Data/Hora</flux:table.column>
                                        <flux:table.column class="px-3 py-3 align-middle">Descrição</flux:table.column>
                                        <flux:table.column class="px-3 py-3 align-middle">Operação</flux:table.column>
                                        <flux:table.column class="px-3 py-3 align-middle">Valor</flux:table.column>
                                        <flux:table.column class="px-3 py-3 align-middle text-right" align="end">Ação</flux:table.column>
                                    </flux:table.columns>

                                    <flux:table.rows>
                                        @foreach($transacoesList as $trans)
                                            @php
                                                $isDebito = $trans->tip_transacao === 'C';
                                                $opLabel = $trans->tip_transacao === 'C' ? 'Compra' : ($trans->tip_transacao === 'D' ? 'Aporte' : 'Pagamento');
                                                $opColor = $trans->tip_transacao === 'C' ? 'red' : ($trans->tip_transacao === 'D' ? 'blue' : 'green');
                                            @endphp
                                            <flux:table.row :key="$trans->idt_transacao">
                                                <flux:table.cell class="px-3 py-3 align-middle text-xs whitespace-nowrap">
                                                    {{ $trans->dat_transacao->format('d/m H:i') }}
                                                </flux:table.cell>
                                                <flux:table.cell class="px-3 py-3 align-middle font-medium text-xs min-w-[120px]">
                                                    {{ $trans->nom_item ?? $trans->des_transacao }}
                                                    @if($trans->qtd_item)
                                                        <span class="text-zinc-400"> (x{{ $trans->qtd_item }})</span>
                                                    @endif
                                                </flux:table.cell>
                                                <flux:table.cell class="px-3 py-3 align-middle">
                                                    <flux:badge :color="$opColor" size="sm" class="font-semibold text-[10px] uppercase">
                                                        {{ $opLabel }}
                                                    </flux:badge>
                                                </flux:table.cell>
                                                <flux:table.cell class="px-3 py-3 align-middle font-bold text-xs whitespace-nowrap">
                                                    <span class="{{ $isDebito ? 'text-zinc-700 dark:text-zinc-300' : 'text-green-600' }}">
                                                        {{ $isDebito ? '-' : '+' }} R$ {{ number_format($trans->val_transacao, 2, ',', '.') }}
                                                    </span>
                                                </flux:table.cell>
                                                <flux:table.cell class="px-3 py-3 align-middle text-right" align="end">
                                                    <flux:button 
                                                        size="xs" 
                                                        variant="ghost" 
                                                        class="text-red-500 hover:text-red-700" 
                                                        icon="arrow-uturn-left"
                                                        wire:confirm="Deseja realmente estornar esta transação? Isso reverterá o saldo da conta e devolverá os itens ao estoque (se aplicável)."
                                                        wire:click="estornarTransacao({{ $trans->idt_transacao }})"
                                                    >
                                                        Estornar
                                                    </flux:button>
                                                </flux:table.cell>
                                            </flux:table.row>
                                        @endforeach
                                    </flux:table.rows>
                                </flux:table>
                            </div>

                            {{-- Feed de Histórico (Mobile) --}}
                            <div class="md:hidden flex flex-col divide-y divide-zinc-100 dark:divide-zinc-700">
                                @foreach($transacoesList as $trans)
                                    @php
                                        $isDebito = $trans->tip_transacao === 'C';
                                        $opLabel = $trans->tip_transacao === 'C' ? 'Compra' : ($trans->tip_transacao === 'D' ? 'Aporte' : 'Pagamento');
                                        $opColor = $trans->tip_transacao === 'C' ? 'red' : ($trans->tip_transacao === 'D' ? 'blue' : 'green');
                                    @endphp
                                    <div class="p-4 space-y-3">
                                        {{-- Linha Superior: Descrição (esquerda) e Valor (direita) --}}
                                        <div class="flex justify-between items-start gap-4">
                                            <div class="flex flex-col">
                                                <span class="font-semibold text-sm text-zinc-900 dark:text-white">
                                                    {{ $trans->nom_item ?? $trans->des_transacao }}
                                                    @if($trans->qtd_item)
                                                        <span class="text-zinc-400 font-normal"> (x{{ $trans->qtd_item }})</span>
                                                    @endif
                                                </span>
                                                <span class="text-xs text-zinc-400 mt-0.5">
                                                    {{ $trans->dat_transacao->format('d/m H:i') }}
                                                </span>
                                            </div>
                                            <div class="font-bold text-sm whitespace-nowrap text-right">
                                                <span class="{{ $isDebito ? 'text-zinc-700 dark:text-zinc-300' : 'text-green-600' }}">
                                                    {{ $isDebito ? '-' : '+' }} R$ {{ number_format($trans->val_transacao, 2, ',', '.') }}
                                                </span>
                                            </div>
                                        </div>

                                        {{-- Linha Inferior: Operação (esquerda) e Ação (direita) --}}
                                        <div class="flex justify-between items-center gap-4">
                                            <div>
                                                <flux:badge :color="$opColor" size="sm" class="font-semibold text-[10px] uppercase">
                                                    {{ $opLabel }}
                                                </flux:badge>
                                            </div>
                                            <div>
                                                <flux:button 
                                                    size="xs" 
                                                    variant="ghost" 
                                                    class="text-red-500 hover:text-red-700 font-medium text-xs flex items-center gap-1" 
                                                    icon="arrow-uturn-left"
                                                    wire:confirm="Deseja realmente estornar esta transação? Isso reverterá o saldo da conta e devolverá os itens ao estoque (se aplicável)."
                                                    wire:click="estornarTransacao({{ $trans->idt_transacao }})"
                                                >
                                                    Estornar
                                                </flux:button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="flex justify-end pt-2">
                        <flux:button variant="ghost" wire:click="$set('showExtratoModal', false)">Fechar</flux:button>
                    </div>
                </div>
            </div>
        @endif
    @else
        {{-- TELA DE SELEÇÃO DO EVENTO --}}
        <div class="max-w-7xl mx-auto space-y-6 py-6 px-4">
            @if($this->eventosAtivos->isEmpty())
                <div class="p-8 text-center bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-2xl shadow-sm italic text-zinc-500">
                    Nenhum evento ativo cadastrado no momento.
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($this->eventosAtivos as $evt)
                        <article 
                            wire:click="selectEvento({{ $evt->idt_evento }})"
                            class="group flex flex-col bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 shadow-sm overflow-hidden hover:shadow-md hover:border-blue-500 hover:ring-1 hover:ring-blue-500 cursor-pointer transition-all duration-300"
                        >
                            <div class="px-5 pt-5 flex justify-between items-start">
                                <span class="px-2 py-1 bg-gray-100 dark:bg-zinc-700 rounded text-[10px] font-black uppercase text-gray-400">
                                    Nº {{ $evt->num_evento }}
                                </span>
                                <x-badge-movimento :sigla="$evt->movimento->des_sigla" />
                            </div>

                            <div class="p-5 flex-grow">
                                <h2 class="text-lg font-bold text-gray-800 dark:text-white mb-3 line-clamp-2 min-h-[3rem] group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                    {{ $evt->des_evento }}
                                </h2>

                                <div class="space-y-3">
                                    <div class="flex items-center text-gray-600 dark:text-gray-300 text-sm">
                                        <x-heroicon-o-calendar class="w-4 h-4 mr-2 text-blue-500" />
                                        <span>{{ $evt->getDataInicioFormatada() }} a {{ $evt->getDataTerminoFormatada() }}</span>
                                    </div>

                                    <div class="flex items-center text-gray-500 dark:text-gray-400 text-xs font-bold uppercase tracking-wider">
                                        <x-heroicon-o-tag class="w-4 h-4 mr-2 shrink-0" />
                                        <span class="flex-1">{{ $evt->tip_evento->label() }}</span>
                                    </div>
                                </div>
                            </div>

                            <footer class="p-4 bg-gray-50 dark:bg-zinc-800/50 border-t border-gray-100 dark:border-zinc-700 mt-auto">
                                <flux:button variant="filled" color="blue" class="w-full pointer-events-none group-hover:bg-blue-700 dark:group-hover:bg-blue-600 transition-colors">
                                    Selecionar Evento
                                </flux:button>
                            </footer>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    @endif
</div>
