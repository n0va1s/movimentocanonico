<?php

use App\Models\TipoParoquia;
use App\Models\TipoMovimento;
use App\Models\TipoEquipe;
use Livewire\Volt\Component;
use Livewire\Attributes\Title;
use Livewire\WithFileUploads;

new #[Title('Organização')] class extends Component {
    use WithFileUploads;

    // ── Seleção ativo (drill-down) ────────────────────────────────────────────
    public ?int $paroquiaSelecionada = null;
    public ?int $movimentoSelecionado = null;

    // ── Form: Paróquia ────────────────────────────────────────────────────────
    public ?int $editandoParoquia = null;
    public string $nom_paroquia = '';
    public string $nom_paroco = '';
    public string $eml_paroquia = '';
    public string $tel_paroquia = '';
    public string $des_chave_pix = '';

    // ── Form: Movimento ───────────────────────────────────────────────────────
    public ?int $editandoMovimento = null;
    public string $nom_movimento = '';
    public string $des_sigla = '';
    public string $dat_inicio = '';
    public bool $ind_inscricao_aberta = false;
    public $med_logo = null;
    public ?string $logo_atual = null;

    // ── Form: Equipe ──────────────────────────────────────────────────────────
    public ?int $editandoEquipe = null;
    public string $des_grupo = '';
    public bool $ind_disponivel_candidatura = false;

    // ── Boot ──────────────────────────────────────────────────────────────────
    public function mount(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // PARÓQUIA
    // ═══════════════════════════════════════════════════════════════════════════

    public function selecionarParoquia(int $id): void
    {
        $this->paroquiaSelecionada = ($this->paroquiaSelecionada === $id) ? null : $id;
        $this->movimentoSelecionado = null;
    }

    public function abrirModalParoquia(?int $id = null): void
    {
        $this->resetParoquiaForm();
        if ($id) {
            $p = TipoParoquia::findOrFail($id);
            $this->editandoParoquia    = $id;
            $this->nom_paroquia        = $p->nom_paroquia;
            $this->nom_paroco          = $p->nom_paroco  ?? '';
            $this->eml_paroquia        = $p->eml_paroquia ?? '';
            $this->tel_paroquia        = $p->tel_paroquia ?? '';
            $this->des_chave_pix       = $p->des_chave_pix ?? '';
        }
        $this->modal('modal-paroquia')->show();
    }

    public function salvarParoquia(): void
    {
        $dados = $this->validate([
            'nom_paroquia'  => 'required|string|max:255',
            'nom_paroco'    => 'nullable|string|max:255',
            'eml_paroquia'  => 'nullable|email|max:255',
            'tel_paroquia'  => 'nullable|string|max:20',
            'des_chave_pix' => 'nullable|string|max:100',
        ]);

        TipoParoquia::updateOrCreate(
            ['idt_paroquia' => $this->editandoParoquia],
            array_map(fn($v) => $v ?: null, $dados)
        );

        $this->modal('modal-paroquia')->close();
        $this->resetParoquiaForm();
    }

    public function excluirParoquia(int $id): void
    {
        TipoParoquia::findOrFail($id)->delete();
        if ($this->paroquiaSelecionada === $id) {
            $this->paroquiaSelecionada = null;
            $this->movimentoSelecionado = null;
        }
    }

    private function resetParoquiaForm(): void
    {
        $this->editandoParoquia = null;
        $this->nom_paroquia = $this->nom_paroco = $this->eml_paroquia
            = $this->tel_paroquia = $this->des_chave_pix = '';
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // MOVIMENTO
    // ═══════════════════════════════════════════════════════════════════════════

    public function selecionarMovimento(int $id): void
    {
        $this->movimentoSelecionado = ($this->movimentoSelecionado === $id) ? null : $id;
    }

    public function abrirModalMovimento(?int $id = null): void
    {
        $this->resetMovimentoForm();
        if ($id) {
            $m = TipoMovimento::findOrFail($id);
            $this->editandoMovimento      = $id;
            $this->nom_movimento          = $m->nom_movimento;
            $this->des_sigla              = $m->des_sigla;
            $this->dat_inicio             = $m->dat_inicio ? $m->dat_inicio->format('Y-m-d') : '';
            $this->ind_inscricao_aberta   = (bool) $m->ind_inscricao_aberta;
            $this->logo_atual             = $m->med_logo;
        }
        $this->modal('modal-movimento')->show();
    }

    public function salvarMovimento(): void
    {
        $this->validate([
            'nom_movimento'        => 'required|string|max:255',
            'des_sigla'            => 'required|string|max:10',
            'dat_inicio'           => 'nullable|date',
            'ind_inscricao_aberta' => 'boolean',
            'med_logo'             => 'nullable|image|max:2048',
        ]);

        $movimento = TipoMovimento::updateOrCreate(
            ['idt_movimento' => $this->editandoMovimento],
            [
                'idt_paroquia'         => $this->paroquiaSelecionada,
                'nom_movimento'        => $this->nom_movimento,
                'des_sigla'            => strtoupper($this->des_sigla),
                'dat_inicio'           => $this->dat_inicio ?: null,
                'ind_inscricao_aberta' => $this->ind_inscricao_aberta,
            ]
        );

        if ($this->med_logo) {
            $servico = new \App\Services\ArquivoService();
            $servico->uploadDirectly($movimento, $this->med_logo, 'med_logo', 'movimentos');
        }

        $this->modal('modal-movimento')->close();
        $this->resetMovimentoForm();
    }

    public function excluirMovimento(int $id): void
    {
        TipoMovimento::findOrFail($id)->delete();
        if ($this->movimentoSelecionado === $id) {
            $this->movimentoSelecionado = null;
        }
    }

    private function resetMovimentoForm(): void
    {
        $this->editandoMovimento    = null;
        $this->nom_movimento        = $this->des_sigla = $this->dat_inicio = '';
        $this->ind_inscricao_aberta = false;
        $this->med_logo             = null;
        $this->logo_atual           = null;
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // EQUIPE
    // ═══════════════════════════════════════════════════════════════════════════

    public function abrirModalEquipe(?int $id = null): void
    {
        $this->resetEquipeForm();
        if ($id) {
            $e = TipoEquipe::findOrFail($id);
            $this->editandoEquipe             = $id;
            $this->des_grupo                  = $e->des_grupo;
            $this->ind_disponivel_candidatura = (bool) $e->ind_disponivel_candidatura;
        }
        $this->modal('modal-equipe')->show();
    }

    public function salvarEquipe(): void
    {
        $this->validate([
            'des_grupo'                  => 'required|string|max:255',
            'ind_disponivel_candidatura' => 'boolean',
        ]);

        TipoEquipe::updateOrCreate(
            ['idt_equipe' => $this->editandoEquipe],
            [
                'idt_movimento'              => $this->movimentoSelecionado,
                'des_grupo'                  => $this->des_grupo,
                'ind_disponivel_candidatura' => $this->ind_disponivel_candidatura,
            ]
        );

        $this->modal('modal-equipe')->close();
        $this->resetEquipeForm();
    }

    public function excluirEquipe(int $id): void
    {
        TipoEquipe::findOrFail($id)->delete();
    }

    private function resetEquipeForm(): void
    {
        $this->editandoEquipe             = null;
        $this->des_grupo                  = '';
        $this->ind_disponivel_candidatura = false;
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // DATA
    // ═══════════════════════════════════════════════════════════════════════════

    public function with(): array
    {
        return [
            'paroquias' => TipoParoquia::orderBy('nom_paroquia')->get(),
            'movimentos' => $this->paroquiaSelecionada
                ? TipoMovimento::where('idt_paroquia', $this->paroquiaSelecionada)
                    ->orderBy('nom_movimento')->get()
                : collect(),
            'equipes' => $this->movimentoSelecionado
                ? TipoEquipe::where('idt_movimento', $this->movimentoSelecionado)
                    ->orderBy('des_grupo')->get()
                : collect(),
        ];
    }
};

?>

<div>
    <section class="p-4 md:p-6 w-full max-w-[90vw] ml-auto">
        <flux:breadcrumbs class="mb-4">
            <flux:breadcrumbs.item icon="home" href="/" />
            <flux:breadcrumbs.item href="{{ route('configuracoes.index') }}">Configurações</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Organização</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        {{-- ── Cabeçalho ────────────────────────────────────────────────────── --}}
        <div class="mb-6">
            <flux:heading size="xl" class="text-indigo-900 dark:text-indigo-100 font-bold tracking-tight mb-1">Organização</flux:heading>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Gerencie paróquias, seus movimentos e equipes vinculadas.
            </p>
        </div>

        {{-- ── Grid de três colunas (drill-down) ──────────────────────────── --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                {{-- ╔══════════════════════════════╗
                 ║  COLUNA 1 — PARÓQUIAS        ║
                 ╚══════════════════════════════╝ --}}
            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 shadow-sm flex flex-col">
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-zinc-700">
                    <h2 class="font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                        <x-heroicon-o-building-library class="w-5 h-5 text-violet-500" />
                        Paróquias
                    </h2>
                    <flux:button size="sm" icon="plus" wire:click="abrirModalParoquia()" variant="primary" class="bg-gradient-to-r from-indigo-500 to-purple-500 hover:from-indigo-600 hover:to-purple-600 border-none shadow-md" aria-label="Nova paróquia">
                        Nova
                    </flux:button>
                </div>

                <ul class="divide-y divide-gray-100 dark:divide-zinc-700 flex-1 overflow-y-auto max-h-[60vh]"
                    role="list" aria-label="Lista de paróquias">
                    @forelse ($paroquias as $p)
                        <li wire:key="paroquia-{{ $p->idt_paroquia }}"
                            class="flex items-center justify-between px-4 py-3 cursor-pointer transition
                                   {{ $paroquiaSelecionada === $p->idt_paroquia
                                       ? 'bg-violet-50 dark:bg-violet-900/30 border-l-4 border-violet-500'
                                       : 'hover:bg-gray-50 dark:hover:bg-zinc-700' }}"
                            wire:click="selecionarParoquia({{ $p->idt_paroquia }})"
                            role="button"
                            aria-pressed="{{ $paroquiaSelecionada === $p->idt_paroquia ? 'true' : 'false' }}"
                            aria-label="Selecionar {{ $p->nom_paroquia }}">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                    {{ $p->nom_paroquia }}
                                </p>
                                @if ($p->nom_paroco)
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                        {{ $p->nom_paroco }}
                                    </p>
                                @endif
                            </div>
                            <div class="flex items-center gap-1 ml-2 shrink-0">
                                <flux:button size="sm" variant="ghost" icon="pencil-square"
                                    wire:click.stop="abrirModalParoquia({{ $p->idt_paroquia }})"
                                    aria-label="Editar {{ $p->nom_paroquia }}" />
                                <flux:button size="sm" variant="ghost" icon="trash"
                                    wire:click.stop="excluirParoquia({{ $p->idt_paroquia }})"
                                    wire:confirm="Excluir a paróquia '{{ $p->nom_paroquia }}' e todos seus movimentos?"
                                    aria-label="Excluir {{ $p->nom_paroquia }}" />
                            </div>
                        </li>
                    @empty
                        <li class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">
                            Nenhuma paróquia cadastrada.
                        </li>
                    @endforelse
                </ul>
            </div>

            {{-- ╔══════════════════════════════╗
                 ║  COLUNA 2 — MOVIMENTOS       ║
                 ╚══════════════════════════════╝ --}}
            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 shadow-sm flex flex-col
                        {{ is_null($paroquiaSelecionada) ? 'opacity-50 pointer-events-none' : '' }}">
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-zinc-700">
                    <h2 class="font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                        <x-heroicon-o-flag class="w-5 h-5 text-blue-500" />
                        Movimentos
                    </h2>
                    @if ($paroquiaSelecionada)
                        <flux:button size="sm" icon="plus" wire:click="abrirModalMovimento()" variant="primary" class="bg-gradient-to-r from-indigo-500 to-purple-500 hover:from-indigo-600 hover:to-purple-600 border-none shadow-md" aria-label="Novo movimento">
                            Novo
                        </flux:button>
                    @endif
                </div>

                <ul class="divide-y divide-gray-100 dark:divide-zinc-700 flex-1 overflow-y-auto max-h-[60vh]"
                    role="list" aria-label="Lista de movimentos">
                    @if (is_null($paroquiaSelecionada))
                        <li class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">
                            Selecione uma paróquia.
                        </li>
                    @else
                        @forelse ($movimentos as $m)
                            <li wire:key="movimento-{{ $m->idt_movimento }}"
                                class="flex items-center justify-between px-4 py-3 cursor-pointer transition
                                       {{ $movimentoSelecionado === $m->idt_movimento
                                           ? 'bg-blue-50 dark:bg-blue-900/30 border-l-4 border-blue-500'
                                           : 'hover:bg-gray-50 dark:hover:bg-zinc-700' }}"
                                wire:click="selecionarMovimento({{ $m->idt_movimento }})"
                                role="button"
                                aria-pressed="{{ $movimentoSelecionado === $m->idt_movimento ? 'true' : 'false' }}"
                                aria-label="Selecionar {{ $m->nom_movimento }}">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                            {{ $m->nom_movimento }}
                                        </span>
                                        <span class="font-mono text-xs bg-gray-100 dark:bg-zinc-700 px-1.5 py-0.5 rounded text-gray-600 dark:text-gray-300">
                                            {{ $m->des_sigla }}
                                        </span>
                                    </div>
                                    @if ($m->ind_inscricao_aberta)
                                        <flux:badge color="green" size="sm" class="mt-0.5">Inscrições abertas</flux:badge>
                                    @else
                                        <flux:badge color="red" size="sm" class="mt-0.5">Fechadas</flux:badge>
                                    @endif
                                </div>
                                <div class="flex items-center gap-1 ml-2 shrink-0">
                                    <flux:button size="sm" variant="ghost" icon="pencil-square"
                                        wire:click.stop="abrirModalMovimento({{ $m->idt_movimento }})"
                                        aria-label="Editar {{ $m->nom_movimento }}" />
                                    <flux:button size="sm" variant="ghost" icon="trash"
                                        wire:click.stop="excluirMovimento({{ $m->idt_movimento }})"
                                        wire:confirm="Excluir o movimento '{{ $m->nom_movimento }}'?"
                                        aria-label="Excluir {{ $m->nom_movimento }}" />
                                </div>
                            </li>
                        @empty
                            <li class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">
                                Nenhum movimento nesta paróquia.
                            </li>
                        @endforelse
                    @endif
                </ul>
            </div>

            {{-- ╔══════════════════════════════╗
                 ║  COLUNA 3 — EQUIPES          ║
                 ╚══════════════════════════════╝ --}}
            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 shadow-sm flex flex-col
                        {{ is_null($movimentoSelecionado) ? 'opacity-50 pointer-events-none' : '' }}">
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-zinc-700">
                    <h2 class="font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                        <x-heroicon-o-user-group class="w-5 h-5 text-emerald-500" />
                        Equipes
                    </h2>
                    @if ($movimentoSelecionado)
                        <flux:button size="sm" icon="plus" wire:click="abrirModalEquipe()" variant="primary" class="bg-gradient-to-r from-indigo-500 to-purple-500 hover:from-indigo-600 hover:to-purple-600 border-none shadow-md" aria-label="Nova equipe">
                            Nova
                        </flux:button>
                    @endif
                </div>

                <ul class="divide-y divide-gray-100 dark:divide-zinc-700 flex-1 overflow-y-auto max-h-[60vh]"
                    role="list" aria-label="Lista de equipes">
                    @if (is_null($movimentoSelecionado))
                        <li class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">
                            Selecione um movimento.
                        </li>
                    @else
                        @forelse ($equipes as $e)
                            <li wire:key="equipe-{{ $e->idt_equipe }}"
                                class="flex items-center justify-between px-4 py-3 hover:bg-gray-50 dark:hover:bg-zinc-700 transition">
                                <div class="min-w-0 flex flex-col">
                                    <span class="text-sm text-gray-900 dark:text-gray-100 truncate">{{ $e->des_grupo }}</span>
                                    @if ($e->ind_disponivel_candidatura)
                                        <flux:badge color="green" size="sm" class="mt-0.5 self-start">Disponível para candidatura</flux:badge>
                                    @endif
                                </div>
                                <div class="flex items-center gap-1 ml-2 shrink-0">
                                    <flux:button size="sm" variant="ghost" icon="pencil-square"
                                        wire:click="abrirModalEquipe({{ $e->idt_equipe }})"
                                        aria-label="Editar {{ $e->des_grupo }}" />
                                    <flux:button size="sm" variant="ghost" icon="trash"
                                        wire:click="excluirEquipe({{ $e->idt_equipe }})"
                                        wire:confirm="Excluir a equipe '{{ $e->des_grupo }}'?"
                                        aria-label="Excluir {{ $e->des_grupo }}" />
                                </div>
                            </li>
                        @empty
                            <li class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">
                                Nenhuma equipe neste movimento.
                            </li>
                        @endforelse
                    @endif
                </ul>
            </div>

        </div>{{-- /grid --}}

    {{-- ════════════════════════════════════════════════════════════════════════
         MODAL — PARÓQUIA
         ════════════════════════════════════════════════════════════════════════ --}}
    <flux:modal name="modal-paroquia" class="w-full max-w-lg">
        <div class="p-6 space-y-5">
            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                {{ $editandoParoquia ? 'Editar Paróquia' : 'Nova Paróquia' }}
            </h3>

            <flux:input wire:model="nom_paroquia" label="Nome da Paróquia" placeholder="Ex: Paróquia São José"
                required aria-required="true" id="nom_paroquia" />
            @error('nom_paroquia') <p class="text-red-500 text-xs -mt-3" role="alert">{{ $message }}</p> @enderror

            <flux:input wire:model="nom_paroco" label="Pároco" placeholder="Ex: Pe. João da Silva"
                id="nom_paroco" />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <flux:input wire:model="eml_paroquia" label="E-mail" type="email"
                        placeholder="paroquia@diocese.org" id="eml_paroquia" />
                    @error('eml_paroquia') <p class="text-red-500 text-xs mt-1" role="alert">{{ $message }}</p> @enderror
                </div>
                <flux:input wire:model="tel_paroquia" label="Telefone" placeholder="(00) 00000-0000"
                    id="tel_paroquia" />
            </div>

            <flux:input wire:model="des_chave_pix" label="Chave PIX" placeholder="CPF, CNPJ, e-mail ou telefone"
                id="des_chave_pix" />

            <div class="flex justify-end gap-3 pt-2">
                <flux:button variant="ghost" x-on:click="$flux.modal('modal-paroquia').close()">Cancelar</flux:button>
                <flux:button variant="primary" class="bg-gradient-to-r from-indigo-500 to-purple-500 hover:from-indigo-600 hover:to-purple-600 border-none shadow-md" wire:click="salvarParoquia" wire:loading.attr="disabled">
                    Salvar
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- ════════════════════════════════════════════════════════════════════════
         MODAL — MOVIMENTO
         ════════════════════════════════════════════════════════════════════════ --}}
    <flux:modal name="modal-movimento" class="w-full max-w-lg">
        <div class="p-6 space-y-5">
            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                {{ $editandoMovimento ? 'Editar Movimento' : 'Novo Movimento' }}
            </h3>

            <flux:input wire:model="nom_movimento" label="Nome do Movimento"
                placeholder="Ex: Encontro de Casais com Cristo"
                required aria-required="true" id="nom_movimento" />
            @error('nom_movimento') <p class="text-red-500 text-xs -mt-3" role="alert">{{ $message }}</p> @enderror

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <flux:input wire:model="des_sigla" label="Sigla" placeholder="Ex: ECC"
                        maxlength="10" class="uppercase" id="des_sigla" />
                    @error('des_sigla') <p class="text-red-500 text-xs mt-1" role="alert">{{ $message }}</p> @enderror
                </div>
                <flux:input wire:model="dat_inicio" label="Data de Início" type="date" id="dat_inicio" />
            </div>

            <div class="space-y-1">
                <flux:input type="file" wire:model="med_logo" label="Logo do Movimento" id="med_logo" accept="image/*" />
                <div wire:loading wire:target="med_logo" class="text-sm text-blue-500">Fazendo upload...</div>
                @error('med_logo') <p class="text-red-500 text-xs mt-1" role="alert">{{ $message }}</p> @enderror
                
                @if ($logo_atual)
                    <div class="mt-2 text-xs text-gray-500 dark:text-gray-400 flex items-center gap-2">
                        <x-heroicon-o-check-circle class="w-4 h-4 text-green-500" />
                        Uma imagem já está cadastrada. Enviar uma nova irá substituí-la.
                    </div>
                @endif
            </div>

            {{-- Toggle: Inscrições abertas --}}
            <div class="flex items-center gap-3 p-3 rounded-lg bg-gray-50 dark:bg-zinc-700 border border-gray-200 dark:border-zinc-600">
                <flux:checkbox wire:model="ind_inscricao_aberta" id="ind_inscricao_aberta"
                    aria-describedby="desc-inscricao-modal" />
                <div class="flex-1">
                    <label for="ind_inscricao_aberta" class="text-sm font-medium text-gray-900 dark:text-gray-100 cursor-pointer">
                        Inscrições abertas
                    </label>
                    <p id="desc-inscricao-modal" class="text-xs text-gray-500 dark:text-gray-400">
                        Exibe o botão de inscrição na Welcome page quando marcado.
                    </p>
                </div>
                @if ($ind_inscricao_aberta)
                    <flux:badge color="green" size="sm">Abertas</flux:badge>
                @else
                    <flux:badge color="red" size="sm">Fechadas</flux:badge>
                @endif
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <flux:button variant="ghost" x-on:click="$flux.modal('modal-movimento').close()">Cancelar</flux:button>
                <flux:button variant="primary" class="bg-gradient-to-r from-indigo-500 to-purple-500 hover:from-indigo-600 hover:to-purple-600 border-none shadow-md" wire:click="salvarMovimento" wire:loading.attr="disabled">
                    Salvar
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- ════════════════════════════════════════════════════════════════════════
         MODAL — EQUIPE
         ════════════════════════════════════════════════════════════════════════ --}}
    <flux:modal name="modal-equipe" class="w-full max-w-md">
        <div class="p-6 space-y-5">
            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                {{ $editandoEquipe ? 'Editar Equipe' : 'Nova Equipe' }}
            </h3>

            <flux:input wire:model="des_grupo" label="Nome da Equipe"
                placeholder="Ex: Oração, Bandinha, Reportagem..."
                required aria-required="true" id="des_grupo" />
            @error('des_grupo') <p class="text-red-500 text-xs -mt-3" role="alert">{{ $message }}</p> @enderror

            <div class="flex items-center gap-3 p-3 rounded-lg bg-gray-50 dark:bg-zinc-700 border border-gray-200 dark:border-zinc-600 mt-2">
                <flux:checkbox wire:model="ind_disponivel_candidatura" id="ind_disponivel_candidatura"
                    aria-describedby="desc-candidatura-equipe" />
                <div class="flex-1">
                    <label for="ind_disponivel_candidatura" class="text-sm font-medium text-gray-900 dark:text-gray-100 cursor-pointer">
                        Disponível para Candidatura
                    </label>
                    <p id="desc-candidatura-equipe" class="text-xs text-gray-500 dark:text-gray-400">
                        Marque se esta equipe aceita candidaturas de trabalhadores.
                    </p>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <flux:button variant="ghost" x-on:click="$flux.modal('modal-equipe').close()">Cancelar</flux:button>
                <flux:button variant="primary" class="bg-gradient-to-r from-indigo-500 to-purple-500 hover:from-indigo-600 hover:to-purple-600 border-none shadow-md" wire:click="salvarEquipe" wire:loading.attr="disabled">
                    Salvar
                </flux:button>
            </div>
        </div>
    </flux:modal>
    </section>
</div>
