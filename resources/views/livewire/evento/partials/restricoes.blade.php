<?php

use App\Models\Evento;
use App\Models\Participante;
use App\Models\Trabalhador;
use Livewire\Volt\Component;
use Livewire\Attributes\Computed;

new class extends Component {
    public Evento $evento;

    public function mount(Evento $evento): void
    {
        $this->evento = $evento;
    }

    #[Computed]
    public function restricoes(): array
    {
        $eventoSelecionadoId = $this->evento->idt_evento;
        $restricoes = [];

        // 1. Participantes Aprovados com Restrição
        $participantes = Participante::where('idt_evento', $eventoSelecionadoId)
            ->whereHas('pessoa.restricoes')
            ->with(['pessoa.restricoes'])
            ->get();

        foreach ($participantes as $part) {
            foreach ($part->pessoa->restricoes as $restricao) {
                $txt_complemento = $restricao->pivot?->txt_complemento;
                $restricoes[] = (object) [
                    'nome' => $part->pessoa->nom_pessoa . ($part->pessoa->nom_apelido ? " ({$part->pessoa->nom_apelido})" : ""),
                    'tipo_cadastro' => 'Participante',
                    'troca' => $part->tip_cor_troca ? ucfirst($part->tip_cor_troca) : 'Geral',
                    'equipe' => '-',
                    'tipo_restricao' => $restricao->getTipo(),
                    'tipo_restricao_cor' => $restricao->getCor(),
                    'desc_restricao' => $restricao->des_restricao . ($txt_complemento ? " — " . $txt_complemento : ""),
                ];
            }
        }

        // 2. Trabalhadores Aprovados com Restrição
        $trabalhadores = Trabalhador::where('idt_evento', $eventoSelecionadoId)
            ->whereHas('pessoa.restricoes')
            ->with(['pessoa.restricoes', 'equipe'])
            ->get();

        foreach ($trabalhadores as $trab) {
            foreach ($trab->pessoa->restricoes as $restricao) {
                $txt_complemento = $restricao->pivot?->txt_complemento;
                $restricoes[] = (object) [
                    'nome' => $trab->pessoa->nom_pessoa . ($trab->pessoa->nom_apelido ? " ({$trab->pessoa->nom_apelido})" : ""),
                    'tipo_cadastro' => 'Trabalhador',
                    'troca' => '-',
                    'equipe' => $trab->equipe ? $trab->equipe->des_grupo : 'Sem Equipe',
                    'tipo_restricao' => $restricao->getTipo(),
                    'tipo_restricao_cor' => $restricao->getCor(),
                    'desc_restricao' => $restricao->des_restricao . ($txt_complemento ? " — " . $txt_complemento : ""),
                ];
            }
        }

        return $restricoes;
    }
}; ?>

<div>
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
            <flux:heading size="lg">Restrições de Saúde</flux:heading>
            <flux:subheading>Visualize as restrições alimentares e médicas dos participantes e trabalhadores deste evento.</flux:subheading>
        </div>
        <div class="print:hidden">
            <flux:button onclick="window.print()" icon="printer" variant="filled">
                Imprimir
            </flux:button>
        </div>
    </div>

    <div class="mt-6 border border-zinc-200 dark:border-zinc-700 rounded-xl overflow-hidden shadow-sm bg-white dark:bg-zinc-800 print:border-none print:shadow-none">
        @if (count($this->restricoes) > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm border-collapse">
                    <thead class="bg-zinc-50 dark:bg-zinc-700/50 border-b border-zinc-200 dark:border-zinc-700">
                        <tr>
                            <th class="p-4 font-bold text-zinc-950 dark:text-white print:py-2">Nome</th>
                            <th class="p-4 font-bold text-zinc-950 dark:text-white print:py-2">Tipo</th>
                            <th class="p-4 font-bold text-zinc-950 dark:text-white print:py-2">Troca</th>
                            <th class="p-4 font-bold text-zinc-950 dark:text-white print:py-2">Equipe</th>
                            <th class="p-4 font-bold text-zinc-950 dark:text-white print:py-2">Restrição</th>
                            <th class="p-4 font-bold text-zinc-950 dark:text-white print:py-2">Descrição / Detalhes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach ($this->restricoes as $r)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition print:hover:bg-transparent">
                                <td class="p-4 font-medium text-zinc-900 dark:text-zinc-100 print:py-2">
                                    {{ $r->nome }}
                                </td>
                                <td class="p-4 text-zinc-650 dark:text-zinc-400 print:py-2">
                                    <flux:badge size="sm" inset="top bottom" color="zinc" class="print:p-0 print:bg-transparent print:text-black">
                                        {{ $r->tipo_cadastro }}
                                    </flux:badge>
                                </td>
                                <td class="p-4 text-zinc-800 dark:text-zinc-200 font-semibold print:py-2">
                                    {{ $r->troca }}
                                </td>
                                <td class="p-4 text-zinc-600 dark:text-zinc-400 print:py-2">
                                    {{ $r->equipe }}
                                </td>
                                <td class="p-4 print:py-2">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-bold {{ $r->tipo_restricao_cor }} print:p-0 print:bg-transparent print:text-black">
                                        {{ $r->tipo_restricao }}
                                    </span>
                                </td>
                                <td class="p-4 text-zinc-900 dark:text-zinc-100 print:py-2">
                                    {{ $r->desc_restricao }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="flex flex-col items-center justify-center text-center p-12">
                <flux:icon.shield-check class="w-16 h-16 text-green-500 mb-4" />
                <p class="text-xl font-bold text-zinc-800 dark:text-zinc-200">Nenhuma restrição identificada!</p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-2 max-w-md">
                    Todos os participantes e trabalhadores analisados para este evento não possuem nenhuma restrição cadastrada.
                </p>
            </div>
        @endif
    </div>

    <style>
        @media print {
            body {
                background: white !important;
                color: black !important;
            }
            .dark {
                background: white !important;
                color: black !important;
            }
            thead {
                background-color: #f4f4f5 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</div>
