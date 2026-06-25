<?php

use App\Models\Evento;
use Livewire\Volt\Component;

new class extends Component {
    public Evento $evento;

    public string|float|null $valReceita  = null;
    public string|float|null $valDespesa  = null;
    public ?string $txtRelatorio = null;

    public function mount(Evento $evento): void
    {
        $this->evento       = $evento;
        $this->valReceita   = $evento->val_receita;
        $this->valDespesa   = $evento->val_despesa;
        $this->txtRelatorio = $evento->txt_relatorio;
    }

    public function saveFinanceiro(): void
    {
        $this->validate([
            'valReceita'   => 'nullable|numeric|min:0',
            'valDespesa'   => 'nullable|numeric|min:0',
            'txtRelatorio' => 'nullable|string|max:3000',
        ]);

        $this->evento->update([
            'val_receita'   => $this->valReceita,
            'val_despesa'   => $this->valDespesa,
            'txt_relatorio' => $this->txtRelatorio,
        ]);

        $this->dispatch('notify', message: 'Prestação de contas salva com sucesso!', type: 'sucesso');
    }
}; ?>

<form wire:submit="saveFinanceiro" class="space-y-6">
    <div>
        <flux:heading size="lg">Financeiro e Prestação de Contas</flux:heading>
        <flux:subheading>Registre receita total, despesa total e observações relevantes para outras equipes no futuro.</flux:subheading>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <flux:input
            wire:model="valReceita"
            type="number"
            step="0.01"
            min="0"
            label="Receita (R$)"
            placeholder="0,00"
        />
        <flux:input
            wire:model="valDespesa"
            type="number"
            step="0.01"
            min="0"
            label="Despesa (R$)"
            placeholder="0,00"
        />
    </div>

    {{-- Saldo calculado em tempo real --}}
    @php
        $receita = (float) ($valReceita ?? 0);
        $despesa = (float) ($valDespesa ?? 0);
        $saldo   = $receita - $despesa;
    @endphp
    <div class="flex items-center gap-3 px-4 py-3 rounded-lg border {{ $saldo >= 0 ? 'bg-green-50 border-green-200 dark:bg-green-900/20 dark:border-green-800' : 'bg-red-50 border-red-200 dark:bg-red-900/20 dark:border-red-800' }}">
        <flux:icon name="calculator" variant="outline" class="size-5 {{ $saldo >= 0 ? 'text-green-600' : 'text-red-600' }}" />
        <div>
            <flux:text size="sm" class="font-bold uppercase text-xs text-zinc-500 tracking-wide">Saldo Previsto</flux:text>
            <flux:heading size="sm" class="{{ $saldo >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                R$ {{ number_format($saldo, 2, ',', '.') }}
            </flux:heading>
        </div>
    </div>

    <flux:textarea
        wire:model="txtRelatorio"
        label="Relatório sobre o evento"
        rows="5"
        placeholder="X pessoas participaram&#10;x kg de carne comprados - x kg de carne sobraram&#10;x livros em consignação - tema XXX não agradou"
    />

    <div class="flex items-center justify-between">
        <flux:button variant="primary" type="submit" icon="check">
            Salvar Prestação
        </flux:button>

        @if($evento->txt_relatorio)
            <flux:badge color="green" icon="check-circle">Já registrada</flux:badge>
        @else
            <flux:badge color="yellow" icon="clock">Pendente</flux:badge>
        @endif
    </div>
</form>
