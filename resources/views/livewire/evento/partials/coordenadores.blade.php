<?php

use App\Models\Evento;
use App\Models\Pessoa;
use App\Models\TipoEquipe;
use App\Models\Trabalhador;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

new class extends Component {
    #[Locked]
    public Evento $evento;
    #[Locked]
    public ?int $equipeId = null;
    public string $modo = '';
    public string $busca = '';
    public string $nome = '';
    public string $apelido = '';
    public string $telefone = '';

    protected function indicacoes()
    {
        return DB::table('coordenador_evento')->where('idt_evento', $this->evento->getKey());
    }

    protected function escalados()
    {
        return Trabalhador::where('idt_evento', $this->evento->getKey())->where('ind_coordenador', true);
    }

    public function abrir(int $equipe, bool $buscar = false): void
    {
        Gate::authorize('evento-tab-voluntarios', $this->evento);
        TipoEquipe::where('idt_movimento', $this->evento->idt_movimento)->findOrFail($equipe);
        $this->resetValidation();
        $this->equipeId = $equipe;
        $indicacao = $this->indicacoes()->where('idt_equipe', $equipe)->first();
        $pessoa = $indicacao ? null : $this->escalados()->where('idt_equipe', $equipe)->first()?->pessoa;
        $this->modo = $buscar || (! $indicacao && ! $pessoa) ? 'busca' : 'edicao';
        $this->busca = '';
        $this->nome = $indicacao->nome ?? $pessoa?->nom_pessoa ?? '';
        $this->apelido = $indicacao->apelido ?? $pessoa?->nom_apelido ?? '';
        $this->telefone = $indicacao->telefone ?? $pessoa?->tel_pessoa ?? '';
    }

    public function semCadastro(): void
    {
        abort_unless($this->equipeId && $this->modo === 'busca', 404);
        $this->nome = trim($this->busca);
        $this->reset('apelido', 'telefone');
        $this->modo = 'novo';
    }

    public function fechar(): void
    {
        $this->equipeId = null;
    }

    public function vincular(int $id): void
    {
        $pessoa = Pessoa::findOrFail($id);
        $this->gravar($pessoa->nom_pessoa, $pessoa->nom_apelido, $this->digitos($pessoa->tel_pessoa), $id);
    }

    public function salvar(): void
    {
        $dados = $this->validate([
            'nome' => ['required', 'string', 'max:255'],
            'apelido' => ['nullable', 'string', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:20'],
        ]);
        $telefone = $this->digitos($dados['telefone']);
        $pessoaId = $this->modo === 'edicao' ? (
            $this->indicacoes()->where('idt_equipe', $this->equipeId)->value('idt_pessoa')
            ?? $this->escalados()->where('idt_equipe', $this->equipeId)->value('idt_pessoa')
        ) : null;
        if ((! $pessoaId || $telefone) && ! in_array(strlen($telefone ?? ''), [10, 11], true)) {
            throw ValidationException::withMessages(['telefone' => 'Informe um telefone com DDD.']);
        }
        $this->gravar($dados['nome'], $dados['apelido'], $telefone, $pessoaId);
    }

    protected function digitos(?string $telefone): ?string
    {
        return preg_replace('/\D/', '', $telefone ?? '') ?: null;
    }

    protected function gravar(string $nome, ?string $apelido, ?string $telefone, ?int $pessoa): void
    {
        Gate::authorize('evento-tab-voluntarios', $this->evento);
        $equipe = TipoEquipe::where('idt_movimento', $this->evento->idt_movimento)->findOrFail($this->equipeId);
        $id = $this->evento->getKey();

        DB::transaction(function () use ($id, $equipe, $nome, $apelido, $telefone, $pessoa) {
            Evento::lockForUpdate()->findOrFail($id);
            $duplicado = $this->indicacoes()->where('idt_equipe', '<>', $equipe->getKey())
                ->where(function ($query) use ($pessoa, $telefone) {
                    $pessoa ? $query->where('idt_pessoa', $pessoa) : $query->whereRaw('1 = 0');
                    if ($telefone) $query->orWhere('telefone', $telefone);
                })->value('idt_equipe');
            $duplicado ??= $pessoa ? $this->escalados()->where('idt_pessoa', $pessoa)
                ->where('idt_equipe', '<>', $equipe->getKey())->value('idt_equipe') : null;

            if ($duplicado) {
                $nomeEquipe = TipoEquipe::withTrashed()->find($duplicado)?->des_grupo;
                throw ValidationException::withMessages(['vinculo' => 'Já vinculado à equipe '.$nomeEquipe.'.']);
            }

            $atuais = $this->escalados()->where('idt_equipe', $equipe->getKey());
            if ($pessoa) {
                $trabalho = Trabalhador::where('idt_evento', $id)->where('idt_pessoa', $pessoa)
                    ->where('idt_equipe', $equipe->getKey())->first();
                if (! $trabalho && Trabalhador::where('idt_evento', $id)->where('idt_pessoa', $pessoa)->exists()) {
                    throw ValidationException::withMessages(['vinculo' => 'Esta pessoa já está escalada em outra equipe.']);
                }
                (clone $atuais)->where('idt_pessoa', '<>', $pessoa)->update(['ind_coordenador' => false]);
                $trabalho ??= new Trabalhador(['idt_evento' => $id, 'idt_pessoa' => $pessoa, 'idt_equipe' => $equipe->getKey()]);
                $trabalho->ind_coordenador = true;
                $trabalho->save();
                Pessoa::findOrFail($pessoa)->voluntarios()->where('idt_evento', $id)
                    ->update(['idt_trabalhador' => $trabalho->getKey()]);
            } else {
                $atuais->update(['ind_coordenador' => false]);
            }

            DB::table('coordenador_evento')->updateOrInsert(
                ['idt_evento' => $id, 'idt_equipe' => $equipe->getKey()],
                ['idt_pessoa' => $pessoa, 'nome' => trim($nome), 'apelido' => trim($apelido ?? '') ?: null, 'telefone' => $telefone],
            );
        }, 3);

        $this->equipeId = null;
        $this->dispatch('coordenadores-atualizados');
    }

    public function desvincular(): void
    {
        Gate::authorize('evento-tab-voluntarios', $this->evento);
        abort_unless($this->equipeId, 404);
        DB::transaction(function () {
            Evento::lockForUpdate()->findOrFail($this->evento->getKey());
            $this->indicacoes()->where('idt_equipe', $this->equipeId)->delete();
            $this->escalados()->where('idt_equipe', $this->equipeId)->update(['ind_coordenador' => false]);
        }, 3);
        $this->equipeId = null;
        $this->dispatch('coordenadores-atualizados');
    }

    public function with(): array
    {
        Gate::authorize('evento-tab-voluntarios', $this->evento);
        $equipes = TipoEquipe::where('idt_movimento', $this->evento->idt_movimento)->orderBy('des_grupo')->get()->keyBy('idt_equipe');
        $indicacoes = $this->indicacoes()->get()->keyBy('idt_equipe');
        $escalados = $this->escalados()->with('pessoa')->get();
        $porPessoa = $indicacoes->whereNotNull('idt_pessoa')->pluck('idt_equipe', 'idt_pessoa')
            ->union($escalados->pluck('idt_equipe', 'idt_pessoa'));
        $porTelefone = $indicacoes->whereNotNull('telefone')->pluck('idt_equipe', 'telefone');
        $resultados = collect();

        if ($this->modo === 'busca' && mb_strlen(trim($this->busca)) >= 2) {
            $busca = '%'.trim($this->busca).'%';
            $resultados = Pessoa::where(fn ($query) => $query->where('nom_pessoa', 'like', $busca)
                ->orWhere('nom_apelido', 'like', $busca))->orderBy('nom_pessoa')->limit(20)->get();
        }

        return compact('equipes', 'indicacoes', 'escalados', 'resultados', 'porPessoa', 'porTelefone');
    }
}; ?>

<div>
    <header class="sticky top-0 z-10 flex flex-wrap items-start justify-between gap-2 border-b border-indigo-100 bg-indigo-50 px-5 py-4 dark:border-indigo-900 dark:bg-indigo-950">
        <div class="min-w-0">
            <flux:heading id="titulo-coordenadores" level="2" size="lg" class="text-indigo-950 dark:text-indigo-100">Indicação de coordenadores</flux:heading>
            <flux:subheading>Indique ou altere a pessoa responsável por cada equipe.</flux:subheading>
        </div>
        <flux:button type="button" size="sm" icon="x-mark" variant="ghost" data-modal-initial-focus autofocus
            wire:click="$dispatch('painel-coordenadores-fechado')">Fechar painel</flux:button>
    </header>
    <div class="space-y-5 p-4 sm:p-5">
        @if ($equipeId && $equipes->has($equipeId))
            <flux:card class="space-y-4 border-indigo-200 bg-indigo-50/50 dark:border-indigo-800 dark:bg-indigo-950/40"
                x-data x-init="$nextTick(() => $el.querySelector('[data-edicao-coordenador]').focus())"
                wire:key="editar-coordenador-{{ $equipeId }}-{{ $modo }}">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <flux:text>{{ $modo === 'edicao' ? 'Editar coordenador' : 'Indicar coordenador' }}</flux:text>
                        <flux:heading level="3" size="lg" tabindex="-1" data-edicao-coordenador
                            class="scroll-mt-28 rounded focus:outline-2 focus:outline-offset-4 focus:outline-indigo-500">{{ $equipes[$equipeId]->des_grupo }}</flux:heading>
                    </div>
                    <flux:button size="sm" variant="ghost" wire:click="fechar">Fechar edição</flux:button>
                </div>
                @error('vinculo') <p role="alert" class="text-sm text-red-700 dark:text-red-300">{{ $message }}</p> @enderror
                @if ($modo === 'busca')
                    <flux:input label="Buscar pessoa cadastrada" placeholder="Nome ou apelido" icon="magnifying-glass" wire:model.live.debounce.250ms="busca" />
                    @foreach ($resultados as $resultado)
                        @php
                            $outra = $porPessoa->get($resultado->getKey()) ?? $porTelefone->get($this->digitos($resultado->tel_pessoa));
                            $bloqueado = $outra && (int) $outra !== $equipeId;
                        @endphp
                        <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                            <div>
                                <p>{{ $resultado->nom_pessoa }} @if($resultado->nom_apelido) ({{ $resultado->nom_apelido }}) @endif</p>
                                @if ($bloqueado)
                                    <flux:text>Já vinculado à equipe {{ $equipes->get($outra)?->des_grupo ?? 'indisponível' }}.</flux:text>
                                @endif
                            </div>
                            <flux:button size="sm" :disabled="$bloqueado" wire:click="vincular({{ $resultado->getKey() }})">Vincular</flux:button>
                        </div>
                    @endforeach
                    @if (mb_strlen(trim($busca)) >= 2 && $resultados->isEmpty())
                        <flux:text>Nenhuma pessoa encontrada.</flux:text>
                        <flux:button size="sm" wire:click="semCadastro">Vincular coordenador sem cadastro</flux:button>
                    @endif
                @else
                    <form wire:submit="salvar" class="space-y-3">
                        <div class="grid gap-3 sm:grid-cols-3">
                            <flux:input label="Nome completo" wire:model="nome" required />
                            <flux:input label="Apelido" wire:model="apelido" />
                            <flux:input label="Telefone com DDD" type="tel" wire:model="telefone" />
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <flux:button type="submit" variant="primary" size="sm">Salvar indicação</flux:button>
                            <flux:button size="sm" wire:click="abrir({{ $equipeId }}, true)">Buscar cadastro</flux:button>
                            @if ($modo === 'edicao')
                                <flux:button size="sm" variant="danger" wire:click="desvincular"
                                    wire:confirm="Desvincular a coordenação desta equipe? A escalação como trabalhador será preservada.">
                                    Desvincular coordenador
                                </flux:button>
                            @endif
                        </div>
                    </form>
                @endif
            </flux:card>
        @endif

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($equipes as $id => $tipo)
                @php
                    $indicacao = $indicacoes->get($id);
                    $pessoa = $escalados->firstWhere('idt_equipe', $id)?->pessoa;
                    $nome = $indicacao->nome ?? $pessoa?->nom_pessoa;
                    $apelido = $indicacao->apelido ?? $pessoa?->nom_apelido;
                    $telefone = preg_replace('/\D/', '', $indicacao->telefone ?? $pessoa?->tel_pessoa ?? '');
                    $mensagem = 'Olá, '.($apelido ?: $nome).'! Poderia se cadastrar no nosso sistema, por favor?';
                @endphp
                <flux:card class="flex min-w-0 flex-col gap-3 bg-white p-4 dark:bg-zinc-900" wire:key="coordenacao-{{ $id }}">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <flux:heading level="3" class="break-words font-bold">{{ $tipo->des_grupo }}</flux:heading>
                        <flux:badge size="sm" :color="$pessoa ? 'green' : ($nome ? 'amber' : 'zinc')">
                            {{ $pessoa ? 'Coordenador vinculado' : ($nome ? 'Cadastro pendente' : 'Sem coordenador') }}
                        </flux:badge>
                    </div>
                    @if ($nome)
                        <p class="break-words text-sm font-semibold">{{ $nome }} @if ($apelido) ({{ $apelido }}) @endif</p>
                        @if (!$pessoa && in_array(strlen($telefone), [10, 11], true))
                            <flux:button size="sm" variant="ghost" :href="'https://wa.me/55'.$telefone.'?text='.rawurlencode($mensagem)"
                                target="_blank" rel="noopener noreferrer">Pedir cadastro pelo WhatsApp</flux:button>
                        @endif
                    @endif
                    <flux:button size="sm" icon="pencil-square" wire:click="abrir({{ $id }})" class="mt-auto w-full"
                        :aria-label="($nome ? 'Editar coordenador' : 'Indicar coordenador').' — '.$tipo->des_grupo">
                        {{ $nome ? 'Editar coordenador' : 'Indicar coordenador' }}
                    </flux:button>
                </flux:card>
            @empty
                <flux:text>Nenhuma equipe disponível para este movimento.</flux:text>
            @endforelse
        </div>
    </div>
</div>
