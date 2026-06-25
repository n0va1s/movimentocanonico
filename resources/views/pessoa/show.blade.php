<x-layouts.app :title="$pessoa->nom_pessoa">
    <section class="p-6 w-full max-w-3xl mx-auto">

        <x-session-alert />

        {{-- NAVEGAÇÃO --}}
        <flux:breadcrumbs class="mb-6">
            <flux:breadcrumbs.item href="/">Início</flux:breadcrumbs.item>
            @if (Auth::user()->isAdmin())
                <flux:breadcrumbs.item :href="route('pessoas.index')">Pessoas</flux:breadcrumbs.item>
            @else
                <flux:breadcrumbs.item :href="route('trabalhadores.minha-equipe')">Minha Equipe</flux:breadcrumbs.item>
            @endif
            <flux:breadcrumbs.item>{{ $pessoa->nom_apelido ?? $pessoa->nom_pessoa }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        {{-- CABEÇALHO --}}
        <div class="flex items-center gap-5 mb-8">
            @if ($pessoa->foto && $pessoa->foto->med_foto)
                <img src="{{ Storage::url($pessoa->foto->med_foto) }}"
                    alt="Foto de {{ $pessoa->nom_pessoa }}"
                    class="w-20 h-20 rounded-full object-cover border-2 border-gray-300 dark:border-zinc-600 shadow">
            @else
                <div class="w-20 h-20 rounded-full bg-gray-200 dark:bg-zinc-700 flex items-center justify-center flex-shrink-0">
                    <x-heroicon-o-user class="w-10 h-10 text-gray-400 dark:text-gray-500" />
                </div>
            @endif
            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between gap-3">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 truncate">{{ $pessoa->nom_pessoa }}</h1>
                    @php $totalPontos = $pessoa->pontos->sum('qtd_pontos'); @endphp
                    <div class="flex-shrink-0 flex flex-col items-center justify-center bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-700 rounded-lg px-3 py-1.5 min-w-[52px]"
                        title="Pontos Aura">
                        <span class="text-lg font-bold text-amber-600 dark:text-amber-400 leading-none">{{ $totalPontos }}</span>
                        <span class="text-[9px] font-semibold text-amber-500 uppercase tracking-wide mt-0.5">pts</span>
                    </div>
                </div>
                @if ($pessoa->nom_apelido)
                    <p class="text-gray-500 dark:text-gray-400 text-sm">{{ $pessoa->nom_apelido }}</p>
                @endif
                @if (Auth::user()->isAdmin())
                    <a href="{{ route('pessoas.edit', $pessoa->idt_pessoa) }}"
                        class="inline-flex items-center gap-1 text-sm text-blue-600 dark:text-blue-400 hover:underline mt-1">
                        <x-heroicon-o-pencil-square class="w-4 h-4" />
                        Editar
                    </a>
                @endif
            </div>
        </div>

        {{-- DADOS PESSOAIS --}}
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow border border-gray-200 dark:border-zinc-700 p-6 mb-6">
            <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                <x-heroicon-o-identification class="w-5 h-5 text-blue-500" />
                Dados Pessoais
            </h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Telefone</dt>
                    <dd class="text-gray-900 dark:text-gray-100 font-medium">
                        {{ $pessoa->tel_pessoa ?? '—' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Data de Nascimento</dt>
                    <dd class="text-gray-900 dark:text-gray-100 font-medium">
                        {{ $pessoa->dat_nascimento ? $pessoa->dat_nascimento->format('d/m/Y') : '—' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">E-mail</dt>
                    <dd class="text-gray-900 dark:text-gray-100 font-medium break-all">
                        {{ $pessoa->eml_pessoa ?? '—' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Endereço</dt>
                    <dd class="text-gray-900 dark:text-gray-100 font-medium">
                        {{ $pessoa->des_endereco ?? '—' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Tamanho de Camiseta</dt>
                    <dd class="text-gray-900 dark:text-gray-100 font-medium">
                        {{ $pessoa->tam_camiseta?->value ?? $pessoa->tam_camiseta ?? '—' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Toca Violão</dt>
                    <dd class="text-gray-900 dark:text-gray-100 font-medium">
                        {{ $pessoa->ind_toca_violao ? 'Sim' : 'Não' }}
                    </dd>
                </div>
            </dl>
        </div>

        {{-- RESTRIÇÕES DE SAÚDE --}}
        @if ($pessoa->restricoes->isNotEmpty())
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow border border-gray-200 dark:border-zinc-700 p-6 mb-6">
                <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                    <x-heroicon-o-heart class="w-5 h-5 text-red-500" />
                    Restrições de Saúde
                </h2>
                <div class="space-y-2">
                    @foreach ($pessoa->restricoes as $restricao)
                        <div class="flex items-start gap-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $restricao->getCor() }} flex-shrink-0 mt-0.5">
                                {{ $restricao->getTipo() }}
                            </span>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $restricao->des_restricao }}</p>
                                @if ($restricao->pivot->txt_complemento)
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $restricao->pivot->txt_complemento }}</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- HISTÓRICO DE TRABALHO --}}
        @if ($pessoa->trabalhadores->isNotEmpty())
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow border border-gray-200 dark:border-zinc-700 p-6">
                <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                    <x-heroicon-o-briefcase class="w-5 h-5 text-green-500" />
                    Histórico de Trabalho
                </h2>
                <ul class="space-y-2">
                    @foreach ($pessoa->trabalhadores->sortByDesc(fn($t) => $t->evento?->dat_inicio) as $trab)
                        <li class="flex items-center justify-between text-sm">
                            <div>
                                <span class="font-medium text-gray-900 dark:text-gray-100">
                                    {{ $trab->evento?->des_evento ?? '—' }}
                                </span>
                                <span class="text-gray-500 dark:text-gray-400 ml-2">
                                    {{ $trab->equipe?->des_grupo ?? '—' }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                @if ($trab->ind_coordenador)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-800 dark:text-purple-100">
                                        Coord.
                                    </span>
                                @endif
                                @if ($trab->evento?->dat_inicio)
                                    <span class="text-gray-400 dark:text-gray-500 text-xs">
                                        {{ $trab->evento->dat_inicio->format('d/m/Y') }}
                                    </span>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

    </section>
</x-layouts.app>
