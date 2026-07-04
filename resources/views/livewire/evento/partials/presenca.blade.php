<?php

/**
 * PRESENÇA
 * Campos necessários (migration):
 *   Schema::table('participante', fn($t) => $t->boolean('ind_presente')->default(false));
 *   Schema::table('trabalhador',  fn($t) => $t->boolean('ind_presente')->default(false));
 */

use App\Models\Evento;
use App\Models\Participante;
use App\Models\Trabalhador;
use Livewire\Volt\Component;
use Livewire\Attributes\Computed;

new class extends Component {
    public Evento $evento;
    public string $search     = '';
    public string $equipeFiltroId = '';
    public string $grupoFiltro = '';

    public function mount(Evento $evento): void
    {
        $this->evento = $evento;
    }

    public function togglePresenca(int $id, string $tipo): void
    {
        if ($tipo === 'participante') {
            $registro = Participante::findOrFail($id);
        } else {
            $registro = Trabalhador::findOrFail($id);
        }
        $registro->ind_presente = ! $registro->ind_presente;
        $registro->save();
        // Invalida o cache da propriedade computada
        unset($this->lista);
    }

    // Propriedade computada — não é serializada pelo Livewire,
    // resolve apenas na hora de renderizar a view.
    #[Computed]
    public function lista(): array
    {
        $search = $this->search;
        $items  = [];

        // 1. Carregar participantes se não houver filtro de equipe
        if (!$this->equipeFiltroId) {
            $participantes = Participante::where('idt_evento', $this->evento->idt_evento)
                ->with('pessoa')
                ->when($this->grupoFiltro, fn($q) => $q->where('tip_cor_troca', $this->grupoFiltro))
                ->when($search, fn($q) => $q->whereHas('pessoa', fn($q2) =>
                    $q2->where('nom_pessoa', 'like', "%{$search}%")
                       ->orWhere('nom_apelido', 'like', "%{$search}%")
                ))
                ->get();

            foreach ($participantes as $p) {
                $corEnum = $p->tip_cor_troca ? \App\Enums\CorTroca::tryFrom(strtolower($p->tip_cor_troca)) : null;
                $grupo_cor_class = match ($corEnum) {
                    \App\Enums\CorTroca::VERMELHA => 'text-red-600 dark:text-red-400 font-semibold',
                    \App\Enums\CorTroca::AZUL => 'text-blue-600 dark:text-blue-400 font-semibold',
                    \App\Enums\CorTroca::VERDE => 'text-green-600 dark:text-green-400 font-semibold',
                    \App\Enums\CorTroca::AMARELA => 'text-amber-600 dark:text-amber-400 font-semibold',
                    \App\Enums\CorTroca::LARANJA => 'text-orange-600 dark:text-orange-400 font-semibold',
                    default => 'text-zinc-500 dark:text-zinc-400 font-medium',
                };

                $items[] = [
                    'id'              => $p->idt_participante,
                    'tipo'            => 'participante',
                    'nome'            => $p->pessoa->nom_pessoa ?? '',
                    'apelido'         => $p->pessoa->nom_apelido ?? '',
                    'telefone'        => $p->pessoa->tel_pessoa ?? '',
                    'nascimento'      => $p->pessoa->dat_nascimento,
                    'ind_presente'    => (bool) $p->ind_presente,
                    'grupo'           => $p->tip_cor_troca ? ucfirst($p->tip_cor_troca) : 'Geral',
                    'grupo_cor_class' => $grupo_cor_class,
                    'ind_coordenador' => false,
                ];
            }
        }

        // 2. Carregar trabalhadores se não houver filtro de grupo
        if (!$this->grupoFiltro) {
            $trabalhadores = Trabalhador::where('idt_evento', $this->evento->idt_evento)
                ->whereHas('pessoa')
                ->with(['pessoa', 'equipe'])
                ->when($this->equipeFiltroId, fn($q) => $q->where('idt_equipe', $this->equipeFiltroId))
                ->when($search, fn($q) => $q->whereHas('pessoa', fn($q2) =>
                    $q2->where('nom_pessoa', 'like', "%{$search}%")
                       ->orWhere('nom_apelido', 'like', "%{$search}%")
                ))
                ->get();

            foreach ($trabalhadores as $t) {
                $items[] = [
                    'id'              => $t->idt_trabalhador,
                    'tipo'            => 'trabalhador',
                    'nome'            => $t->pessoa->nom_pessoa ?? '',
                    'apelido'         => $t->pessoa->nom_apelido ?? '',
                    'telefone'        => $t->pessoa->tel_pessoa ?? '',
                    'nascimento'      => $t->pessoa->dat_nascimento,
                    'ind_presente'    => (bool) $t->ind_presente,
                    'grupo'           => $t->equipe ? $t->equipe->des_grupo : 'Sem Equipe',
                    'grupo_cor_class' => 'text-zinc-500 dark:text-zinc-400 font-medium',
                    'ind_coordenador' => (bool) $t->ind_coordenador,
                ];
            }
        }

        usort($items, fn($a, $b) => strcmp($a['nome'], $b['nome']));
        return $items;
    }

    public function with(): array
    {
        return [
            'equipes' => \App\Models\TipoEquipe::where('idt_movimento', $this->evento->movimento->idt_movimento)->get(),
        ];
    }
}; ?>

<div class="space-y-6">

    {{-- Cabeçalho --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
        <div>
            <flux:heading size="lg">Controle de Presença</flux:heading>
            @php
                $lista      = $this->lista;
                $total      = count($lista);
                $presentes  = count(array_filter($lista, fn($i) => $i['ind_presente']));
            @endphp
            <flux:subheading>{{ $presentes }} presente(s) de {{ $total }} pessoa(s)</flux:subheading>
        </div>

        <div class="flex flex-col sm:flex-row gap-2 w-full md:w-auto items-end">
            <flux:select label="Equipe" wire:model.live="equipeFiltroId" placeholder="Todas" class="w-full sm:w-48">
                <option value="">Todas as equipes</option>
                @foreach ($equipes as $eq)
                    <option value="{{ $eq->idt_equipe }}">{{ $eq->des_grupo }}</option>
                @endforeach
            </flux:select>

            <flux:select label="Grupo" wire:model.live="grupoFiltro" placeholder="Todos" class="w-full sm:w-48">
                <option value="">Todos os grupos</option>
                <option value="vermelha">Vermelha</option>
                <option value="azul">Azul</option>
                <option value="verde">Verde</option>
                <option value="amarela">Amarela</option>
                <option value="laranja">Laranja</option>
            </flux:select>

            <flux:input
                label="Busca"
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="Nome ou apelido..."
                class="w-full sm:w-56"
            />
        </div>
    </div>

    {{-- Barra de progresso --}}
    @if($total > 0)
        @php $pct = round(($presentes / $total) * 100) @endphp
        <div>
            <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-2">
                <div class="bg-green-500 h-2 rounded-full transition-all duration-500" style="width: {{ $pct }}%"></div>
            </div>
            <flux:text size="sm" class="text-zinc-400 mt-1">{{ $pct }}% de presença confirmada</flux:text>
        </div>
    @endif

    {{-- Tabela --}}
    <flux:table>
        <flux:table.columns>
            <flux:table.column>Pessoa</flux:table.column>
            <flux:table.column>Telefone</flux:table.column>
            <flux:table.column>Tipo</flux:table.column>
            <flux:table.column>Menor de Idade</flux:table.column>
            <flux:table.column>Presente</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->lista as $item)
                @php
                    $nasc  = $item['nascimento'] ? \Carbon\Carbon::parse($item['nascimento']) : null;
                    $menor = $nasc && $nasc->age < 18;
                @endphp
                <flux:table.row :key="$item['tipo'].'-'.$item['id']">

                    <flux:table.cell>
                        <div class="flex items-center gap-3">
                            <flux:avatar :initials="mb_substr($item['nome'] ?? '?', 0, 2)" size="sm" />
                            <div>
                                <div class="font-medium text-zinc-900 dark:text-white flex items-center gap-2">
                                    <span>{{ $item['nome'] }}</span>
                                    @if ($item['tipo'] === 'trabalhador' && $item['ind_coordenador'])
                                        <flux:icon.star variant="solid" class="size-4 text-yellow-500 shrink-0 cursor-default" title="Coordenador da Equipe" />
                                    @endif
                                </div>
                                @if($item['apelido'])
                                    <div class="text-xs text-zinc-500">{{ $item['apelido'] }}</div>
                                @endif
                                @if($item['grupo'])
                                    <div class="text-xs {{ $item['grupo_cor_class'] }} mt-0.5 leading-none">{{ $item['grupo'] }}</div>
                                @endif
                            </div>
                        </div>
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:text size="sm">{{ $item['telefone'] ?: '—' }}</flux:text>
                    </flux:table.cell>

                    <flux:table.cell>
                        @if($item['tipo'] === 'trabalhador')
                            <flux:badge size="sm" color="purple">Trabalhador</flux:badge>
                        @else
                            <flux:badge size="sm" color="blue">Participante</flux:badge>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell>
                        @if($menor)
                            <flux:badge size="sm" color="yellow">Sim</flux:badge>
                        @else
                            <flux:badge size="sm" color="zinc">Não</flux:badge>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:switch
                            :checked="$item['ind_presente']"
                            wire:click="togglePresenca({{ $item['id'] }}, '{{ $item['tipo'] }}')"
                            color="green"
                        />
                    </flux:table.cell>

                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5" class="text-center py-10 text-zinc-500">
                        Nenhuma pessoa encontrada.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

</div>
