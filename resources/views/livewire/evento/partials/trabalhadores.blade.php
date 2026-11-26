<?php

use App\Models\Evento;
use App\Models\TipoEquipe;
use App\Models\Trabalhador;
use App\Models\Voluntario;
use App\Services\PhoneService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    private const AVALIACAO = [
        'ind_recomendado' => 'Recomendado para trabalhar novamente',
        'ind_lideranca' => 'Tem potencial para liderança',
        'ind_destaque' => 'Indicado para a Coordenação Geral',
        'ind_camiseta_pediu' => 'Pediu camiseta',
        'ind_camiseta_pagou' => 'Pagou a camiseta',
        'ind_taxa_pagou' => 'Pagou a taxa de inscrição',
    ];

    #[Locked]
    public Evento $evento;
    #[Locked]
    public ?int $trabalhadorSelecionadoId = null;
    #[Locked]
    public ?int $alterarEquipeTrabalhadorId = null;
    #[Locked]
    public string $nomeTrabalhador = '';

    public string $search = '';
    public string $equipeFiltroId = '';
    public string $visualizacao = 'geral';
    public string $equipeAbertaId = '';
    public string $situacaoAvaliacao = 'todos';
    public array $formAvaliacao = [];
    public ?int $alterarEquipeId = null;
    public bool $alterarIndCoordenador = false;
    public bool $alterarIndPrimeiraVez = false;
    public string $aviso = '';

    public function mount(Evento $evento): void
    {
        Gate::authorize('evento-tab-trabalhadores', $evento);
        $this->evento = $evento;
    }

    public function updated(string $campo): void
    {
        if (in_array($campo, ['search', 'equipeFiltroId', 'situacaoAvaliacao'], true)) {
            $this->resetPage('paginaTrabalhadores');
            $this->resetPage('paginaEquipeTrabalhadores');
        }
    }

    public function limparFiltros(): void
    {
        $this->reset('search', 'equipeFiltroId', 'situacaoAvaliacao');
        $this->resetPage('paginaTrabalhadores');
        $this->resetPage('paginaEquipeTrabalhadores');
    }

    protected function trabalhadorDoEvento(?int $id): Trabalhador
    {
        Gate::authorize('evento-tab-trabalhadores', $this->evento);

        return Trabalhador::where('idt_evento', $this->evento->getKey())->findOrFail($id);
    }

    public function abrirAvaliacao(int $id): void
    {
        $trabalhador = $this->trabalhadorDoEvento($id);
        $this->resetValidation();
        $this->trabalhadorSelecionadoId = $id;
        $this->nomeTrabalhador = $trabalhador->pessoa?->nom_pessoa ?? '';
        foreach (array_keys(self::AVALIACAO) as $campo) {
            $this->formAvaliacao[$campo] = (bool) $trabalhador->$campo;
        }
        $this->modal('avaliar-trabalhador')->show();
    }

    public function salvarAvaliacao(): void
    {
        $trabalhador = $this->trabalhadorDoEvento($this->trabalhadorSelecionadoId);
        $regras = [];
        foreach (array_keys(self::AVALIACAO) as $campo) {
            $regras["formAvaliacao.$campo"] = 'required|boolean';
        }
        $avaliacao = $this->validate($regras)['formAvaliacao'];
        $trabalhador->update($avaliacao + ['ind_avaliacao' => true]);
        $this->modal('avaliar-trabalhador')->close();
        $this->trabalhadorSelecionadoId = null;
        $this->resetPage('paginaTrabalhadores');
        $this->aviso = 'Avaliação salva. O trabalhador agora aparece no Quadrante.';
    }

    public function abrirAlterarEquipe(int $id): void
    {
        $trabalhador = $this->trabalhadorDoEvento($id);
        $this->resetValidation();
        $this->alterarEquipeTrabalhadorId = $id;
        $this->nomeTrabalhador = $trabalhador->pessoa?->nom_pessoa ?? '';
        $this->alterarEquipeId = $trabalhador->idt_equipe;
        $this->alterarIndCoordenador = (bool) $trabalhador->ind_coordenador;
        $this->alterarIndPrimeiraVez = (bool) $trabalhador->ind_primeira_vez;
        $this->modal('alterar-equipe-trabalhador')->show();
    }

    public function salvarAlterarEquipe(): void
    {
        $trabalhador = $this->trabalhadorDoEvento($this->alterarEquipeTrabalhadorId);
        $this->validate([
            'alterarEquipeId' => ['required', 'integer', Rule::exists('tipo_equipe', 'idt_equipe')
                ->where('idt_movimento', $this->evento->idt_movimento)->whereNull('deleted_at')],
        ], ['alterarEquipeId.required' => 'Escolha uma equipe.', 'alterarEquipeId.exists' => 'Escolha uma equipe deste evento.']);

        DB::transaction(function () use ($trabalhador) {
            Evento::lockForUpdate()->findOrFail($this->evento->getKey());
            $trabalhador->refresh();
            $outros = Trabalhador::where('idt_evento', $this->evento->getKey())->whereKeyNot($trabalhador->getKey());

            if ((clone $outros)->where('idt_pessoa', $trabalhador->idt_pessoa)->where('idt_equipe', $this->alterarEquipeId)->exists()) {
                throw ValidationException::withMessages(['alterarEquipeId' => 'Esta pessoa já está nessa equipe.']);
            }

            if ($this->alterarIndCoordenador && (! $trabalhador->ind_coordenador || $trabalhador->idt_equipe !== $this->alterarEquipeId)) {
                $outroCoordenador = (clone $outros)->where('ind_coordenador', true)
                    ->where(fn ($query) => $query->where('idt_equipe', $this->alterarEquipeId)
                        ->orWhere('idt_pessoa', $trabalhador->idt_pessoa))->exists();
                $indicacao = DB::table('coordenador_evento')->where('idt_evento', $this->evento->getKey())
                    ->where('idt_equipe', $this->alterarEquipeId)->exists();
                if ($outroCoordenador || $indicacao) {
                    throw ValidationException::withMessages(['alterarEquipeId' => 'Revise a indicação na aba Voluntários antes de alterar a coordenação.']);
                }
            }

            if (! $this->alterarIndCoordenador || $trabalhador->idt_equipe !== $this->alterarEquipeId) {
                $this->removerIndicacao($trabalhador);
            }

            $trabalhador->update([
                'idt_equipe' => $this->alterarEquipeId,
                'ind_coordenador' => $this->alterarIndCoordenador,
                'ind_primeira_vez' => $this->alterarIndPrimeiraVez,
            ]);
        });

        $this->modal('alterar-equipe-trabalhador')->close();
        $this->reset('alterarEquipeTrabalhadorId', 'alterarEquipeId', 'alterarIndCoordenador', 'alterarIndPrimeiraVez');
        $this->aviso = 'Equipe e função atualizadas.';
    }

    public function removerTrabalhador(int $id): void
    {
        $trabalhador = $this->trabalhadorDoEvento($id);
        DB::transaction(function () use ($trabalhador) {
            Evento::lockForUpdate()->findOrFail($this->evento->getKey());
            $trabalhador->refresh();
            $this->removerIndicacao($trabalhador);
            Voluntario::where('idt_trabalhador', $trabalhador->getKey())->update(['idt_trabalhador' => null]);
            $trabalhador->delete();
        });
        $this->resetPage('paginaTrabalhadores');
        $this->aviso = 'Trabalhador removido da escalação.';
    }

    protected function removerIndicacao(Trabalhador $trabalhador): void
    {
        DB::table('coordenador_evento')->where('idt_evento', $this->evento->getKey())
            ->where('idt_pessoa', $trabalhador->idt_pessoa)->where('idt_equipe', $trabalhador->idt_equipe)->delete();
    }

    private const POR_PAGINA = 10;

    protected function pagina(int $pagina, int $total): int
    {
        return min(max(1, $pagina), max(1, (int) ceil($total / self::POR_PAGINA)));
    }

    protected function dadosTrabalhador(object $trabalhador, $equipes): array
    {
        return [
            'id' => (int) $trabalhador->idt_trabalhador,
            'nome' => $trabalhador->nom_pessoa,
            'apelido' => $trabalhador->nom_apelido,
            'iniciais' => mb_strtoupper(mb_substr($trabalhador->nom_pessoa ?? '?', 0, 1)),
            'telefone' => PhoneService::format($trabalhador->tel_pessoa),
            'equipe' => $equipes->get($trabalhador->idt_equipe)?->des_grupo ?? 'Equipe indisponível',
            'coordenador' => (bool) $trabalhador->ind_coordenador,
            'primeiraVez' => (bool) $trabalhador->ind_primeira_vez,
            'avaliado' => (bool) $trabalhador->ind_avaliacao,
            'tipoCandidatura' => $trabalhador->tipo_candidatura ? ucfirst($trabalhador->tipo_candidatura) : null,
            'menor' => $trabalhador->dat_nascimento && Carbon::parse($trabalhador->dat_nascimento)->age < 18,
        ];
    }

    public function with(): array
    {
        Gate::authorize('evento-tab-trabalhadores', $this->evento);
        $busca = trim($this->search);
        $equipes = TipoEquipe::catalogo($this->evento->idt_movimento);
        $consulta = DB::table('trabalhador')
            ->join('pessoa', 'trabalhador.idt_pessoa', '=', 'pessoa.idt_pessoa')
            ->where('trabalhador.idt_evento', $this->evento->getKey())
            ->whereNull('pessoa.deleted_at')
            ->when(in_array($this->situacaoAvaliacao, ['pendentes', 'avaliados'], true),
                fn ($query) => $query->where('trabalhador.ind_avaliacao', $this->situacaoAvaliacao === 'avaliados'))
            ->when($busca !== '', fn ($query) => $query->where(fn ($pessoa) => $pessoa
                ->where('pessoa.nom_pessoa', 'like', "%$busca%")->orWhere('pessoa.nom_apelido', 'like', "%$busca%")));

        $contagens = (clone $consulta)->selectRaw('trabalhador.idt_equipe, count(*) as total,
            sum(case when trabalhador.ind_avaliacao = false then 1 else 0 end) as pendentes')
            ->groupBy('trabalhador.idt_equipe')->get()->keyBy('idt_equipe');
        $equipesDaVisao = $equipes->keyBy('idt_equipe');
        $historicas = $contagens->keys()->diff($equipesDaVisao->keys());
        if ($historicas->isNotEmpty()) {
            $equipesDaVisao = $equipesDaVisao->union(TipoEquipe::withTrashed()->whereKey($historicas->all())
                ->with('movimento:idt_movimento,des_sigla')->get(['idt_equipe', 'idt_movimento', 'des_grupo'])->keyBy('idt_equipe'));
        }

        $colunas = ['trabalhador.idt_trabalhador', 'trabalhador.idt_equipe', 'trabalhador.ind_coordenador',
            'trabalhador.ind_primeira_vez', 'trabalhador.ind_avaliacao', 'pessoa.nom_pessoa',
            'pessoa.nom_apelido', 'pessoa.tel_pessoa', 'pessoa.dat_nascimento', 'candidatura.tipo_candidatura'];
        $consulta->leftJoin('candidatura_voluntario as candidatura', fn ($join) => $join
            ->on('candidatura.idt_evento', '=', 'trabalhador.idt_evento')
            ->on('candidatura.idt_pessoa', '=', 'trabalhador.idt_pessoa'));
        $mapear = fn ($registro) => $this->dadosTrabalhador($registro, $equipesDaVisao);

        $totalGeral = (int) ($this->equipeFiltroId ? $contagens->get($this->equipeFiltroId)?->total : $contagens->sum('total'));
        $paginaGeral = $this->pagina($this->getPage('paginaTrabalhadores'), $totalGeral);
        $geral = $totalGeral ? (clone $consulta)->select($colunas)
            ->when($this->equipeFiltroId, fn ($query) => $query->where('trabalhador.idt_equipe', $this->equipeFiltroId))
            ->orderBy('pessoa.nom_pessoa')->orderBy('trabalhador.idt_trabalhador')
            ->forPage($paginaGeral, self::POR_PAGINA)->get()->map($mapear) : collect();

        $indices = (clone $consulta)->select($colunas)->selectRaw('row_number() over (
            partition by trabalhador.idt_equipe
            order by trabalhador.ind_coordenador desc, pessoa.nom_pessoa, trabalhador.idt_trabalhador
        ) as ordem_equipe');
        $primeirasPaginas = $contagens->isEmpty() ? collect() : DB::query()->fromSub($indices, 'equipes_paginadas')
            ->where('ordem_equipe', '<=', self::POR_PAGINA)->orderBy('ordem_equipe')->get()->groupBy('idt_equipe');

        $listas = $equipesDaVisao->mapWithKeys(function (TipoEquipe $equipe) use ($contagens, $primeirasPaginas, $mapear) {
            $id = $equipe->getKey();
            $contagem = $contagens->get($id);

            return [$id => [
                'total' => (int) ($contagem?->total ?? 0),
                'pendentes' => (int) ($contagem?->pendentes ?? 0),
                'pagina' => 1,
                'itens' => $primeirasPaginas->get($id, collect())->map($mapear)->all(),
            ]];
        });

        $equipeAberta = $listas->get($this->equipeAbertaId);
        $paginaEquipe = $this->pagina($this->getPage('paginaEquipeTrabalhadores'), $equipeAberta['total'] ?? 0);
        $paginaAdicional = $paginaEquipe > 1 ? [
            'id' => $this->equipeAbertaId,
            'total' => $equipeAberta['total'],
            'pagina' => $paginaEquipe,
            'itens' => (clone $consulta)->select($colunas)->where('trabalhador.idt_equipe', $this->equipeAbertaId)
                ->orderByDesc('trabalhador.ind_coordenador')->orderBy('pessoa.nom_pessoa')->orderBy('trabalhador.idt_trabalhador')
                ->forPage($paginaEquipe, self::POR_PAGINA)->get()->map($mapear)->all(),
        ] : null;

        return [
            'equipes' => $equipes,
            'camposAvaliacao' => self::AVALIACAO,
            'dadosTrabalhadores' => [
                'porPagina' => self::POR_PAGINA,
                'geral' => ['itens' => $geral->all(), 'total' => $totalGeral, 'pagina' => $paginaGeral],
                'equipes' => $listas,
                'grupos' => $equipesDaVisao->map(function (TipoEquipe $equipe) use ($equipes, $listas) {
                    $id = $equipe->getKey();
                    $sigla = $equipe->relationLoaded('movimento') ? $equipe->movimento?->des_sigla : null;

                    return [
                        'id' => (string) $id,
                        'nome' => $equipe->des_grupo,
                        'descricao' => $equipes->contains('idt_equipe', $id) ? null : 'Fora do catálogo atual'.($sigla ? ' · '.$sigla : ''),
                        'total' => $listas[$id]['total'],
                        'pendentes' => $listas[$id]['pendentes'],
                    ];
                })->values()->all(),
                'paginaEquipe' => $paginaAdicional,
            ],
        ];
    }
}; ?>

<div class="space-y-5" x-data="listaTrabalhadores"
    data-trabalhadores="{{ json_encode($dadosTrabalhadores, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) }}">
    <header class="flex flex-wrap items-start justify-between gap-3">
        <div class="space-y-1">
            <flux:heading level="2" size="xl" class="font-bold text-indigo-950 dark:text-indigo-100">Trabalhadores</flux:heading>
            <flux:subheading>Consulte a escalação e acompanhe as avaliações. Quem já foi avaliado também aparece no Quadrante.</flux:subheading>
        </div>
        <flux:button href="{{ route('eventos.casais-visitacao', $evento) }}" size="sm" icon="user-group" variant="outline">
            Casais de Visitação
        </flux:button>
    </header>

    <div class="flex gap-2" role="group" aria-label="Visualização dos trabalhadores">
        <flux:button size="sm" icon="users" variant="outline" class="aria-pressed:bg-indigo-100! dark:aria-pressed:bg-indigo-950!"
            x-bind:aria-pressed="$wire.visualizacao === 'geral'" x-on:click="mudarVisualizacao('geral')">Geral</flux:button>
        <flux:button size="sm" icon="squares-2x2" variant="outline" class="aria-pressed:bg-indigo-100! dark:aria-pressed:bg-indigo-950!"
            x-bind:aria-pressed="$wire.visualizacao === 'equipes'" x-on:click="mudarVisualizacao('equipes')">Por equipe</flux:button>
    </div>

    <div class="grid gap-3 rounded-xl border border-zinc-200 bg-zinc-50 p-4 sm:grid-cols-2 lg:grid-cols-[minmax(0,1fr)_minmax(0,12rem)_minmax(0,12rem)_auto] lg:items-end dark:border-zinc-700 dark:bg-zinc-900">
        <flux:input label="Buscar pessoa" wire:model.live.debounce.350ms="search" icon="magnifying-glass" placeholder="Nome ou apelido" />
        <div x-show="$wire.visualizacao === 'geral'" x-cloak>
            <flux:select label="Equipe" wire:model.live="equipeFiltroId">
                <option value="">Todas as equipes</option>
                @foreach ($equipes as $equipe)
                    <option value="{{ $equipe->getKey() }}">{{ $equipe->des_grupo }}</option>
                @endforeach
            </flux:select>
        </div>
        <flux:select label="Avaliação" wire:model.live="situacaoAvaliacao">
            <option value="todos">Todos</option>
            <option value="pendentes">Pendentes de avaliação</option>
            <option value="avaliados">Já avaliados</option>
        </flux:select>
        @if ($search !== '' || $equipeFiltroId !== '' || $situacaoAvaliacao !== 'todos')
            <flux:button type="button" variant="ghost" wire:click="limparFiltros">Limpar filtros</flux:button>
        @endif
    </div>

    @if ($aviso)
        <flux:callout role="status" variant="success" icon="check-circle"><flux:callout.text>{{ $aviso }}</flux:callout.text></flux:callout>
    @endif

    <p wire:loading.delay wire:target="search,equipeFiltroId,situacaoAvaliacao,limparFiltros,setPage"
        role="status" class="text-sm text-zinc-600 dark:text-zinc-300">Atualizando lista…</p>

    <section x-show="$wire.visualizacao === 'equipes'" x-cloak class="space-y-3" aria-labelledby="equipes-trabalhadores-titulo">
        <h3 id="equipes-trabalhadores-titulo" class="font-semibold">Equipes do evento</h3>
        <p x-show="!dados.grupos?.length" class="text-sm text-zinc-600 dark:text-zinc-300">Nenhuma equipe encontrada.</p>
        <ul class="space-y-2">
            <template x-for="grupo in dados.grupos ?? []" :key="grupo.id">
                <li class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
                    <h4>
                        <button type="button" x-on:click="alternarEquipe(grupo.id)"
                            x-bind:id="'resumo-equipe-' + grupo.id" x-bind:aria-expanded="$wire.equipeAbertaId === grupo.id"
                            x-bind:aria-controls="'detalhe-equipe-' + grupo.id"
                            class="flex w-full items-center gap-3 bg-white px-4 py-3 text-left hover:bg-zinc-50
                                aria-expanded:bg-indigo-50 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-indigo-500
                                dark:bg-zinc-900 dark:hover:bg-zinc-800 dark:aria-expanded:bg-indigo-950">
                            <span class="min-w-0 flex-1">
                                <span class="block break-words font-semibold" x-text="grupo.nome"></span>
                                <span class="block text-xs font-normal text-zinc-600 dark:text-zinc-300"
                                    x-show="grupo.descricao" x-text="grupo.descricao"></span>
                                <span class="block text-sm font-normal text-zinc-600 dark:text-zinc-300"
                                    x-text="grupo.total + (grupo.total === 1 ? ' escalado' : ' escalados') + ' · ' + grupo.pendentes + ' para avaliar'"></span>
                            </span>
                            <flux:icon.chevron-down aria-hidden="true" x-bind:class="{ 'rotate-180': $wire.equipeAbertaId === grupo.id }"
                                class="size-5 shrink-0 text-indigo-700 dark:text-indigo-300" />
                        </button>
                    </h4>
                    <div x-bind:id="'detalhe-equipe-' + grupo.id" x-show="$wire.equipeAbertaId === grupo.id">
                        <template x-if="$wire.equipeAbertaId === grupo.id && $wire.visualizacao === 'equipes'">
                            @include('trabalhador.lista-gerenciamento', [
                                'lista' => 'equipe(grupo.id)', 'porEquipe' => true, 'nomePagina' => 'paginaEquipeTrabalhadores',
                            ])
                        </template>
                    </div>
                </li>
            </template>
        </ul>
    </section>

    <section x-show="$wire.visualizacao === 'geral'" x-cloak class="space-y-3" aria-labelledby="trabalhadores-listados">
        <h3 id="trabalhadores-listados" class="font-semibold">
            Trabalhadores na lista <span class="text-sm font-normal text-zinc-600 dark:text-zinc-300" x-text="'(' + (dados.geral?.total ?? 0) + ')'"></span>
        </h3>
        <template x-if="$wire.visualizacao === 'geral'">
            @include('trabalhador.lista-gerenciamento', [
                'lista' => 'dados.geral', 'porEquipe' => false, 'nomePagina' => 'paginaTrabalhadores',
            ])
        </template>
    </section>

    <flux:modal name="avaliar-trabalhador" x-data="modalTriagem('titulo-avaliacao-trabalhador')"
        class="triagem-modal w-[calc(100%-1rem)] max-w-2xl max-h-[calc(100dvh-1rem)] overflow-y-auto p-0">
        <header class="border-b border-indigo-100 bg-indigo-50 px-5 py-4 pe-12 dark:border-indigo-900 dark:bg-indigo-950">
            <flux:heading id="titulo-avaliacao-trabalhador" level="2" size="lg">Avaliar trabalhador</flux:heading>
            <flux:subheading>{{ $nomeTrabalhador }}</flux:subheading>
        </header>
        <form wire:submit="salvarAvaliacao">
            <div class="grid gap-4 p-5 sm:grid-cols-2">
                @foreach (['Perfil e liderança' => array_slice($camposAvaliacao, 0, 3, true), 'Camiseta e inscrição' => array_slice($camposAvaliacao, 3, 3, true)] as $titulo => $campos)
                    <fieldset class="space-y-3 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                        <legend class="px-1 text-sm font-semibold">{{ $titulo }}</legend>
                        @foreach ($campos as $campo => $rotulo)
                            <flux:checkbox wire:model="formAvaliacao.{{ $campo }}" :label="$rotulo" />
                        @endforeach
                    </fieldset>
                @endforeach
            </div>
            <footer class="flex flex-wrap justify-end gap-2 border-t border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:modal.close><flux:button type="button" variant="ghost" data-modal-initial-focus>Cancelar</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary" class="bg-indigo-700! text-white! hover:bg-indigo-800!">Salvar avaliação</flux:button>
            </footer>
        </form>
    </flux:modal>

    <flux:modal name="alterar-equipe-trabalhador" x-data="modalTriagem('titulo-alocacao-trabalhador')"
        class="triagem-modal w-[calc(100%-1rem)] max-w-lg max-h-[calc(100dvh-1rem)] overflow-y-auto p-0">
        <header class="border-b border-indigo-100 bg-indigo-50 px-5 py-4 pe-12 dark:border-indigo-900 dark:bg-indigo-950">
            <flux:heading id="titulo-alocacao-trabalhador" level="2" size="lg">Alterar alocação</flux:heading>
            <flux:subheading>{{ $nomeTrabalhador }}</flux:subheading>
        </header>
        <form wire:submit="salvarAlterarEquipe">
            <div class="space-y-4 p-5">
                <flux:select label="Equipe" wire:model="alterarEquipeId" data-modal-initial-focus autofocus required>
                    <option value="">Selecione uma equipe</option>
                    @foreach ($equipes as $equipe)
                        <option value="{{ $equipe->getKey() }}">{{ $equipe->des_grupo }}</option>
                    @endforeach
                </flux:select>
                <flux:checkbox wire:model="alterarIndCoordenador" label="Coordenador da equipe" />
                <flux:checkbox wire:model="alterarIndPrimeiraVez" label="Primeira vez como trabalhador" />
            </div>
            <footer class="flex flex-wrap justify-end gap-2 border-t border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:modal.close><flux:button type="button" variant="ghost">Cancelar</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary" class="bg-indigo-700! text-white! hover:bg-indigo-800!">Salvar alocação</flux:button>
            </footer>
        </form>
    </flux:modal>
</div>
