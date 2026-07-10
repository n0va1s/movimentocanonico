<x-layouts.app :title="__('Dashboard')">
    <div class="p-4 md:p-6 w-full max-w-7xl mx-auto space-y-6 md:space-y-8">

        {{-- ── Cabeçalho com Saudação + Ações/Stats inline ────────────────── --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <flux:heading size="xl" class="text-indigo-900 dark:text-indigo-100 font-bold tracking-tight mb-1">
                    Olá, {{ Auth::user()->name }}!
                </flux:heading>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Essa é a força da sua comunidade</p>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4 w-full sm:w-auto">
                {{-- Consultar Saldo --}}
                <flux:modal.trigger name="modal-mercadinho" class="w-full sm:w-auto">
                    <flux:button variant="primary" icon="magnifying-glass"
                        class="w-full sm:w-auto bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 border-none shadow-md">
                        Consultar Saldo
                    </flux:button>
                </flux:modal.trigger>

                {{-- Pessoas Evangelizadas --}}
                <div class="flex items-center gap-3 px-5 py-3 bg-white dark:bg-zinc-800 rounded-2xl border border-gray-100 dark:border-zinc-700 shadow-sm w-full sm:w-auto">
                    <div class="flex items-center justify-center h-10 w-10 rounded-xl bg-green-50 dark:bg-green-900/20">
                        <x-heroicon-o-users class="h-5 w-5 text-green-600" />
                    </div>
                    <div class="flex-1 sm:flex-initial">
                        <p class="text-[10px] font-bold text-gray-400 dark:text-zinc-500 uppercase tracking-widest">Pessoas Evangelizadas</p>
                        <p class="text-2xl font-black text-gray-900 dark:text-white tabular-nums leading-none mt-0.5">{{ $qtdParticipantesCadastrados }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Grid Principal (2 colunas desktop) ───────────────────────── --}}
        <div class="grid gap-6 md:gap-8 lg:grid-cols-2">

            {{-- ╔═══════════════════════════════════════╗
                 ║  🎂  ANIVERSARIANTES DO MÊS           ║
                 ╚═══════════════════════════════════════╝ --}}
            <section
                class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-gray-200 dark:border-zinc-700 overflow-hidden"
                aria-label="Aniversariantes da semana">
                <header
                    class="px-5 md:px-6 py-4 border-b border-gray-100 dark:border-zinc-700 flex justify-between items-center bg-gray-50/50 dark:bg-zinc-800/50">
                    <h2 class="font-bold text-gray-800 dark:text-white flex items-center gap-2">
                        <x-heroicon-s-cake class="w-5 h-5 text-pink-500" />
                        Aniversariantes da Semana
                    </h2>
                    <flux:badge color="pink" size="sm">{{ $aniversariantes->count() }}</flux:badge>
                </header>

                <div class="p-4 md:p-6">
                    @if ($aniversariantes->isEmpty())
                        <div class="py-8 text-center">
                            <x-heroicon-o-cake class="w-10 h-10 mx-auto text-gray-300 dark:text-zinc-600 mb-2" />
                            <p class="text-sm text-gray-400 dark:text-gray-500">Nenhum aniversariante nesta semana.</p>
                        </div>
                    @else
                        <ul class="divide-y divide-gray-100 dark:divide-zinc-700 -my-3 max-h-[45vh] overflow-y-auto overflow-x-hidden pr-1"
                            role="list">
                            @foreach ($aniversariantes as $pessoa)
                                @php
                                    $isHoje = $pessoa->dat_nascimento->day === now()->day && $pessoa->dat_nascimento->month === now()->month;
                                    $dia = $pessoa->dat_nascimento->format('d');
                                    $mesAbrev = $pessoa->dat_nascimento->translatedFormat('M');
                                    $iniciais = collect(explode(' ', $pessoa->nom_pessoa))->take(2)->map(fn($w) => mb_substr($w, 0, 1))->implode('');
                                @endphp
                                <li class="py-3 flex items-center gap-3 group {{ $isHoje ? 'bg-pink-50/50 dark:bg-pink-900/10 -mx-4 md:-mx-6 px-4 md:px-6 rounded-xl' : '' }}">
                                    {{-- Avatar --}}
                                    @if ($pessoa->foto && $pessoa->foto->med_foto)
                                        <img src="{{ asset('storage/' . $pessoa->foto->med_foto) }}"
                                             alt="{{ $pessoa->nom_pessoa }}"
                                             loading="lazy"
                                             class="w-10 h-10 rounded-full object-cover border-2 {{ $isHoje ? 'border-pink-400 ring-2 ring-pink-200 dark:ring-pink-800' : 'border-gray-200 dark:border-zinc-600' }}">
                                    @else
                                        <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold
                                                    {{ $isHoje
                                                        ? 'bg-gradient-to-br from-pink-400 to-rose-500 text-white border-2 border-pink-300 ring-2 ring-pink-200 dark:ring-pink-800'
                                                        : 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 border-2 border-indigo-200 dark:border-indigo-800' }}">
                                            {{ $iniciais }}
                                        </div>
                                    @endif

                                    {{-- Info --}}
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                            {{ $pessoa->nom_apelido ?? $pessoa->nom_pessoa }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $dia }} de {{ $pessoa->dat_nascimento->translatedFormat('F') }}
                                        </p>
                                    </div>

                                    {{-- Badge "Hoje" --}}
                                    @if ($isHoje)
                                        <flux:badge color="pink" size="sm" icon="sparkles" class="animate-pulse">Hoje 🎉</flux:badge>
                                    @else
                                        <span class="text-xs font-mono text-gray-400 dark:text-zinc-500 tabular-nums">
                                            {{ $dia }}/{{ $mesAbrev }}
                                        </span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </section>

            {{-- ╔═══════════════════════════════════════╗
                 ║  🏆  LÍDERES DE AURA (TOP 10)         ║
                 ╚═══════════════════════════════════════╝ --}}
            <section
                class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-gray-200 dark:border-zinc-700 overflow-hidden"
                aria-label="Líderes de Aura">
                <header
                    class="px-5 md:px-6 py-4 border-b border-gray-100 dark:border-zinc-700 flex justify-between items-center bg-gray-50/50 dark:bg-zinc-800/50">
                    <h2 class="font-bold text-gray-800 dark:text-white flex items-center gap-2">
                        <x-heroicon-s-trophy class="w-5 h-5 text-amber-500" />
                        Líderes de Aura
                    </h2>
                    <flux:badge color="amber" size="sm">Top {{ $lideresAura->count() }}</flux:badge>
                </header>

                <div class="p-4 md:p-6">
                    @if ($lideresAura->isEmpty())
                        <div class="py-8 text-center">
                            <x-heroicon-o-trophy class="w-10 h-10 mx-auto text-gray-300 dark:text-zinc-600 mb-2" />
                            <p class="text-sm text-gray-400 dark:text-gray-500">Nenhum líder com pontuação registrada.</p>
                        </div>
                    @else
                        <ol class="space-y-2 max-h-[45vh] overflow-y-auto overflow-x-hidden pr-1" role="list">
                            @foreach ($lideresAura as $index => $lider)
                                @php
                                    $posicao = $index + 1;
                                    $medalhas = [1 => '🥇', 2 => '🥈', 3 => '🥉'];
                                    $medalha = $medalhas[$posicao] ?? null;
                                    $isPodio = $posicao <= 3;
                                    $iniciais = collect(explode(' ', $lider->nom_pessoa))->take(2)->map(fn($w) => mb_substr($w, 0, 1))->implode('');
                                @endphp
                                <li class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-colors
                                           {{ $isPodio
                                               ? 'bg-gradient-to-r from-amber-50/80 to-orange-50/50 dark:from-amber-900/15 dark:to-orange-900/10 border border-amber-200/60 dark:border-amber-800/30'
                                               : 'hover:bg-gray-50 dark:hover:bg-zinc-700/50' }}">

                                    {{-- Posição --}}
                                    <div class="w-8 h-8 flex-shrink-0 flex items-center justify-center rounded-lg text-sm font-black
                                                {{ $isPodio
                                                    ? 'bg-gradient-to-br from-amber-400 to-orange-500 text-white shadow-sm'
                                                    : 'bg-gray-100 dark:bg-zinc-700 text-gray-500 dark:text-zinc-400' }}">
                                        @if ($medalha)
                                            <span class="text-base" aria-label="Posição {{ $posicao }}">{{ $medalha }}</span>
                                        @else
                                            {{ $posicao }}
                                        @endif
                                    </div>

                                    {{-- Avatar --}}
                                    @if ($lider->foto && $lider->foto->med_foto)
                                        <img src="{{ asset('storage/' . $lider->foto->med_foto) }}"
                                             alt="{{ $lider->nom_pessoa }}"
                                             loading="lazy"
                                             class="w-9 h-9 rounded-full object-cover border-2 {{ $isPodio ? 'border-amber-300 dark:border-amber-700' : 'border-gray-200 dark:border-zinc-600' }}">
                                    @else
                                        <div class="w-9 h-9 rounded-full flex items-center justify-center text-xs font-bold
                                                    {{ $isPodio
                                                        ? 'bg-gradient-to-br from-amber-100 to-orange-100 dark:from-amber-900/30 dark:to-orange-900/20 text-amber-700 dark:text-amber-400 border-2 border-amber-300 dark:border-amber-700'
                                                        : 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 border-2 border-indigo-200 dark:border-indigo-800' }}">
                                            {{ $iniciais }}
                                        </div>
                                    @endif

                                    {{-- Nome --}}
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                            {{ $lider->nom_apelido ?? $lider->nom_pessoa }}
                                        </p>
                                    </div>

                                    {{-- Pontuação --}}
                                    <div class="flex items-center gap-1.5 flex-shrink-0">
                                        <x-heroicon-s-star class="w-4 h-4 {{ $isPodio ? 'text-amber-500' : 'text-gray-400 dark:text-zinc-500' }}" />
                                        <span class="text-sm font-black tabular-nums {{ $isPodio ? 'text-amber-600 dark:text-amber-400' : 'text-gray-600 dark:text-gray-300' }}">
                                            {{ number_format($lider->qtd_pontos_total, 0, '', '.') }}
                                        </span>
                                        <span class="text-[10px] text-gray-400 dark:text-zinc-500 font-medium">pts</span>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </div>
            </section>
        </div>

        {{-- ── Terceira fileira (Próximos Eventos + Entre em Contato) ────── --}}
        <div class="grid gap-6 md:gap-8 lg:grid-cols-2">
            {{-- 📅 PRÓXIMOS EVENTOS --}}
            <section
                class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-gray-200 dark:border-zinc-700 overflow-hidden"
                aria-label="Próximos eventos">
                <header
                    class="px-5 md:px-6 py-4 border-b border-gray-100 dark:border-zinc-700 flex justify-between items-center bg-gray-50/50 dark:bg-zinc-800/50">
                    <h2 class="font-bold text-gray-800 dark:text-white flex items-center gap-2">
                        <x-heroicon-s-calendar-days class="w-5 h-5 text-blue-600" />
                        Próximos Eventos
                    </h2>
                    <a href="{{ route('eventos.index') }}"
                        class="text-xs font-bold text-blue-600 hover:text-blue-700 uppercase tracking-wider focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500 rounded"
                        aria-label="Ver todos os eventos">Ver Todos</a>
                </header>

                <div class="p-4 md:p-6">
                    <ul class="divide-y divide-gray-100 dark:divide-zinc-700 -my-4" role="list">
                        @forelse ($proximoseventos as $evento)
                            <li class="py-4 flex items-center gap-4 group">
                                <div
                                    class="flex-shrink-0 w-12 h-12 bg-blue-50 dark:bg-blue-900/20 text-blue-600 rounded-xl flex flex-col items-center justify-center border border-blue-100 dark:border-blue-800/30">
                                    <span
                                        class="text-sm font-bold leading-none">{{ $evento->dat_inicio->format('d') }}</span>
                                    <span
                                        class="text-[10px] uppercase font-black">{{ $evento->dat_inicio->translatedFormat('M') }}</span>
                                </div>

                                <div class="flex-1 min-w-0">
                                    <h3
                                        class="text-sm font-bold text-gray-900 dark:text-white truncate group-hover:text-blue-600 transition-colors">
                                        {{ $evento->des_evento }}
                                    </h3>
                                    <div class="mt-1 flex items-center gap-2">
                                        <x-badge-movimento :sigla="$evento->movimento?->des_sigla" size="xs" />
                                    </div>
                                </div>

                                <a href="{{ route('eventos.index') }}"
                                    class="p-2 text-gray-400 hover:text-blue-600 transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500 rounded"
                                    aria-label="Ver evento {{ $evento->des_evento }}">
                                    <x-heroicon-s-chevron-right class="w-5 h-5" />
                                </a>
                            </li>
                        @empty
                            <div class="py-8 text-center">
                                <x-heroicon-o-calendar class="w-10 h-10 mx-auto text-gray-300 dark:text-zinc-600 mb-2" />
                                <p class="text-sm text-gray-400 dark:text-gray-500">Nenhum evento agendado.</p>
                            </div>
                        @endforelse
                    </ul>
                </div>
            </section>

            {{-- ✉️ ENTRE EM CONTATO --}}
            <section
                class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-gray-200 dark:border-zinc-700 overflow-hidden"
                aria-label="Entre em Contato">
                <header
                    class="px-5 md:px-6 py-4 border-b border-gray-100 dark:border-zinc-700 flex justify-between items-center bg-gray-50/50 dark:bg-zinc-800/50">
                    <h2 class="font-bold text-gray-800 dark:text-white flex items-center gap-2">
                        <x-heroicon-s-envelope class="w-5 h-5 text-indigo-500" />
                        Entre em Contato
                    </h2>
                </header>

                <div class="p-4 md:p-6 space-y-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Escolha o movimento com o qual deseja se comunicar, preencha seus dados e envie sua mensagem.
                    </p>

                    <form action="{{ route('home.contato') }}" method="POST" class="space-y-4">
                        @csrf
                        <flux:input name="nom_contato" label="Nome" placeholder="Seu nome completo" required />
                        <flux:input name="tel_contato" label="Telefone" placeholder="(00) 00000-0000" required />
                        <flux:input name="eml_contato" label="Email" type="email" placeholder="seu@email.com" />
                        <flux:select name="idt_movimento" label="Movimento" placeholder="Selecione..." required>
                            @foreach ($movimentos as $movimento)
                                <flux:select.option value="{{ $movimento->idt_movimento }}">{{ $movimento->des_sigla }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:textarea name="txt_mensagem" label="Mensagem" placeholder="Como podemos ajudar você?" rows="3" required />
                        <div class="flex justify-end mt-2">
                            <flux:button type="submit" variant="primary" icon="paper-airplane"
                                class="bg-gradient-to-r from-indigo-500 to-purple-500 hover:from-indigo-600 hover:to-purple-600 border-none shadow-md text-white w-full sm:w-auto">
                                Enviar Mensagem
                            </flux:button>
                        </div>
                    </form>
                </div>
            </section>
        </div>

    </div>

    {{-- ════════════════════════════════════════════════════════════════════════
         MODAL — MERCADINHO (Consulta de Saldo)
         ════════════════════════════════════════════════════════════════════════ --}}
    <flux:modal name="modal-mercadinho" class="w-full max-w-lg">
        <livewire:mercadinho.consulta-saldo />
    </flux:modal>

</x-layouts.app>
