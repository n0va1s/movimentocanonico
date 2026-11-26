<?php

use App\Enums\TipoEvento;
use App\Models\Evento;
use App\Models\TipoEquipe;
use App\Models\TipoMovimento;
use App\Models\Trabalhador;
use App\Services\TriagemVoluntarios;
use App\Services\PhoneService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    #[Locked]
    public Evento $evento;
    public string $situacao = 'pendente';
    public string $busca = '';
    public string $perfil = '';
    public string $ordem = 'prioridade';
    public string $interesse = '';
    public string $escala = '';
    public bool $alterandoOrigem = false;
    public bool $coordenadores = false;
    public bool $acompanhandoEscalacao = false;
    public string $origem = '';
    public string $equipe = '';
    public bool $primeiraVez = false;
    public string $motivo = '';
    public string $aviso = '';

    private const PAGINAS = [
        'pendente' => 'paginaVoluntarios',
        'escalado' => 'paginaEscalados',
        'recusado' => 'paginaNaoSelecionados',
    ];

    public function mount(Evento $evento): void
    {
        Gate::authorize('evento-tab-voluntarios', $evento);
        $this->evento = $evento;
    }

    public function escolherSituacao(string $situacao): void
    {
        abort_unless(in_array($situacao, ['pendente', 'escalado', 'recusado'], true), 404);
        $this->situacao = $situacao;
        $this->resetPage('paginaVoluntarios');
        $this->reiniciarDecisao();
    }

    public function limparFiltros(): void
    {
        $this->reset('busca', 'perfil', 'ordem', 'interesse', 'escala');
        $this->reiniciarPaginas();
        $this->reiniciarDecisao();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['busca', 'perfil', 'ordem', 'interesse', 'escala'], true)) {
            $this->reiniciarPaginas();
            $this->reiniciarDecisao();
        }
    }

    public function updatedPaginators($page, $pageName): void
    {
        if (in_array($pageName, self::PAGINAS, true)) {
            $this->reiniciarDecisao();
        }
    }

    protected function reiniciarPaginas(): void
    {
        foreach (self::PAGINAS as $pagina) {
            $this->resetPage($pagina);
        }
    }

    protected function reiniciarDecisao(): void
    {
        $this->reset('equipe', 'primeiraVez', 'motivo');
        $this->resetValidation();
    }

    protected function origens()
    {
        return Evento::withTrashed()
            ->where('idt_evento', '<>', $this->evento->getKey())
            ->where('tip_evento', TipoEvento::ENCONTRO->value)
            ->where('dat_inicio', '<', $this->evento->dat_inicio->toDateString())
            ->whereIn('idt_movimento', [TipoMovimento::VEM, TipoMovimento::SGM])
            ->orderByDesc('dat_inicio');
    }

    public function editarOrigem(): void
    {
        $this->origem = (string) $this->evento->idt_encontro_origem;
        $this->resetValidation('origem');
        $this->alterandoOrigem = true;
    }

    public function vincularOrigem(): void
    {
        Gate::authorize('evento-tab-voluntarios', $this->evento);
        $origem = $this->origens()->find($this->origem);
        if (! $origem) {
            $this->addError('origem', 'Escolha um encontro anterior disponível.');
            return;
        }

        $this->evento->forceFill(['idt_encontro_origem' => $origem->getKey()])->save();
        $this->alterandoOrigem = false;
        $this->aviso = 'Encontro de origem atualizado.';
        $this->reiniciarPaginas();
        $this->reiniciarDecisao();
    }

    public function escalar(int $pessoa): void
    {
        $this->decidir($pessoa, true);
    }

    public function naoSelecionar(int $pessoa): void
    {
        $this->decidir($pessoa, false);
    }

    protected function decidir(int $pessoa, bool $escalando): void
    {
        Gate::authorize('evento-tab-voluntarios', $this->evento);
        $id = $this->evento->getKey();
        $equipe = $escalando
            ? TipoEquipe::where('idt_movimento', $this->evento->idt_movimento)->find($this->equipe)
            : null;

        if ($escalando && ! $equipe) {
            $this->addError('equipe', 'Escolha uma equipe do evento.');
            return;
        }
        if (! $escalando) {
            $this->validate(['motivo' => ['required', 'string', 'max:500']]);
        }

        $alterado = DB::transaction(function () use ($id, $pessoa, $equipe, $escalando) {
            $evento = Evento::lockForUpdate()->findOrFail($id, ['idt_evento', 'idt_encontro_origem']);
            if (app(TriagemVoluntarios::class)->fila($evento, $pessoa)->value('situacao') !== 'pendente') {
                return false;
            }

            if ($escalando) {
                DB::table('candidatura_voluntario')->insertOrIgnore([
                    'idt_evento' => $id, 'idt_pessoa' => $pessoa, 'created_at' => now(), 'updated_at' => now(),
                ]);
                $trabalhador = Trabalhador::create([
                    'idt_evento' => $id, 'idt_pessoa' => $pessoa, 'idt_equipe' => $equipe->getKey(),
                    'ind_primeira_vez' => $this->primeiraVez,
                ]);
                DB::table('voluntario')->where('idt_pessoa', $pessoa)->where('idt_evento', $id)
                    ->update(['idt_trabalhador' => $trabalhador->getKey()]);
            } else {
                DB::table('candidatura_voluntario')->upsert([[
                    'idt_evento' => $id, 'idt_pessoa' => $pessoa, 'motivo_nao_selecao' => trim($this->motivo),
                    'created_at' => now(), 'updated_at' => now(),
                ]], ['idt_evento', 'idt_pessoa'], ['motivo_nao_selecao', 'updated_at']);
            }

            return true;
        }, 3);

        $this->aviso = match (true) {
            ! $alterado => 'A situação da candidatura já mudou.',
            $escalando => 'Voluntário escalado para '.$equipe->des_grupo.'.',
            default => 'Candidatura marcada como não selecionada.',
        };
        $this->reiniciarDecisao();
    }

    public function with(): array
    {
        Gate::authorize('evento-tab-voluntarios', $this->evento);
        $id = $this->evento->getKey();
        $origem = $this->evento->idt_encontro_origem
            ? Evento::withTrashed()->find($this->evento->idt_encontro_origem, ['idt_evento', 'des_evento', 'idt_movimento'])
            : null;
        $triagem = app(TriagemVoluntarios::class);
        $fila = $triagem->filtrar($triagem->fila($this->evento), $this->evento, $origem?->idt_movimento,
            $this->busca, $this->perfil, $this->interesse, $this->escala);
        $contagens = (clone $fila)->selectRaw('situacao, count(*) as total')->groupBy('situacao')->pluck('total', 'situacao');

        $porPagina = 20;
        $listas = [];
        foreach (self::PAGINAS as $situacao => $nomePagina) {
            $total = (int) $contagens->get($situacao, 0);
            $pagina = min($this->getPage($nomePagina), max(1, (int) ceil($total / $porPagina)));
            $itens = $total > 0
                ? $triagem->ordenar((clone $fila)->where('situacao', $situacao), $this->ordem)->forPage($pagina, $porPagina)->get()
                : collect();
            $listas[$situacao] = new LengthAwarePaginator($itens, $total, $porPagina, $pagina, [
                'path' => request()->url(), 'pageName' => $nomePagina,
            ]);
        }

        $ids = collect($listas)->flatMap(fn ($lista) => $lista->pluck('idt_pessoa'));
        $equipes = TipoEquipe::catalogo($this->evento->idt_movimento);
        $preferencias = $ids->isEmpty() ? collect() : $triagem->preferencias($id, $ids, comMotivo: true);
        $idsEscalados = $listas['escalado']->pluck('idt_pessoa');
        $escaladas = $idsEscalados->isEmpty() ? collect() : $triagem->equipesEscaladas($id, $idsEscalados);
        $metadados = $ids->isEmpty() ? collect() : DB::table('candidatura_voluntario')->where('idt_evento', $id)
            ->whereIn('idt_pessoa', $ids)->get([
                'idt_pessoa', 'ind_publica', 'tipo_candidatura', 'equipes_anteriores', 'instrumento', 'ind_canta',
                'observacoes', 'motivo_nao_selecao', 'created_at',
                'nome_conjuge', 'apelido_conjuge', 'telefone_conjuge', 'email_conjuge',
            ])->keyBy('idt_pessoa');

        $candidaturas = collect($listas)->map(fn ($lista) => $lista->getCollection()->map(function ($pessoa) use (
            $origem, $preferencias, $escaladas, $metadados, $equipes
        ) {
            $meta = $metadados->get($pessoa->idt_pessoa);
            $historico = json_decode($meta?->equipes_anteriores ?? '[]', true) ?? [];
            $pessoa->selo = $pessoa->prioritario
                ? ($origem->idt_movimento === TipoMovimento::VEM ? 'Ex-venista' : 'Ex-seguimista') : null;
            $pessoa->equipe_escalada = $escaladas->get($pessoa->idt_pessoa)?->nome;
            $interesses = $preferencias->get($pessoa->idt_pessoa, collect());
            $pessoa->equipes_interesse = $interesses->pluck('nome')->filter()->implode(' · ');
            $pessoa->tipo_candidatura = $meta?->tipo_candidatura ? ucfirst($meta->tipo_candidatura) : null;

            return [
                'id' => (int) $pessoa->idt_pessoa,
                'nome' => $pessoa->nom_pessoa,
                'iniciais' => mb_strtoupper(mb_substr($pessoa->nom_pessoa, 0, 1)),
                'apelido' => $pessoa->nom_apelido,
                'selo' => $pessoa->selo,
                'coordenador' => (bool) $pessoa->coordenador,
                'equipe' => $pessoa->equipe_escalada,
                'preferencias' => $interesses->map(fn ($preferencia) => [
                    'nome' => $preferencia->nome ?? 'Equipe indisponível',
                    'motivo' => $preferencia->txt_habilidade,
                ])->all(),
                'data' => $meta?->ind_publica ? Carbon::parse($meta->created_at)->format('d/m/Y') : null,
                'telefone' => PhoneService::format($pessoa->tel_pessoa),
                'idade' => $pessoa->dat_nascimento ? Carbon::parse($pessoa->dat_nascimento)->age : null,
                'historico' => $equipes->whereIn('idt_equipe', $historico)->pluck('des_grupo')->implode(' · '),
                'musica' => collect([$meta?->instrumento, $meta?->ind_canta ? 'Canta' : null])->filter()->implode(' · '),
                'observacoes' => $meta?->observacoes,
                'tipoCandidatura' => $pessoa->tipo_candidatura,
                'conjuge' => $meta?->nome_conjuge ? [
                    'nome' => $meta->nome_conjuge,
                    'apelido' => $meta->apelido_conjuge,
                    'telefone' => PhoneService::format($meta->telefone_conjuge),
                    'email' => $meta->email_conjuge,
                ] : null,
                'recusa' => $meta?->motivo_nao_selecao,
            ];
        })->all());

        return [
            'contagens' => $contagens,
            'listas' => $listas,
            'candidaturas' => $candidaturas,
            'equipes' => $equipes,
            'origens' => $this->alterandoOrigem ? $this->origens()->get(['idt_evento', 'des_evento', 'dat_inicio']) : collect(),
            'vinculado' => $origem,
            'resumoEscalacao' => $this->acompanhandoEscalacao ? $triagem->resumoEscalacao($this->evento, $equipes) : null,
        ];
    }
}; ?>

<div class="space-y-5" x-data="triagemVoluntarios" data-candidaturas="{{ json_encode($candidaturas, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) }}">
    <header class="flex flex-wrap items-center justify-between gap-3">
        <flux:heading size="xl" class="font-bold text-indigo-950 dark:text-indigo-100">Triagem de voluntários</flux:heading>
        <div class="flex flex-wrap items-center gap-2">
            <flux:button size="sm" icon="chart-bar" wire:click="$set('acompanhandoEscalacao', true)">Acompanhar escalação</flux:button>
            <flux:button size="sm" icon="user-group" wire:click="$toggle('coordenadores')">Coordenadores</flux:button>
            @if ($vinculado)
                <flux:badge color="indigo">{{ $vinculado->des_evento }}</flux:badge>
                <flux:button size="sm" icon="arrows-right-left" aria-label="Trocar encontro" title="Trocar encontro" wire:click="editarOrigem" />
            @else
                <flux:button size="sm" icon="link" aria-label="Vincular encontro anterior" wire:click="editarOrigem">
                    <span class="sm:hidden">Vincular origem</span>
                    <span class="hidden sm:inline">Vincular encontro anterior</span>
                </flux:button>
            @endif
        </div>
    </header>

    <flux:modal wire:model.live="acompanhandoEscalacao" :closable="false"
        x-data="modalTriagem('titulo-acompanhamento-escalacao')"
        class="triagem-modal w-[calc(100%-1rem)] max-w-3xl max-h-[calc(100dvh-1rem)] overflow-y-auto p-0">
        <header class="sticky top-0 z-10 flex flex-wrap items-start justify-between gap-2 border-b border-indigo-100 bg-indigo-50 px-5 py-4 dark:border-indigo-900 dark:bg-indigo-950">
            <div>
                <flux:heading id="titulo-acompanhamento-escalacao" level="2" size="lg" class="text-indigo-950 dark:text-indigo-100">Acompanhamento da escalação</flux:heading>
                <flux:subheading>Distribuição dos trabalhadores por equipe.</flux:subheading>
            </div>
            <flux:button size="sm" type="button" icon="x-mark" variant="ghost" data-modal-initial-focus autofocus
                wire:click="$set('acompanhandoEscalacao', false)">Fechar</flux:button>
        </header>

        @if ($resumoEscalacao)
            <div class="space-y-4 p-4 sm:p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm text-zinc-700 dark:text-zinc-300">
                        <strong class="text-xl tabular-nums text-indigo-950 dark:text-indigo-100">{{ $resumoEscalacao['total'] }}</strong>
                        no total do evento
                    </p>
                    <flux:button size="sm" type="button" icon="arrow-path" wire:click="$refresh" wire:loading.attr="disabled">Atualizar</flux:button>
                </div>

                @if ($resumoEscalacao['equipes']->isEmpty())
                    <flux:text>Nenhuma equipe cadastrada para este evento.</flux:text>
                @else
                    <ul aria-label="Equipes" class="grid gap-3 sm:grid-cols-2">
                        @foreach ($resumoEscalacao['equipes'] as $equipe)
                            <li wire:key="acompanhamento-equipe-{{ $equipe['id'] }}" class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                                <h3 class="break-words text-sm font-semibold">{{ $equipe['nome'] }}</h3>
                                <dl class="mt-3 grid grid-cols-2 gap-2 text-sm">
                                    <div>
                                        <dt class="text-zinc-600 dark:text-zinc-300">Escalados</dt>
                                        <dd class="text-xl font-semibold tabular-nums">{{ $equipe['escalados'] }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-zinc-600 dark:text-zinc-300">Coordenadores</dt>
                                        <dd class="text-xl font-semibold tabular-nums">{{ $equipe['coordenadores'] }}</dd>
                                    </div>
                                </dl>
                            </li>
                        @endforeach
                    </ul>
                    <flux:text>O número de escalados já inclui os coordenadores vinculados.</flux:text>
                @endif
            </div>
        @endif
    </flux:modal>

    <flux:modal wire:model.live="coordenadores" :closable="false"
        x-data="modalTriagem('titulo-coordenadores')"
        class="triagem-modal w-[calc(100%-1rem)] max-w-6xl max-h-[calc(100dvh-1rem)] overflow-y-auto p-0">
        @if ($coordenadores)
            <livewire:evento.partials.coordenadores :evento="$evento" @coordenadores-atualizados="$refresh"
                @painel-coordenadores-fechado="$set('coordenadores', false)" />
        @endif
    </flux:modal>

    <flux:modal wire:model.live="alterandoOrigem" x-data="modalTriagem('vinculo-encontro-titulo')"
        class="triagem-modal w-[calc(100%-1rem)] max-w-lg p-0">
        <header class="border-b border-indigo-100 bg-indigo-50 px-5 py-4 pe-12 dark:border-indigo-900 dark:bg-indigo-950">
            <flux:heading id="vinculo-encontro-titulo" level="2" size="lg" class="text-indigo-950 dark:text-indigo-100">
                {{ $vinculado ? 'Trocar encontro vinculado' : 'Vincular encontro anterior' }}
            </flux:heading>
            <flux:subheading>Defina quem terá prioridade na fila de voluntários.</flux:subheading>
        </header>
        <form wire:submit="vincularOrigem">
            <div class="space-y-4 p-5">
                @if ($vinculado)
                    <div class="flex flex-wrap items-center gap-2 text-sm">Encontro atual <flux:badge color="indigo">{{ $vinculado->des_evento }}</flux:badge></div>
                @endif
                <flux:select label="Encontro de origem" wire:model="origem" data-modal-initial-focus autofocus>
                    <option value="">Selecione um encontro anterior</option>
                    @foreach ($origens as $encontro)
                        <option value="{{ $encontro->getKey() }}">{{ $encontro->des_evento }} · {{ $encontro->getDataInicioFormatada() }}</option>
                    @endforeach
                </flux:select>
                <flux:text>Candidaturas e escalações já registradas são preservadas ao trocar o encontro.</flux:text>
            </div>
            <div class="flex flex-wrap justify-end gap-2 border-t border-zinc-200 bg-zinc-50 px-5 py-4 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:button type="button" size="sm" wire:click="$set('alterandoOrigem', false)">Cancelar</flux:button>
                <flux:button type="submit" size="sm" variant="primary" class="bg-indigo-700! text-white! hover:bg-indigo-800!">
                    {{ $vinculado ? 'Confirmar troca' : 'Vincular participantes' }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <div class="grid grid-cols-3 gap-2" role="group" aria-label="Situação da candidatura">
        @foreach ([
            'pendente' => ['Aguardando escalação', 'clock'],
            'escalado' => ['Escalados', 'check-circle'],
            'recusado' => ['Não selecionados', 'minus-circle'],
        ] as $chave => [$rotulo, $icone])
            <button type="button" x-on:click="escolherSituacao('{{ $chave }}')"
                x-bind:aria-pressed="situacao === '{{ $chave }}'" aria-label="{{ $rotulo }} ({{ $contagens->get($chave, 0) }})"
                class="flex min-h-11 flex-col items-start gap-1 rounded-xl border border-zinc-200 bg-white px-2 py-2 text-left text-xs font-semibold sm:flex-row sm:items-center sm:gap-2 sm:px-3 sm:text-sm
                    text-zinc-700 hover:border-indigo-400 aria-pressed:border-indigo-600 aria-pressed:bg-indigo-50 aria-pressed:text-indigo-900
                    focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500
                    dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:aria-pressed:border-indigo-400 dark:aria-pressed:bg-indigo-950 dark:aria-pressed:text-indigo-100">
                <flux:icon :name="$icone" class="hidden size-4 shrink-0 sm:block" aria-hidden="true" />
                @if ($chave === 'pendente')
                    <span class="sm:hidden">Aguardando</span>
                    <span class="hidden sm:inline">{{ $rotulo }}</span>
                @else
                    <span>{{ $rotulo }}</span>
                @endif
                <span aria-hidden="true" class="tabular-nums sm:ms-auto">{{ $contagens->get($chave, 0) }}</span>
            </button>
        @endforeach
    </div>

    <div class="flex items-center gap-2">
        <flux:input aria-label="Buscar pessoa" placeholder="Buscar por nome ou apelido" icon="magnifying-glass"
            class="min-w-0 flex-1 md:max-w-sm" wire:model.live.debounce.350ms="busca" />
        <flux:button type="button" icon="funnel" aria-controls="filtros-voluntarios"
            x-bind:aria-expanded="filtrosAbertos" x-on:click="filtrosAbertos = !filtrosAbertos">Filtros</flux:button>
        <p wire:loading.delay wire:target="busca,perfil,ordem,interesse,escala,setPage"
            role="status" class="text-sm text-zinc-600 dark:text-zinc-300">Atualizando…</p>
    </div>

    <div id="filtros-voluntarios" x-show="filtrosAbertos" x-cloak>
        <flux:card class="space-y-3">
            <div class="flex items-center justify-between gap-2">
                <flux:heading>Filtros e ordenação</flux:heading>
                <flux:button size="sm" variant="ghost" wire:click="limparFiltros">Limpar filtros</flux:button>
            </div>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <flux:select label="Ordenar por" wire:model.live="ordem">
                    <option value="prioridade">Prioridade</option>
                    <option value="nome-asc">Nome (A–Z)</option>
                    <option value="nome-desc">Nome (Z–A)</option>
                </flux:select>
                <flux:select label="Perfil" wire:model.live="perfil">
                    <option value="">Todos os perfis</option>
                    <option value="publica">Candidatura pública</option>
                    <option value="coordenador">Coordenador</option>
                    <option value="vem">Ex-venista</option>
                    <option value="sgm">Ex-seguimista</option>
                </flux:select>
                @foreach (['pendente' => ['Equipe de interesse', 'interesse'], 'escalado' => ['Equipe escalada', 'escala']] as $estado => [$label, $campo])
                    <div x-show="situacao === '{{ $estado }}'">
                        <flux:select :label="$label" wire:model.live="{{ $campo }}">
                            <option value="">Todas as equipes</option>
                            @foreach ($equipes as $tipo)
                                <option value="{{ $tipo->getKey() }}">{{ $tipo->des_grupo }}</option>
                            @endforeach
                        </flux:select>
                    </div>
                @endforeach
            </div>
        </flux:card>
    </div>

    @if ($aviso)
        <flux:callout variant="success" icon="check-circle" role="status">
            <flux:callout.text>{{ $aviso }}</flux:callout.text>
        </flux:callout>
    @endif

    <div class="grid items-start gap-5 lg:grid-cols-[minmax(15rem,1fr)_minmax(0,2fr)]">
        <section aria-labelledby="lista-candidaturas-titulo" class="min-w-0 overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <h2 id="lista-candidaturas-titulo" class="border-b border-zinc-200 px-4 py-3 text-sm font-semibold dark:border-zinc-700">Candidaturas</h2>
            @foreach ($listas as $estado => $lista)
                <div wire:key="triagem-lista-{{ $estado }}" x-show="situacao === '{{ $estado }}'" x-cloak>
                    <ul class="max-h-64 divide-y divide-zinc-100 overflow-y-auto p-1 lg:max-h-[34rem] dark:divide-zinc-800" aria-label="Candidaturas">
                        @forelse ($lista as $pessoa)
                            <li wire:key="candidato-{{ $pessoa->idt_pessoa }}">
                                <button type="button" x-on:click="selecionar({{ $pessoa->idt_pessoa }}, true)"
                                    x-bind:aria-pressed="selecao === {{ $pessoa->idt_pessoa }}"
                                    class="flex w-full items-center gap-3 rounded-lg px-3 py-3 text-left hover:bg-zinc-50
                                        aria-pressed:bg-indigo-50 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-indigo-500
                                        dark:hover:bg-zinc-800 dark:aria-pressed:bg-indigo-950">
                                    <span class="flex w-10 shrink-0 flex-col items-center gap-1">
                                        <span aria-hidden="true" class="grid size-9 place-items-center rounded-full bg-indigo-100 text-sm font-semibold text-indigo-800 dark:bg-indigo-900 dark:text-indigo-100">
                                            {{ mb_strtoupper(mb_substr($pessoa->nom_pessoa, 0, 1)) }}
                                        </span>
                                        @if ($pessoa->tipo_candidatura)
                                            <span class="text-[11px] leading-tight text-zinc-600 dark:text-zinc-400">{{ $pessoa->tipo_candidatura }}</span>
                                        @endif
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block break-words text-sm font-semibold">{{ $pessoa->nom_pessoa }}</span>
                                        @if ($pessoa->nom_apelido)
                                            <span class="block break-words text-sm text-zinc-600 dark:text-zinc-300">{{ $pessoa->nom_apelido }}</span>
                                        @endif
                                        @if ($estado === 'pendente')
                                            <span class="mt-1 block break-words text-xs text-zinc-600 dark:text-zinc-300">
                                                {{ $pessoa->equipes_interesse ?: 'Nenhuma equipe de interesse informada' }}
                                            </span>
                                        @endif
                                        @if ($pessoa->selo || $pessoa->coordenador || $pessoa->equipe_escalada)
                                            <span class="mt-1 flex flex-wrap gap-1">
                                                @if ($pessoa->selo) <flux:badge color="amber" size="sm">{{ $pessoa->selo }}</flux:badge> @endif
                                                @if ($pessoa->coordenador) <flux:badge color="indigo" size="sm">Coordenador</flux:badge> @endif
                                                @if ($pessoa->equipe_escalada) <flux:badge color="green" size="sm">{{ $pessoa->equipe_escalada }}</flux:badge> @endif
                                            </span>
                                        @endif
                                    </span>
                                    <flux:icon.chevron-right class="size-4 shrink-0 text-zinc-500 dark:text-zinc-400" aria-hidden="true" />
                                </button>
                            </li>
                        @empty
                            <li class="px-4 py-8 text-sm text-zinc-600 dark:text-zinc-300">Nenhuma candidatura nesta situação com os filtros atuais.</li>
                        @endforelse
                    </ul>
                    @if ($lista->hasPages())
                        <div class="border-t border-zinc-200 p-3 dark:border-zinc-700">
                            {{ $lista->links(data: ['scrollTo' => false]) }}
                        </div>
                    @endif
                </div>
            @endforeach
        </section>

        <section x-show="candidato" x-cloak aria-labelledby="candidatura-selecionada"
            class="min-w-0 overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <header class="flex flex-wrap items-center gap-3 border-b border-indigo-100 bg-indigo-50/70 p-4 sm:p-5 dark:border-indigo-900 dark:bg-indigo-950/50">
                <div class="flex w-12 shrink-0 flex-col items-center gap-1">
                    <span aria-hidden="true" x-text="candidato?.iniciais"
                        class="grid size-12 place-items-center rounded-full bg-indigo-100 text-lg font-bold text-indigo-800 dark:bg-indigo-900 dark:text-indigo-100"></span>
                    <span class="text-center text-[11px] leading-tight text-zinc-600 dark:text-zinc-400"
                        x-text="candidato?.tipoCandidatura || 'Tipo não informado'"></span>
                </div>
                <div class="min-w-0 flex-1">
                    <h2 id="candidatura-selecionada" tabindex="-1" x-ref="tituloCandidatura" x-text="candidato?.nome"
                        class="scroll-mt-6 break-words rounded text-xl font-bold text-indigo-950 focus:outline-2 focus:outline-offset-4 focus:outline-indigo-500 dark:text-indigo-100"></h2>
                    <p x-show="candidato?.apelido || candidato?.data" class="text-sm text-zinc-700 dark:text-zinc-300"
                        x-text="[candidato?.apelido, candidato?.data ? 'Recebida em ' + candidato.data : null].filter(Boolean).join(' · ')"></p>
                </div>
                <div class="flex flex-wrap gap-1.5">
                    <flux:badge color="amber" x-show="candidato?.selo"><span x-text="candidato?.selo"></span></flux:badge>
                    <flux:badge color="indigo" x-show="candidato?.coordenador">Coordenador</flux:badge>
                    <flux:badge color="green" x-show="candidato?.equipe"><span x-text="candidato?.equipe"></span></flux:badge>
                </div>
            </header>
            <div class="space-y-5 p-4 sm:p-5">
                <div class="space-y-2">
                    <h3 class="font-semibold">Equipes desejadas</h3>
                    <template x-for="(preferencia, indice) in candidato?.preferencias ?? []" :key="indice">
                        <div class="rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-700">
                            <p class="text-sm font-semibold" x-text="preferencia.nome"></p>
                            <p x-show="preferencia.motivo" class="whitespace-pre-wrap break-words text-sm text-zinc-600 dark:text-zinc-300" x-text="preferencia.motivo"></p>
                        </div>
                    </template>
                    <flux:text x-show="!candidato?.preferencias.length">Nenhuma preferência informada.</flux:text>
                </div>

                <section class="space-y-3 border-t border-zinc-200 pt-4 dark:border-zinc-700" aria-labelledby="perfil-voluntario-titulo">
                    <h3 id="perfil-voluntario-titulo" class="font-semibold">Sobre o voluntário</h3>
                    <dl class="grid gap-3 text-sm sm:grid-cols-2">
                        <div x-show="candidato?.telefone"><dt class="font-semibold">Telefone</dt><dd x-text="candidato?.telefone"></dd></div>
                        <div x-show="candidato?.idade != null"><dt class="font-semibold">Idade</dt><dd x-text="candidato?.idade + ' anos'"></dd></div>
                        <div><dt class="font-semibold">Experiência anterior</dt><dd x-text="candidato?.historico || 'Não informada'"></dd></div>
                        <div><dt class="font-semibold">Música</dt><dd x-text="candidato?.musica || 'Não informada'"></dd></div>
                        <div class="sm:col-span-2"><dt class="font-semibold">Observações</dt>
                            <dd class="whitespace-pre-wrap break-words" x-text="candidato?.observacoes || 'Nenhuma observação'"></dd>
                        </div>
                    </dl>
                </section>
                <section x-show="candidato?.conjuge" class="space-y-3 border-t border-zinc-200 pt-4 dark:border-zinc-700"
                    aria-labelledby="dados-conjuge-titulo">
                    <h3 id="dados-conjuge-titulo" class="font-semibold">Dados do cônjuge</h3>
                    <dl class="grid gap-3 text-sm sm:grid-cols-2">
                        <div><dt class="font-semibold">Nome</dt><dd class="break-words" x-text="candidato?.conjuge?.nome"></dd></div>
                        <div x-show="candidato?.conjuge?.apelido"><dt class="font-semibold">Apelido</dt><dd class="break-words" x-text="candidato?.conjuge?.apelido"></dd></div>
                        <div><dt class="font-semibold">Telefone</dt><dd x-text="candidato?.conjuge?.telefone"></dd></div>
                        <div><dt class="font-semibold">E-mail</dt><dd class="break-words" x-text="candidato?.conjuge?.email"></dd></div>
                    </dl>
                </section>

                <div x-show="situacao === 'recusado'" class="rounded-lg bg-zinc-100 p-3 dark:bg-zinc-800">
                    <h3 class="font-semibold">Motivo da não seleção</h3>
                    <p class="whitespace-pre-wrap break-words text-sm" x-text="candidato?.recusa"></p>
                </div>
            </div>

            <div x-show="situacao === 'pendente'" class="space-y-3 border-t border-zinc-200 bg-zinc-50 p-4 sm:p-5 dark:border-zinc-700 dark:bg-zinc-800">
                <div x-show="!recusando" class="space-y-3">
                    <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end">
                        <flux:select label="Equipe de escalação" wire:model="equipe">
                            <option value="">Selecione uma equipe</option>
                            @foreach ($equipes as $tipo)
                                <option value="{{ $tipo->getKey() }}">{{ $tipo->des_grupo }}</option>
                            @endforeach
                        </flux:select>
                        <flux:button type="button" variant="primary" x-on:click="candidato && $wire.escalar(candidato.id)"
                            class="bg-indigo-700! text-white! hover:bg-indigo-800!" wire:loading.attr="disabled" wire:target="escalar">
                            <span wire:loading.remove wire:target="escalar">Confirmar escalação</span>
                            <span wire:loading wire:target="escalar">Salvando…</span>
                        </flux:button>
                    </div>
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <flux:checkbox label="Primeira vez como trabalhador" wire:model="primeiraVez" />
                        <flux:button type="button" variant="ghost" size="sm" x-on:click="recusando = true; $nextTick(() => $refs.motivoRecusa.focus())">
                            Marcar como não selecionado
                        </flux:button>
                    </div>
                </div>
                <div x-show="recusando" class="space-y-3">
                    <flux:textarea label="Motivo da não seleção" wire:model="motivo" x-ref="motivoRecusa" maxlength="500" rows="2" required />
                    <div class="flex flex-wrap gap-2">
                        <flux:button type="button" variant="danger" x-on:click="candidato && $wire.naoSelecionar(candidato.id)"
                            wire:loading.attr="disabled" wire:target="naoSelecionar">Confirmar não seleção</flux:button>
                        <flux:button type="button" variant="ghost" x-on:click="recusando = false">Cancelar</flux:button>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
