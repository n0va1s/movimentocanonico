<?php

use App\Models\Evento;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Enums\CorTroca;

new class extends Component {
    use WithPagination;

    public Evento $evento;
    public string $search = '';
    public string $corTroca = '';
    public string $sortColumn = 'nom_pessoa';
    public string $sortDirection = 'asc';

    public ?int $parentescoParticipanteId = null;
    public string $desParentescoInput = '';

    public function mount(Evento $evento): void
    {
        $this->evento = $evento;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCorTroca(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $column): void
    {
        $allowedColumns = ['nom_pessoa', 'dat_nascimento', 'des_endereco', 'tip_cor_troca', 'tam_camiseta', 'responsavel'];
        if (! in_array($column, $allowedColumns)) {
            return;
        }

        if ($this->sortColumn === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortColumn = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    protected function applySorting($query)
    {
        switch ($this->sortColumn) {
            case 'dat_nascimento':
                return $query->orderBy('pessoa.dat_nascimento', $this->sortDirection);
            case 'des_endereco':
                return $query->orderBy('pessoa.des_endereco', $this->sortDirection);
            case 'tip_cor_troca':
                return $query->orderBy('participante.tip_cor_troca', $this->sortDirection);
            case 'tam_camiseta':
                return $query->orderBy('pessoa.tam_camiseta', $this->sortDirection);
            case 'responsavel':
                $eventoId = $this->evento->idt_evento;
                return $query
                    ->leftJoin('ficha', function ($join) use ($eventoId) {
                        $join->on('ficha.idt_pessoa', '=', 'pessoa.idt_pessoa')
                            ->where('ficha.idt_evento', '=', $eventoId)
                            ->whereNull('ficha.deleted_at');
                    })
                    ->leftJoin('ficha_vem', 'ficha_vem.idt_ficha', '=', 'ficha.idt_ficha')
                    ->leftJoin('ficha_sgm', 'ficha_sgm.idt_ficha', '=', 'ficha.idt_ficha')
                    ->orderByRaw("
                        COALESCE(
                            NULLIF(ficha_vem.nom_responsavel, ''),
                            NULLIF(ficha_vem.nom_mae, ''),
                            NULLIF(ficha_vem.nom_pai, ''),
                            NULLIF(ficha_sgm.nom_falar_com, ''),
                            NULLIF(ficha_sgm.nom_mae, ''),
                            NULLIF(ficha_sgm.nom_pai, ''),
                            'ZZZZZZ'
                        ) {$this->sortDirection}
                    ");
            case 'nom_pessoa':
            default:
                return $query->orderBy('pessoa.nom_pessoa', $this->sortDirection);
        }
    }

    public function toggleCorTroca(string $cor): void
    {
        if (strtolower($this->corTroca) === strtolower($cor)) {
            $this->corTroca = '';
        } else {
            $this->corTroca = $cor;
        }
        $this->resetPage();
    }

    public function atualizarTroca(int $participanteId, string $novaCor): void
    {
        $participante = \App\Models\Participante::with('pessoa')->findOrFail($participanteId);
        $novaCorSalvar = empty($novaCor) ? null : strtolower(trim($novaCor));
        $participante->update(['tip_cor_troca' => $novaCorSalvar]);
        
        $corEnum = $novaCorSalvar ? CorTroca::tryFrom($novaCorSalvar) : null;
        $corLabel = $corEnum ? $corEnum->label() : 'Nenhuma';
        
        $this->dispatch('notify', message: "A cor da troca de {$participante->pessoa->nom_apelido} agora é {$corLabel}!");
    }

    public function abrirParentesco(int $participanteId): void
    {
        $participante = \App\Models\Participante::findOrFail($participanteId);
        $this->parentescoParticipanteId = $participante->idt_participante;
        $this->desParentescoInput = $participante->des_parentesco ?? '';
        $this->modal('modal-parentesco')->show();
    }

    public function salvarParentesco(): void
    {
        if (! $this->parentescoParticipanteId) {
            return;
        }

        $participante = \App\Models\Participante::with('pessoa')->findOrFail($this->parentescoParticipanteId);
        $novoParentesco = trim($this->desParentescoInput) ?: null;
        $participante->update(['des_parentesco' => $novoParentesco]);

        $this->modal('modal-parentesco')->close();
        $this->parentescoParticipanteId = null;
        $this->desParentescoInput = '';

        $this->dispatch('notify', message: "Parentesco de {$participante->pessoa->nom_apelido} atualizado!");
    }

    public function removerParentesco(): void
    {
        if (! $this->parentescoParticipanteId) {
            return;
        }

        $participante = \App\Models\Participante::with('pessoa')->findOrFail($this->parentescoParticipanteId);
        $participante->update(['des_parentesco' => null]);

        $this->modal('modal-parentesco')->close();
        $this->parentescoParticipanteId = null;
        $this->desParentescoInput = '';

        $this->dispatch('notify', message: "Parentesco de {$participante->pessoa->nom_apelido} removido!");
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

        $participantesQuery = \App\Models\Participante::query()
            ->select('participante.*')
            ->join('pessoa', 'participante.idt_pessoa', '=', 'pessoa.idt_pessoa')
            ->where('participante.idt_evento', $eventoId)
            ->when($this->corTroca, function ($query) {
                $query->where('participante.tip_cor_troca', $this->corTroca);
            })
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('pessoa.nom_pessoa', 'like', '%' . $this->search . '%')
                        ->orWhere('pessoa.nom_apelido', 'like', '%' . $this->search . '%');
                });
            })
            ->with([
                'pessoa.restricoes',
                'pessoa.fichas' => function ($query) use ($eventoId) {
                    $query->where('idt_evento', $eventoId)
                        ->with(['fichaVem', 'fichaSGM']);
                }
            ]);

        $this->applySorting($participantesQuery);

        $participantes = $participantesQuery->get();

        $cabecalho = [
            'ID Participante',
            'Nome',
            'Apelido',
            'Parentesco',
            'Data de Nascimento',
            'Idade',
            'Endereço',
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

                $datNascStr = $p->pessoa->dat_nascimento ? $p->pessoa->dat_nascimento->format('d/m/Y') : '';
                $idadeStr = $p->pessoa->dat_nascimento ? $p->pessoa->dat_nascimento->age . ' anos' : '';

                fputcsv($handle, [
                    $p->idt_participante,
                    $p->pessoa->nom_pessoa,
                    $p->pessoa->nom_apelido ?? '',
                    $p->des_parentesco ?? '',
                    $datNascStr,
                    $idadeStr,
                    $p->pessoa->des_endereco ?? '',
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

    /**
     * Retorna a contagem de participantes agrupada por cor da troca.
     */
    public function getQuantidadePorCor(): array
    {
        return \App\Models\Participante::where('idt_evento', $this->evento->idt_evento)
            ->select('tip_cor_troca', \DB::raw('count(*) as total'))
            ->groupBy('tip_cor_troca')
            ->pluck('total', 'tip_cor_troca')
            ->toArray();
    }

    public function with(): array
    {
        $query = \App\Models\Participante::query()
            ->select('participante.*')
            ->join('pessoa', 'participante.idt_pessoa', '=', 'pessoa.idt_pessoa')
            ->where('participante.idt_evento', $this->evento->idt_evento)
            ->with([
                'pessoa.foto',
                'pessoa.restricoes',
                'pessoa.fichas' => function ($query) {
                    $query->where('idt_evento', $this->evento->idt_evento)
                        ->with(['fichaVem', 'fichaSGM']);
                }
            ])
            ->when($this->corTroca, function ($query) {
                $query->where('participante.tip_cor_troca', $this->corTroca);
            })
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('pessoa.nom_pessoa', 'like', '%' . $this->search . '%')
                        ->orWhere('pessoa.nom_apelido', 'like', '%' . $this->search . '%');
                });
            });

        $this->applySorting($query);

        return [
            'participantes' => $query->paginate(10),
        ];
    }
}; ?>

<div class="space-y-4">
    @php
        $quantidades = $this->getQuantidadePorCor();
        $totalParticipantes = \App\Models\Participante::where('idt_evento', $this->evento->idt_evento)->count();

        $gruposCadastrados = array_filter(\App\Enums\CorTroca::cases(), function($cor) use ($quantidades) {
            $val = $cor->value;
            $qtd = $quantidades[strtolower($val)] ?? $quantidades[ucfirst($val)] ?? $quantidades[$val] ?? 0;
            return $qtd > 0;
        });
    @endphp

    {{-- 1. Cabeçalho --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <flux:heading size="lg">Participantes Confirmados</flux:heading>
                <flux:badge size="sm" color="zinc" inset="top bottom" title="Total filtrado">{{ $participantes->total() }}</flux:badge>
                <flux:button wire:click="exportar" icon="arrow-down-tray" variant="outline" size="sm" title="Exportar CSV">
                    Exportar
                </flux:button>
            </div>
            <flux:subheading class="mt-1">Gerencie as cores das trocas e informações básicas.</flux:subheading>
        </div>
    </div>

    {{-- 2. Filtros e Busca (Linha dedicada com borda igual ao modelo de Fichas) --}}
    <div class="flex flex-col sm:flex-row justify-between items-center gap-3 w-full border-t border-zinc-100 dark:border-zinc-700/50 pt-4">
        <div class="w-full sm:w-64">
            <flux:select wire:model.live="corTroca" icon="funnel" placeholder="Todas as cores" class="w-full">
                <option value="">Todas as cores ({{ $totalParticipantes }})</option>
                @foreach (\App\Enums\CorTroca::cases() as $cor)
                    @php
                        $qtd = $quantidades[strtolower($cor->value)] ?? $quantidades[ucfirst($cor->value)] ?? $quantidades[$cor->value] ?? 0;
                    @endphp
                    <option value="{{ $cor->value }}">{{ $cor->label() }} ({{ $qtd }})</option>
                @endforeach
            </flux:select>
        </div>

        <div class="w-full sm:w-72">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="Nome ou apelido..."
                class="w-full"
            />
        </div>
    </div>

    {{-- 3. Dashboard de Grupos por Cor (Grid centralizado e responsivo) --}}
    @if (!empty($gruposCadastrados))
        @php
            $numGrupos = count($gruposCadastrados);
            $gridColsClass = match ($numGrupos) {
                1 => 'grid-cols-1 max-w-xs mx-auto',
                2 => 'grid-cols-2 max-w-lg mx-auto',
                3 => 'grid-cols-2 sm:grid-cols-3 max-w-3xl mx-auto',
                4 => 'grid-cols-2 sm:grid-cols-4 max-w-4xl mx-auto',
                default => 'grid-cols-2 sm:grid-cols-3 md:grid-cols-5 max-w-5xl mx-auto',
            };
        @endphp
        <div class="grid {{ $gridColsClass }} gap-3 my-2 w-full">
            @foreach ($gruposCadastrados as $cor)
                @php
                    $val = $cor->value;
                    $qtd = $quantidades[strtolower($val)] ?? $quantidades[ucfirst($val)] ?? $quantidades[$val] ?? 0;
                    $isActive = strtolower($this->corTroca) === strtolower($val);
                @endphp
                <div
                    wire:click="toggleCorTroca('{{ $val }}')"
                    class="w-full cursor-pointer transition-all duration-200 rounded-xl p-3 flex flex-col border shadow-sm hover:shadow-md hover:-translate-y-0.5 {{ $isActive ? $cor->activeClass() : 'bg-white dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700' }}"
                >
                    <div class="flex items-center gap-2">
                        <span class="size-3 rounded-full shrink-0 {{ $cor->dotClass() }}"></span>
                        <span class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 truncate">Grupo {{ $cor->label() }}</span>
                    </div>
                    <h4 class="text-xl font-bold text-zinc-900 dark:text-white mt-2">{{ $qtd }}</h4>
                </div>
            @endforeach
        </div>
    @endif

    {{-- 4. Tabela com rolagem horizontal e padding adequado nas bordas --}}
    <div class="overflow-x-auto border border-zinc-200 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-800 shadow-xs">
        <table class="w-full text-left text-sm border-collapse min-w-[950px]">
            <thead class="bg-zinc-50 dark:bg-zinc-800/60 border-b border-zinc-200 dark:border-zinc-700 select-none">
                <tr>
                    <th scope="col" class="px-6 py-4 font-bold text-zinc-950 dark:text-white" aria-sort="{{ $sortColumn === 'nom_pessoa' ? ($sortDirection === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                        <button
                            type="button"
                            wire:click="sortBy('nom_pessoa')"
                            class="group inline-flex items-center gap-1.5 hover:text-indigo-600 dark:hover:text-indigo-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 rounded transition-colors"
                            title="Clique para ordenar por Nome"
                        >
                            <span>Nome</span>
                            @if ($sortColumn === 'nom_pessoa')
                                <flux:icon :icon="$sortDirection === 'asc' ? 'chevron-up' : 'chevron-down'" class="size-4 text-indigo-600 dark:text-indigo-400" />
                            @else
                                <flux:icon.arrows-up-down class="size-3.5 text-zinc-400 opacity-40 group-hover:opacity-100 transition-opacity" />
                            @endif
                        </button>
                    </th>
                    <th scope="col" class="px-6 py-4 font-bold text-zinc-950 dark:text-white whitespace-nowrap" aria-sort="{{ $sortColumn === 'dat_nascimento' ? ($sortDirection === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                        <button
                            type="button"
                            wire:click="sortBy('dat_nascimento')"
                            class="group inline-flex items-center gap-1.5 hover:text-indigo-600 dark:hover:text-indigo-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 rounded transition-colors"
                            title="Clique para ordenar por Data de Nascimento / Idade"
                        >
                            <span>Data Nasc</span>
                            @if ($sortColumn === 'dat_nascimento')
                                <flux:icon :icon="$sortDirection === 'asc' ? 'chevron-up' : 'chevron-down'" class="size-4 text-indigo-600 dark:text-indigo-400" />
                            @else
                                <flux:icon.arrows-up-down class="size-3.5 text-zinc-400 opacity-40 group-hover:opacity-100 transition-opacity" />
                            @endif
                        </button>
                    </th>
                    <th scope="col" class="px-6 py-4 font-bold text-zinc-950 dark:text-white" aria-sort="{{ $sortColumn === 'des_endereco' ? ($sortDirection === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                        <button
                            type="button"
                            wire:click="sortBy('des_endereco')"
                            class="group inline-flex items-center gap-1.5 hover:text-indigo-600 dark:hover:text-indigo-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 rounded transition-colors"
                            title="Clique para ordenar por Endereço"
                        >
                            <span>Endereço</span>
                            @if ($sortColumn === 'des_endereco')
                                <flux:icon :icon="$sortDirection === 'asc' ? 'chevron-up' : 'chevron-down'" class="size-4 text-indigo-600 dark:text-indigo-400" />
                            @else
                                <flux:icon.arrows-up-down class="size-3.5 text-zinc-400 opacity-40 group-hover:opacity-100 transition-opacity" />
                            @endif
                        </button>
                    </th>
                    <th scope="col" class="px-6 py-4 font-bold text-zinc-950 dark:text-white whitespace-nowrap" aria-sort="{{ $sortColumn === 'tip_cor_troca' ? ($sortDirection === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                        <button
                            type="button"
                            wire:click="sortBy('tip_cor_troca')"
                            class="group inline-flex items-center gap-1.5 hover:text-indigo-600 dark:hover:text-indigo-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 rounded transition-colors"
                            title="Clique para ordenar por Cor do Grupo"
                        >
                            <span>Cor do Grupo</span>
                            @if ($sortColumn === 'tip_cor_troca')
                                <flux:icon :icon="$sortDirection === 'asc' ? 'chevron-up' : 'chevron-down'" class="size-4 text-indigo-600 dark:text-indigo-400" />
                            @else
                                <flux:icon.arrows-up-down class="size-3.5 text-zinc-400 opacity-40 group-hover:opacity-100 transition-opacity" />
                            @endif
                        </button>
                    </th>
                    <th scope="col" class="px-6 py-4 font-bold text-zinc-950 dark:text-white whitespace-nowrap" aria-sort="{{ $sortColumn === 'tam_camiseta' ? ($sortDirection === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                        <button
                            type="button"
                            wire:click="sortBy('tam_camiseta')"
                            class="group inline-flex items-center gap-1.5 hover:text-indigo-600 dark:hover:text-indigo-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 rounded transition-colors"
                            title="Clique para ordenar por Tamanho de Camiseta"
                        >
                            <span>Camiseta</span>
                            @if ($sortColumn === 'tam_camiseta')
                                <flux:icon :icon="$sortDirection === 'asc' ? 'chevron-up' : 'chevron-down'" class="size-4 text-indigo-600 dark:text-indigo-400" />
                            @else
                                <flux:icon.arrows-up-down class="size-3.5 text-zinc-400 opacity-40 group-hover:opacity-100 transition-opacity" />
                            @endif
                        </button>
                    </th>
                    <th scope="col" class="px-6 py-4 font-bold text-zinc-950 dark:text-white whitespace-nowrap" aria-sort="{{ $sortColumn === 'responsavel' ? ($sortDirection === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                        <button
                            type="button"
                            wire:click="sortBy('responsavel')"
                            class="group inline-flex items-center gap-1.5 hover:text-indigo-600 dark:hover:text-indigo-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 rounded transition-colors"
                            title="Clique para ordenar por Nome do Responsável"
                        >
                            <span>Responsável</span>
                            @if ($sortColumn === 'responsavel')
                                <flux:icon :icon="$sortDirection === 'asc' ? 'chevron-up' : 'chevron-down'" class="size-4 text-indigo-600 dark:text-indigo-400" />
                            @else
                                <flux:icon.arrows-up-down class="size-3.5 text-zinc-400 opacity-40 group-hover:opacity-100 transition-opacity" />
                            @endif
                        </button>
                    </th>
                    <th scope="col" class="px-6 py-4 font-bold text-zinc-950 dark:text-white text-right whitespace-nowrap">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700/60">
                @forelse ($participantes as $p)
                    <tr wire:key="participante-row-{{ $p->idt_participante }}" class="hover:bg-zinc-50/50 dark:hover:bg-zinc-700/20 transition-colors">
                        {{-- Nome --}}
                        <td class="px-6 py-4 align-middle">
                            <div class="flex items-center gap-3">
                                <flux:avatar
                                    src="{{ $p->pessoa->foto?->url_foto ? asset('storage/'.$p->pessoa->foto->url_foto) : '' }}"
                                    :initials="substr($p->pessoa->nom_pessoa, 0, 2)"
                                    size="sm"
                                />
                                <div class="space-y-1">
                                    <div class="font-medium text-zinc-900 dark:text-white">{{ $p->pessoa->nom_pessoa }}</div>
                                    <div class="text-xs text-zinc-500">{{ $p->pessoa->nom_apelido }}</div>
                                    @if ($p->des_parentesco)
                                        <flux:badge
                                            size="sm"
                                            color="purple"
                                            icon="user-group"
                                            wire:click="abrirParentesco({{ $p->idt_participante }})"
                                            class="cursor-pointer hover:opacity-80 transition-opacity"
                                            title="Clique para editar parentesco"
                                        >
                                            {{ $p->des_parentesco }}
                                        </flux:badge>
                                    @endif
                                </div>
                            </div>
                        </td>

                        {{-- Data Nasc --}}
                        <td class="px-6 py-4 align-middle whitespace-nowrap">
                            @if ($p->pessoa->dat_nascimento)
                                <div class="text-sm">
                                    <span class="text-zinc-800 dark:text-zinc-200 font-medium">{{ $p->pessoa->dat_nascimento->format('d/m/Y') }}</span>
                                    <span class="text-xs text-zinc-400 dark:text-zinc-500 ml-2">({{ $p->pessoa->dat_nascimento->age }} anos)</span>
                                </div>
                            @else
                                <span class="text-xs text-zinc-400">—</span>
                            @endif
                        </td>

                        {{-- Endereço --}}
                        <td class="px-6 py-4 align-middle">
                            @if ($p->pessoa->des_endereco)
                                <span class="text-xs text-zinc-700 dark:text-zinc-300 max-w-[220px] truncate block" title="{{ $p->pessoa->des_endereco }}">
                                    {{ $p->pessoa->des_endereco }}
                                </span>
                            @else
                                <span class="text-xs text-zinc-400">—</span>
                            @endif
                        </td>

                        {{-- Cor da Troca --}}
                        <td class="px-6 py-4 align-middle whitespace-nowrap">
                            <select
                                wire:key="select-cor-{{ $p->idt_participante }}-{{ $p->tip_cor_troca }}"
                                wire:change="atualizarTroca({{ $p->idt_participante }}, $event.target.value)"
                                class="w-32 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-800 dark:text-zinc-200 px-2.5 py-1.5 text-xs font-semibold shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 border-l-[5px] {{ \App\Enums\CorTroca::tryFrom(strtolower($p->tip_cor_troca ?? ''))?->borderLClass() ?? 'border-l-zinc-200 dark:border-l-zinc-700' }}">
                                <option value="" @selected(empty($p->tip_cor_troca)) class="text-zinc-800 bg-white dark:bg-zinc-900 dark:text-zinc-200">
                                    Selecionar...
                                </option>
                                @foreach (\App\Enums\CorTroca::cases() as $cor)
                                    <option value="{{ $cor->value }}" @selected(strtolower($p->tip_cor_troca ?? '') === $cor->value) class="text-zinc-800 bg-white dark:bg-zinc-900 dark:text-zinc-200">
                                        {{ $cor->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </td>

                        {{-- Camiseta --}}
                        <td class="px-6 py-4 align-middle whitespace-nowrap">
                            @if ($p->pessoa->tam_camiseta)
                                <flux:badge size="sm" color="zinc">
                                    {{ $p->pessoa->tam_camiseta->value }}
                                </flux:badge>
                            @else
                                <span class="text-xs text-zinc-400">—</span>
                            @endif
                        </td>

                        {{-- Responsável --}}
                        <td class="px-6 py-4 align-middle whitespace-nowrap">
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
                        </td>

                        {{-- Ações --}}
                        <td class="px-6 py-4 align-middle text-right whitespace-nowrap">
                            <div class="flex justify-end gap-2">
                                <flux:button
                                    icon="user-plus"
                                    size="sm"
                                    variant="ghost"
                                    wire:click="abrirParentesco({{ $p->idt_participante }})"
                                    tooltip="Parentesco"
                                />
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
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-10 text-zinc-500">
                            Nenhum participante encontrado para este evento.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $participantes->links(data: ['scrollTo' => false]) }}
    </div>

    {{-- Modal de Parentesco --}}
    <flux:modal name="modal-parentesco" class="min-w-[20rem] md:min-w-[28rem]">
        <form wire:submit="salvarParentesco" class="space-y-6">
            <div>
                <flux:heading size="lg">Gerenciar Parentesco</flux:heading>
                <flux:subheading>Informe a relação de parentesco com outro participante do evento.</flux:subheading>
            </div>

            <div class="space-y-4">
                <flux:input
                    label="Parentesco"
                    wire:model="desParentescoInput"
                    placeholder="Ex: irmã da Ana, pai do Pedro"
                    autofocus
                />
            </div>

            <div class="flex items-center justify-between gap-2 pt-2 border-t border-zinc-200 dark:border-zinc-700">
                <div>
                    @if ($desParentescoInput)
                        <flux:button
                            type="button"
                            variant="ghost"
                            color="red"
                            icon="trash"
                            wire:click="removerParentesco"
                        >
                            Desvincular
                        </flux:button>
                    @endif
                </div>

                <div class="flex gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancelar</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary">Salvar Parentesco</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>
</div>
