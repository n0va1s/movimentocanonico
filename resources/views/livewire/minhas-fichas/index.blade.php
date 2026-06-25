<?php

use App\Models\Ficha;
use App\Models\Pessoa;
use App\Models\User;
use App\Models\TipoMovimento;
use App\Models\Evento;
use App\Enums\TipoSituacao;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public ?int $eventoId = null;
    public string $situacao = '';

    public function mount(): void
    {
        if (!auth()->check() || !auth()->user()->hasRole('admin', 'visit')) {
            abort(403, 'Acesso não autorizado.');
        }

        $user = auth()->user();
        $movimentoId = $user->idt_movimento;
        $hoje = now()->startOfDay();

        $primeiroEventoAtivo = Evento::where(function ($q) use ($hoje) {
                $q->where('dat_inicio', '>=', $hoje)
                    ->orWhere('dat_termino', '>=', $hoje)
                    ->orWhereNull('dat_termino');
            })
            ->when($movimentoId, function ($q) use ($movimentoId) {
                $q->where('idt_movimento', $movimentoId);
            })
            ->orderBy('dat_inicio', 'asc')
            ->first();

        if ($primeiroEventoAtivo) {
            $this->eventoId = $primeiroEventoAtivo->idt_evento;
        }
    }

    public function updatedEventoId(): void
    {
        $this->resetPage();
    }

    public function updatedSituacao(): void
    {
        $this->resetPage();
    }

    public function alterarSituacao(int $fichaId, string $novaSituacao): void
    {
        try {
            $situacaoEnum = TipoSituacao::from($novaSituacao);
            \App\Services\FichaService::atualizarSituacaoFicha($fichaId, $situacaoEnum);
            session()->flash('success', 'Situação da ficha atualizada com sucesso!');
        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao atualizar situação: ' . $e->getMessage());
        }
    }

    public function with(): array
    {
        $user = auth()->user();
        $movimentoId = $user->idt_movimento;
        $pessoa = $user->pessoa;
        $pessoaId = $pessoa?->idt_pessoa;
        $parceiroId = $pessoa?->idt_parceiro;
        $hoje = now()->startOfDay();

        // Get all active events for the filter
        $eventosAtivos = Evento::where(function ($q) use ($hoje) {
                $q->where('dat_inicio', '>=', $hoje)
                    ->orWhere('dat_termino', '>=', $hoje)
                    ->orWhereNull('dat_termino');
            })
            ->when($movimentoId, function ($q) use ($movimentoId) {
                $q->where('idt_movimento', $movimentoId);
            })
            ->orderBy('dat_inicio', 'asc')
            ->get();

        $fichasQuery = Ficha::with(['fichaVem', 'fichaEcc', 'fichaSGM', 'evento', 'visitador', 'visitador.parceiro']);

        // A ficha só pode aparecer para a pessoa logada se for o visitador designado (ou seu parceiro/cônjuge),
        // exceto se for administrador (admin), caso em que vê todas as fichas de visitação do evento.
        if (!$user->isAdmin()) {
            if (!$pessoaId) {
                $fichasQuery->whereRaw('1 = 0');
            } else {
                if ($parceiroId) {
                    $fichasQuery->whereIn('idt_pessoa_visitacao', [$pessoaId, $parceiroId]);
                } else {
                    $fichasQuery->where('idt_pessoa_visitacao', $pessoaId);
                }
            }
        }

        $fichasQuery
            ->when($this->eventoId, function ($query) {
                $query->where('idt_evento', $this->eventoId);
            }, function ($query) {
                $query->whereRaw('1 = 0');
            })
            ->when($this->situacao, function ($query) {
                $query->where('tip_situacao', $this->situacao);
            }, function ($query) {
                $query->whereIn('tip_situacao', [
                    TipoSituacao::SELECIONADA,
                    TipoSituacao::CONTATO,
                    TipoSituacao::AGUARDANDO
                ]);
            });

        return [
            'fichas' => $fichasQuery->orderBy('created_at', 'desc')->paginate(12),
            'eventosAtivos' => $eventosAtivos,
        ];
    }
}; ?>

<div class="space-y-6 w-full max-w-7xl mx-auto p-4 md:p-8">
    {{-- Cabeçalho --}}
    <header class="mb-8 flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Minhas Fichas</h1>
            <p class="text-gray-600 mt-1 dark:text-gray-400">Essas sãos as fichas sob sua responsabilidade como Visitação</p>
        </div>
    </header>

    {{-- Alerts --}}
    <x-session-alert />

    {{-- Filtros --}}
    <div class="flex flex-col sm:flex-row gap-4 bg-zinc-50 dark:bg-zinc-900 p-4 rounded-xl border border-zinc-200 dark:border-zinc-700 items-center justify-between w-full">
        <div class="flex flex-col sm:flex-row gap-4 w-full sm:w-auto">
            <div class="w-full sm:w-72">
                <flux:select wire:model.live="eventoId" placeholder="Selecione um Evento Ativo">
                    <flux:select.option value="">Selecione um Evento Ativo</flux:select.option>
                    @foreach ($eventosAtivos as $ev)
                        <flux:select.option value="{{ $ev->idt_evento }}">{{ $ev->des_evento }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <div class="w-full sm:w-72">
                <flux:select wire:model.live="situacao" placeholder="Todas as Situações">
                    <flux:select.option value="">Todas as Situações</flux:select.option>
                    @foreach ([App\Enums\TipoSituacao::SELECIONADA, App\Enums\TipoSituacao::CONTATO, App\Enums\TipoSituacao::AGUARDANDO] as $sit)
                        <flux:select.option value="{{ $sit->value }}">{{ $sit->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </div>
    </div>

    {{-- Grid de Fichas --}}
    @if ($fichas->isNotEmpty())
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($fichas as $ficha)
                @php
                    $style = $ficha->tip_situacao->badge();
                @endphp
                <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-5 shadow-sm hover:shadow-md transition duration-200 flex flex-col justify-between">
                    <div>
                        {{-- Top row: Event + Badge --}}
                        <div class="flex justify-between items-start gap-2 mb-3">
                            <span class="text-xs font-semibold uppercase tracking-wider text-blue-600 dark:text-blue-400">
                                {{ $ficha->evento->des_evento ?? 'Sem Evento' }}
                            </span>
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-2xs font-bold {{ $style['light'] }} border">
                                {{ $ficha->tip_situacao->label() }}
                            </span>
                        </div>

                        {{-- Name --}}
                        <flux:heading size="lg" class="text-zinc-900 dark:text-zinc-100 font-bold">
                            {{ $ficha->nom_candidato }}
                            @if ($ficha->nom_apelido)
                                <span class="text-sm font-normal text-zinc-500 dark:text-zinc-400">({{ $ficha->nom_apelido }})</span>
                            @endif
                        </flux:heading>

                        {{-- Address --}}
                        <div class="flex items-start gap-2 text-zinc-600 dark:text-zinc-400 text-xs mt-3">
                            <flux:icon.map-pin class="size-4 shrink-0 text-zinc-400 mt-0.5" />
                            <span>{{ $ficha->des_endereco ?? 'Endereço não informado' }}</span>
                        </div>

                        {{-- Visitador Designado --}}
                        @if ($ficha->visitador)
                            @php
                                $v = $ficha->visitador;
                                $nomeLabel = $v->nom_pessoa;
                                if ($v->parceiro) {
                                    $nomeLabel .= ' & ' . $v->parceiro->nom_pessoa;
                                }
                            @endphp
                            <div class="flex items-start gap-2 text-zinc-600 dark:text-zinc-400 text-xs mt-3">
                                <flux:icon.user-circle class="size-4 shrink-0 text-zinc-400 mt-0.5" />
                                <span>Designado: <strong class="text-zinc-800 dark:text-zinc-200 font-semibold">{{ $nomeLabel }}</strong></span>
                            </div>
                        @else
                            <div class="flex items-start gap-2 text-zinc-500 dark:text-zinc-500 text-xs mt-3">
                                <flux:icon.user-circle class="size-4 shrink-0 text-zinc-400 mt-0.5" />
                                <span class="italic text-zinc-400 dark:text-zinc-500">Sem responsável designado</span>
                            </div>
                        @endif

                        {{-- Contacts Box --}}
                        @php
                            $resp = $ficha->responsavel_info;
                        @endphp
                        @if ($resp)
                            <div class="bg-zinc-50 dark:bg-zinc-900/50 rounded-lg p-3 mt-4 border border-zinc-200/50 dark:border-zinc-700/50">
                                <div class="text-3xs font-extrabold uppercase tracking-widest text-zinc-400 dark:text-zinc-500">
                                    {{ $resp['tipo'] }}
                                </div>
                                <div class="text-sm font-semibold text-zinc-800 dark:text-zinc-200 mt-0.5">
                                    {{ $resp['nome'] }}
                                </div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5 mt-2">
                                    <flux:icon.phone class="size-3.5 text-zinc-400" />
                                    <span class="font-medium">{{ $resp['telefone'] }}</span>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Actions --}}
                    <div class="mt-6 pt-4 border-t border-zinc-100 dark:border-zinc-700 grid grid-cols-2 gap-2">
                        {{-- Fiz Contato --}}
                        <button 
                            wire:click="alterarSituacao({{ $ficha->idt_ficha }}, 'F')" 
                            @disabled($ficha->tip_situacao->value === 'F')
                            class="flex items-center justify-center gap-1 text-2xs font-semibold px-2 py-1.5 rounded-md border border-cyan-300 dark:border-cyan-800 text-cyan-600 dark:text-cyan-400 hover:bg-cyan-50 dark:hover:bg-cyan-900/20 disabled:opacity-40 disabled:pointer-events-none transition cursor-pointer"
                        >
                            <flux:icon.phone class="size-3.5" />
                            Fiz Contato
                        </button>

                        {{-- Aguardando --}}
                        <button 
                            wire:click="alterarSituacao({{ $ficha->idt_ficha }}, 'W')" 
                            @disabled($ficha->tip_situacao->value === 'W')
                            class="flex items-center justify-center gap-1 text-2xs font-semibold px-2 py-1.5 rounded-md border border-purple-300 dark:border-purple-800 text-purple-600 dark:text-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/20 disabled:opacity-40 disabled:pointer-events-none transition cursor-pointer"
                        >
                            <flux:icon.clock class="size-3.5" />
                            Aguardando
                        </button>

                        {{-- Editar --}}
                        <a 
                            href="{{ $ficha->getEditRoute() }}" 
                            wire:navigate
                            class="flex items-center justify-center gap-1 text-2xs font-semibold px-2 py-1.5 rounded-md border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition cursor-pointer"
                        >
                            <flux:icon.pencil-square class="size-3.5" />
                            Editar Ficha
                        </a>

                        {{-- Cancelar --}}
                        <button 
                            wire:click="alterarSituacao({{ $ficha->idt_ficha }}, 'C')" 
                            @disabled($ficha->tip_situacao->value === 'C')
                            wire:confirm="Tem certeza de que deseja cancelar esta ficha?"
                            class="flex items-center justify-center gap-1 text-2xs font-semibold px-2 py-1.5 rounded-md border border-rose-300 dark:border-rose-800 text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-900/20 disabled:opacity-40 disabled:pointer-events-none transition cursor-pointer"
                        >
                            <flux:icon.x-circle class="size-3.5" />
                            Cancelar
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $fichas->links(data: ['scrollTo' => false]) }}
        </div>
    @else
        <div class="flex flex-col items-center justify-center text-center p-12 bg-white dark:bg-zinc-800 rounded-xl border border-dashed border-zinc-300 dark:border-zinc-600 shadow-sm">
            <flux:icon.document-text class="w-12 h-12 text-zinc-400 dark:text-zinc-500 mb-4" />
            <flux:heading size="lg" class="text-zinc-700 dark:text-zinc-300">
                @if (!$eventoId)
                    Selecione um Evento Ativo
                @else
                    Nenhuma ficha encontrada
                @endif
            </flux:heading>
            <flux:subheading class="mt-1">
                @if (!$eventoId)
                    Selecione um dos eventos ativos no filtro acima para visualizar suas fichas designadas.
                @else
                    Não existem fichas designadas para a sua conta ou compatíveis com os filtros selecionados.
                @endif
            </flux:subheading>
        </div>
    @endif
</div>
