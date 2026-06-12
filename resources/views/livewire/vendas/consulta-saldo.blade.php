<?php

use App\Models\Pessoa;
use App\Models\Conta;
use App\Models\Evento;
use Livewire\Volt\Component;
use Livewire\Attributes\Computed;

new class extends Component {
    public string $cpf = '';
    public string $dat_nascimento = '';
    public string $idt_evento = '';
    
    public ?array $resultado = null;
    public ?string $erro = null;

    protected $rules = [
        'cpf' => 'required',
        'dat_nascimento' => 'required|date',
        'idt_evento' => 'required|exists:evento,idt_evento',
    ];

    #[Computed]
    public function eventosAtivos()
    {
        return Evento::where('dat_termino', '>=', today())
            ->orderBy('dat_inicio', 'desc')
            ->get();
    }

    public function consultar(): void
    {
        $this->validate();
        $this->resultado = null;
        $this->erro = null;

        // Limpa formatação do CPF para buscar no banco
        $cpfLimpo = preg_replace('/\D/', '', $this->cpf);

        // Busca a pessoa pelo CPF e Data de Nascimento
        $pessoa = Pessoa::where('num_cpf_pessoa', $cpfLimpo)
            ->where('dat_nascimento', $this->dat_nascimento)
            ->first();

        if (!$pessoa) {
            $this->erro = 'Nenhuma pessoa encontrada com o CPF e Data de Nascimento informados.';
            return;
        }

        // Busca a conta vinculada a essa pessoa para o evento selecionado
        $conta = Conta::with(['evento', 'transacoes' => fn($q) => $q->orderBy('dat_transacao', 'desc')])
            ->where('idt_pessoa', $pessoa->idt_pessoa)
            ->where('idt_evento', $this->idt_evento)
            ->first();

        if (!$conta) {
            $this->erro = 'Nenhuma conta do Mercadinho foi encontrada para esta pessoa no evento selecionado.';
            return;
        }

        $this->resultado = [
            'pessoa' => $pessoa,
            'conta' => $conta,
            'transacoes' => $conta->transacoes,
        ];
    }

    public function limpar(): void
    {
        $this->cpf = '';
        $this->dat_nascimento = '';
        $this->idt_evento = '';
        $this->resultado = null;
        $this->erro = null;
    }
}; ?>

<div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-gray-200 dark:border-zinc-700 overflow-hidden flex flex-col h-full">
    <header class="px-6 py-4 border-b border-gray-100 dark:border-zinc-700 flex justify-between items-center bg-gray-50/50 dark:bg-zinc-800/50">
        <h2 class="font-bold text-gray-800 dark:text-white flex items-center gap-2">
            <x-heroicon-s-magnifying-glass class="w-5 h-5 text-blue-600" />
            Consulta de Saldo - Mercadinho
        </h2>
        @if($resultado)
            <button wire:click="limpar" class="text-xs text-blue-600 dark:text-blue-400 font-bold hover:underline">
                Nova Consulta
            </button>
        @endif
    </header>

    <div class="p-6 flex-1 flex flex-col justify-between">
        @if(!$resultado)
            {{-- Formulário de Consulta --}}
            <form wire:submit.prevent="consultar" class="space-y-4 my-auto">
                {{-- Dropdown de Eventos Ativos --}}
                <flux:select wire:model="idt_evento" label="Evento" placeholder="Selecione o evento..." required>
                    @forelse($this->eventosAtivos as $ev)
                        <flux:select.option value="{{ $ev->idt_evento }}">{{ $ev->des_evento }}</flux:select.option>
                    @empty
                        <flux:select.option value="" disabled>Nenhum evento ativo no momento</flux:select.option>
                    @endforelse
                </flux:select>

                <flux:input 
                    wire:model="cpf" 
                    label="CPF" 
                    placeholder="000.000.000-00" 
                    x-mask="999.999.999-99" 
                    required 
                />

                <flux:input 
                    wire:model="dat_nascimento" 
                    label="Data de Nascimento" 
                    type="date" 
                    required 
                />

                @if($erro)
                    <div class="text-xs font-semibold text-red-600 dark:text-red-400 p-2.5 bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-900/40 rounded-xl">
                        {{ $erro }}
                    </div>
                @endif

                <flux:button variant="primary" type="submit" class="w-full">
                    Consultar Consumo
                </flux:button>
            </form>
        @else
            {{-- Resultado da Consulta --}}
            <div class="space-y-5 flex-1 overflow-y-auto max-h-[400px] pr-1">
                {{-- Resumo da Conta --}}
                <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-700 text-center space-y-2">
                    <div class="text-xs font-bold text-zinc-400 uppercase tracking-wider">
                        {{ $resultado['conta']->evento->des_evento }}
                    </div>
                    <div class="font-bold text-zinc-800 dark:text-white text-sm">
                        {{ $resultado['pessoa']->nom_pessoa }}
                    </div>
                    <div class="text-2xl font-black {{ $resultado['conta']->val_saldo < 0 ? 'text-red-600' : ($resultado['conta']->val_saldo > 0 ? 'text-blue-600' : 'text-zinc-500') }}">
                        R$ {{ number_format($resultado['conta']->val_saldo, 2, ',', '.') }}
                    </div>
                    <div class="text-[10px] text-zinc-400">
                        Saldo atualizado em tempo real
                    </div>
                </div>

                {{-- Extrato --}}
                <div class="space-y-2">
                    <div class="text-xs font-bold text-zinc-500 uppercase tracking-wider">Histórico de Consumo</div>
                    <div class="border border-zinc-200 dark:border-zinc-700 rounded-xl overflow-hidden bg-white dark:bg-zinc-800 text-xs">
                        @if($resultado['transacoes']->isEmpty())
                            <div class="p-4 text-center text-zinc-450 italic">
                                Nenhuma compra registrada.
                            </div>
                        @else
                            <flux:table>
                                <flux:table.columns>
                                    <flux:table.column>Data</flux:table.column>
                                    <flux:table.column>Item</flux:table.column>
                                    <flux:table.column>Valor</flux:table.column>
                                </flux:table.columns>

                                <flux:table.rows>
                                    @foreach($resultado['transacoes'] as $trans)
                                        @php
                                            $isDebito = $trans->tip_transacao === 'C';
                                        @endphp
                                        <flux:table.row>
                                            <flux:table.cell class="text-[10px]">
                                                {{ $trans->dat_transacao->format('d/m H:i') }}
                                            </flux:table.cell>
                                            <flux:table.cell class="font-semibold">
                                                {{ $trans->nom_item ?? $trans->des_transacao }}
                                                @if($trans->qtd_item)
                                                    <span class="text-zinc-400 font-normal"> (x{{ $trans->qtd_item }})</span>
                                                @endif
                                            </flux:table.cell>
                                            <flux:table.cell class="font-bold">
                                                <span class="{{ $isDebito ? 'text-zinc-700 dark:text-zinc-300' : 'text-green-600' }}">
                                                    {{ $isDebito ? '-' : '+' }} R$ {{ number_format($trans->val_transacao, 2, ',', '.') }}
                                                </span>
                                            </flux:table.cell>
                                        </flux:table.row>
                                    @endforeach
                                </flux:table.rows>
                            </flux:table>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
