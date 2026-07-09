<?php

use App\Models\Pessoa;
use App\Models\Conta;
use App\Models\Evento;
use Livewire\Volt\Component;
use Livewire\Attributes\Computed;

new class extends Component {
    public string $idt_evento = '';
    
    public ?array $resultado = null;
    public ?string $erro = null;

    protected $rules = [
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

        $user = auth()->user();
        if (!$user) {
            $this->erro = 'Usuário não autenticado.';
            return;
        }

        $pessoaLogada = $user->pessoa;
        if (!$pessoaLogada) {
            $this->erro = 'Nenhuma pessoa associada ao seu usuário.';
            return;
        }

        // Limpa formatação do CPF para buscar no banco
        $cpfLimpo = preg_replace('/\D/', '', $pessoaLogada->num_cpf_pessoa ?? '');

        if (empty($cpfLimpo)) {
            $this->erro = 'CPF não cadastrado para o seu usuário.';
            return;
        }

        // Busca a pessoa pelo CPF da pessoa logada
        $pessoa = Pessoa::where('num_cpf_pessoa', $cpfLimpo)
            ->first();

        if (!$pessoa) {
            $this->erro = 'Nenhuma pessoa encontrada com o CPF do usuário logado.';
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
        $this->idt_evento = '';
        $this->resultado = null;
        $this->erro = null;
    }
}; ?>

<div class="flex flex-col h-full">
    <div class="px-6 py-4 flex justify-between items-center">
        <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
            <x-heroicon-s-banknotes class="w-5 h-5 text-emerald-600" />
            Consulta de Saldo
        </h3>
        @if($resultado)
            <button wire:click="limpar" class="text-xs text-blue-600 dark:text-blue-400 font-bold hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500 rounded">
                Nova Consulta
            </button>
        @endif
    </div>

    <div class="px-6 pb-6 flex-1 flex flex-col justify-between">
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

                @if($erro)
                    <div class="text-xs font-semibold text-red-600 dark:text-red-400 p-2.5 bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-900/40 rounded-xl">
                        {{ $erro }}
                    </div>
                @endif

                <flux:button variant="primary" type="submit" class="w-full bg-gradient-to-r from-indigo-500 to-purple-500 hover:from-indigo-600 hover:to-purple-600 border-none shadow-md" icon="magnifying-glass">
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
