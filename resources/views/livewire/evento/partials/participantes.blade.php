<?php

use App\Models\Evento;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

new class extends Component {
    use WithPagination;

    public Evento $evento;
    public string $search = '';

    public function mount(Evento $evento): void
    {
        $this->evento = $evento;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function atualizarTroca(int $participanteId, string $novaCor): void
    {
        $participante = \App\Models\Participante::with('pessoa')->findOrFail($participanteId);
        $participante->update(['tip_cor_troca' => $novaCor]);
        $this->dispatch('notify', message: "A cor da troca de {$participante->pessoa->nom_apelido} agora é " . ucfirst($novaCor) . "!");
    }

    public function excluirParticipante(int $participanteId): void
    {
        $participante = \App\Models\Participante::with('pessoa')->findOrFail($participanteId);
        $nome = $participante->pessoa->nom_pessoa;
        $participante->delete();
        $this->dispatch('notify', message: "Participante {$nome} foi removido com sucesso!");
    }

    public function exportar(): StreamedResponse
    {
        $eventoId = $this->evento->idt_evento;

        $participantes = \App\Models\Participante::where('idt_evento', $eventoId)
            ->with([
                'pessoa.restricoes',
                'pessoa.fichas' => function ($query) use ($eventoId) {
                    $query->where('idt_evento', $eventoId)
                        ->with(['fichaVem', 'fichaSGM']);
                }
            ])
            ->get();

        $cabecalho = [
            'ID Participante',
            'Nome',
            'Apelido',
            'Gênero',
            'Telefone do Participante',
            'Cor da Troca',
            'Restrições',
            'Responsável',
            'Telefone do Responsável',
        ];

        $nomeArquivo = 'participantes_' . \Str::slug($this->evento->nom_evento ?? 'evento') . '_' . now()->format('Y-m-d') . '.csv';

        $response = new StreamedResponse(function () use ($participantes, $cabecalho) {
            $handle = fopen('php://output', 'w');

            // BOM para o Excel reconhecer UTF-8
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, $cabecalho, ';');

            foreach ($participantes as $p) {
                // Obter restrições concatenadas
                $restricoesArr = [];
                foreach ($p->pessoa->restricoes as $r) {
                    $tipoEnum = \App\Enums\TipoRestricao::tryFrom($r->tip_restricao);
                    $tipoLabel = $tipoEnum ? $tipoEnum->label() : $r->tip_restricao;
                    $complemento = $r->pivot->txt_complemento ? " ({$r->pivot->txt_complemento})" : "";
                    $restricoesArr[] = "{$tipoLabel}: {$r->des_restricao}{$complemento}";
                }
                $restricoesStr = implode(' | ', $restricoesArr);

                // Obter responsável e telefone com a mesma prioridade da tabela
                $ficha = $p->pessoa->fichas->first();
                $respName = '';
                $respPhone = '';

                if ($ficha) {
                    if ($ficha->fichaVem) {
                        $fv = $ficha->fichaVem;
                        if (!empty($fv->tel_responsavel)) {
                            $respPhone = $fv->tel_responsavel;
                            $respName = $fv->nom_responsavel;
                        } elseif (!empty($fv->tel_mae)) {
                            $respPhone = $fv->tel_mae;
                            $respName = $fv->nom_mae;
                        } elseif (!empty($fv->tel_pai)) {
                            $respPhone = $fv->tel_pai;
                            $respName = $fv->nom_pai;
                        }
                    } elseif ($ficha->fichaSGM) {
                        $fs = $ficha->fichaSGM;
                        if (!empty($fs->tel_falar_com)) {
                            $respPhone = $fs->tel_falar_com;
                            $respName = $fs->nom_falar_com;
                        } elseif (!empty($fs->tel_mae)) {
                            $respPhone = $fs->tel_mae;
                            $respName = $fs->nom_mae;
                        } elseif (!empty($fs->tel_pai)) {
                            $respPhone = $fs->tel_pai;
                            $respName = $fs->nom_pai;
                        }
                    }
                }

                fputcsv($handle, [
                    $p->idt_participante,
                    $p->pessoa->nom_pessoa,
                    $p->pessoa->nom_apelido ?? '',
                    $p->pessoa->tip_genero instanceof \App\Enums\Genero ? $p->pessoa->tip_genero->value : ($p->pessoa->tip_genero ?? ''),
                    $p->pessoa->tel_pessoa ?? '',
                    $p->tip_cor_troca ?? '',
                    $restricoesStr,
                    $respName,
                    $respPhone,
                ], ';');
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $nomeArquivo . '"');

        return $response;
    }

    public function with(): array
    {
        return [
            'participantes' => \App\Models\Participante::where('idt_evento', $this->evento->idt_evento)
                ->with([
                    'pessoa.foto',
                    'pessoa.restricoes',
                    'pessoa.fichas' => function ($query) {
                        $query->where('idt_evento', $this->evento->idt_evento)
                            ->with(['fichaVem', 'fichaSGM']);
                    }
                ])
                ->when($this->search, function ($query) {
                    $query->whereHas('pessoa', function ($q) {
                        $q->where('nom_pessoa', 'like', '%' . $this->search . '%')
                            ->orWhere('nom_apelido', 'like', '%' . $this->search . '%');
                    });
                })
                ->paginate(10),
        ];
    }
}; ?>

<div class="space-y-4">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <div class="flex items-center gap-3">
                <flux:heading size="lg">Participantes Confirmados</flux:heading>
                <flux:button wire:click="exportar" icon="arrow-down-tray" variant="outline" size="sm" title="Exportar CSV">
                    Exportar
                </flux:button>
            </div>
            <flux:subheading>Gerencie as cores das trocas e informações básicas.</flux:subheading>
        </div>

        <flux:input
            wire:model.live.debounce.300ms="search"
            icon="magnifying-glass"
            placeholder="Nome ou apelido..."
            class="w-full md:max-w-xs"
        />
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>Nome</flux:table.column>
            <flux:table.column>Cor da Troca</flux:table.column>
            <flux:table.column>Restrição</flux:table.column>
            <flux:table.column>Responsável</flux:table.column>
            <flux:table.column align="end">Ações</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($participantes as $p)
                <flux:table.row :key="'participante-'.$p->idt_participante">
                    {{-- Nome --}}
                    <flux:table.cell class="flex items-center gap-3">
                        <flux:avatar
                            src="{{ $p->pessoa->foto?->url_foto ? asset('storage/'.$p->pessoa->foto->url_foto) : '' }}"
                            :initials="substr($p->pessoa->nom_pessoa, 0, 2)"
                            size="sm"
                        />
                        <div>
                            <div class="font-medium text-zinc-900 dark:text-white">{{ $p->pessoa->nom_pessoa }}</div>
                            <div class="text-xs text-zinc-500">{{ $p->pessoa->nom_apelido }}</div>
                        </div>
                    </flux:table.cell>

                    {{-- Cor da Troca --}}
                    <flux:table.cell>
                        <flux:select
                            wire:change="atualizarTroca({{ $p->idt_participante }}, $event.target.value)"
                            size="sm"
                            class="w-32">
                            @foreach (['azul', 'amarela', 'verde', 'vermelha', 'laranja'] as $cor)
                                <option value="{{ $cor }}" @selected(strtolower($p->tip_cor_troca) === $cor)>
                                    {{ ucfirst($cor) }}
                                </option>
                            @endforeach
                        </flux:select>
                    </flux:table.cell>

                    {{-- Restrição --}}
                    <flux:table.cell>
                        <div class="flex flex-wrap gap-1">
                            @forelse ($p->pessoa->restricoes as $restricao)
                                @php
                                    $tipoEnum = \App\Enums\TipoRestricao::tryFrom($restricao->tip_restricao);
                                    $tipoLabel = $tipoEnum ? $tipoEnum->label() : $restricao->tip_restricao;
                                    $tipoCor = $restricao->getCor();
                                    $tooltip = $restricao->pivot->txt_complemento ? " ({$restricao->pivot->txt_complemento})" : "";
                                @endphp
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium {{ $tipoCor }}" title="{{ $restricao->des_restricao }}{{ $tooltip }}">
                                    <span>{{ $tipoEnum?->icon() }}</span>
                                    <span>{{ $tipoLabel }}: {{ $restricao->des_restricao }}</span>
                                </span>
                            @empty
                                <span class="text-xs text-zinc-400">—</span>
                            @endforelse
                        </div>
                    </flux:table.cell>

                    {{-- Responsável --}}
                    <flux:table.cell>
                        @php
                            $ficha = $p->pessoa->fichas->first();
                            $respName = null;
                            $respPhone = null;

                            if ($ficha) {
                                if ($ficha->fichaVem) {
                                    $fv = $ficha->fichaVem;
                                    if (!empty($fv->tel_responsavel)) {
                                        $respPhone = $fv->tel_responsavel;
                                        $respName = $fv->nom_responsavel;
                                    } elseif (!empty($fv->tel_mae)) {
                                        $respPhone = $fv->tel_mae;
                                        $respName = $fv->nom_mae;
                                    } elseif (!empty($fv->tel_pai)) {
                                        $respPhone = $fv->tel_pai;
                                        $respName = $fv->nom_pai;
                                    }
                                } elseif ($ficha->fichaSGM) {
                                    $fs = $ficha->fichaSGM;
                                    if (!empty($fs->tel_falar_com)) {
                                        $respPhone = $fs->tel_falar_com;
                                        $respName = $fs->nom_falar_com;
                                    } elseif (!empty($fs->tel_mae)) {
                                        $respPhone = $fs->tel_mae;
                                        $respName = $fs->nom_mae;
                                    } elseif (!empty($fs->tel_pai)) {
                                        $respPhone = $fs->tel_pai;
                                        $respName = $fs->nom_pai;
                                    }
                                }
                            }
                        @endphp
                        @if ($respPhone)
                            <div class="flex flex-col">
                                <span class="text-xs text-zinc-500 font-medium leading-none mb-1">{{ $respName ?: 'Responsável' }}</span>
                                <span class="text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ $respPhone }}</span>
                            </div>
                        @else
                            <span class="text-xs text-zinc-400">—</span>
                        @endif
                    </flux:table.cell>

                    {{-- Ações --}}
                    <flux:table.cell>
                        <div class="flex justify-end gap-2">
                            <flux:button
                                icon="trash"
                                size="sm"
                                variant="ghost"
                                color="red"
                                wire:click="excluirParticipante({{ $p->idt_participante }})"
                                wire:confirm="Tem certeza que deseja excluir o participante {{ $p->pessoa->nom_pessoa }} deste evento?"
                                tooltip="Excluir"
                            />
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5" class="text-center py-10 text-zinc-500">
                        Nenhum participante encontrado para este evento.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <div class="mt-4">
        {{ $participantes->links(data: ['scrollTo' => false]) }}
    </div>
</div>
