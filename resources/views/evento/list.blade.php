<x-layouts.app title="Gerenciar Eventos">
    <section class="p-6 w-full max-w-7xl mx-auto">
        <x-session-alert />

        {{-- Cabeçalho --}}
        <header class="mb-8 flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Gerenciar Eventos</h1>
                <p class="text-gray-600 mt-1 dark:text-gray-400">Visualize e participe dos próximos encontros e desafios.</p>
            </div>

            @if (Auth::user()->isAdmin())
                <flux:button href="{{ route('eventos.create') }}" variant="primary" icon="plus" color="green">
                    Novo Evento
                </flux:button>
            @endif
        </header>

        {{-- Abas Ativos / Encerrados para Admin e Espec --}}
        @if (Auth::user()?->isAdmin() || Auth::user()?->isEspec())
            <div class="border-b border-gray-200 dark:border-zinc-700 mb-6">
                <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                    <a href="{{ route('eventos.index', array_merge(request()->except('page'), ['status' => 'ativos'])) }}" 
                       class="border-b-2 py-4 px-1 text-sm font-semibold transition-colors duration-200 flex items-center gap-2 {{ ($status ?? 'ativos') === 'ativos' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-zinc-400 dark:hover:text-zinc-200' }}">
                        <x-heroicon-o-calendar class="w-4 h-4" />
                        <span>Eventos Ativos</span>
                    </a>
                    <a href="{{ route('eventos.index', array_merge(request()->except('page'), ['status' => 'encerrados'])) }}" 
                       class="border-b-2 py-4 px-1 text-sm font-semibold transition-colors duration-200 flex items-center gap-2 {{ ($status ?? 'ativos') === 'encerrados' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-zinc-400 dark:hover:text-zinc-200' }}">
                        <x-heroicon-o-archive-box class="w-4 h-4" />
                        <span>Eventos Encerrados</span>
                    </a>
                </nav>
            </div>
        @endif

        {{-- Filtros  --}}
        <nav x-data="{ isFiltersOpen: false }" class="bg-white dark:bg-zinc-800 p-5 rounded-xl border border-gray-200 dark:border-zinc-700 shadow-sm mb-8">
            {{-- Versão Desktop (Sempre Visível) --}}
            <div class="hidden md:block">
                <form method="GET" action="{{ route('eventos.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center">
                    <input type="hidden" name="status" value="{{ $status ?? 'ativos' }}">
                    <div class="md:col-span-4">
                        <flux:input name="search" value="{{ $search }}" icon="magnifying-glass" placeholder="Buscar por descrição ou número..." />
                    </div>

                    <div class="md:col-span-3">
                        <flux:select name="idt_movimento" placeholder="Todos os Movimentos">
                            @foreach ($movimentos as $mov)
                                <flux:select.option value="{{ $mov->idt_movimento }}" :selected="$idt_movimento == $mov->idt_movimento">
                                    {{ $mov->des_sigla }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>

                    <div class="md:col-span-3">
                        <flux:select name="tip_evento" placeholder="Todos os Tipos">
                            <flux:select.option value="" :selected="empty($tip_evento)">Todos</flux:select.option>
                            <flux:select.option value="P" :selected="($tip_evento ?? null) === 'P'">Pós-encontro</flux:select.option>
                            <flux:select.option value="D" :selected="($tip_evento ?? null) === 'D'">Desafio</flux:select.option>
                            <flux:select.option value="E" :selected="($tip_evento ?? null) === 'E'">Encontro Anual</flux:select.option>
                        </flux:select>
                    </div>

                    <div class="md:col-span-2 flex gap-2">
                        <flux:button type="submit" variant="filled" color="blue" class="flex-1">Filtrar</flux:button>
                        @if ($search || $idt_movimento || ($tip_evento ?? null))
                            <flux:button href="{{ route('eventos.index', ['status' => $status ?? 'ativos']) }}" icon="x-mark" variant="ghost" />
                        @endif
                    </div>
                </form>
            </div>

            {{-- Versão Mobile (Expansível com Botão) --}}
            <div class="md:hidden">
                <button 
                    type="button" 
                    @click="isFiltersOpen = !isFiltersOpen" 
                    class="w-full flex items-center justify-between text-zinc-700 dark:text-zinc-200 font-semibold text-sm cursor-pointer focus:outline-none"
                >
                    <span class="flex items-center gap-2">
                        <flux:icon name="funnel" variant="outline" class="size-4 text-zinc-500" />
                        <span>Filtrar Eventos</span>
                    </span>
                    <flux:icon.chevron-down class="size-4 text-zinc-500 transition-transform duration-300" x-bind:class="isFiltersOpen ? 'rotate-180' : ''" />
                </button>

                <div 
                    x-show="isFiltersOpen" 
                    x-collapse
                    class="mt-4"
                >
                    <form method="GET" action="{{ route('eventos.index') }}" class="grid grid-cols-1 gap-4">
                        <input type="hidden" name="status" value="{{ $status ?? 'ativos' }}">
                        <div>
                            <flux:input name="search" value="{{ $search }}" icon="magnifying-glass" placeholder="Buscar por descrição ou número..." />
                        </div>

                        <div>
                            <flux:select name="idt_movimento" placeholder="Todos os Movimentos">
                                @foreach ($movimentos as $mov)
                                    <flux:select.option value="{{ $mov->idt_movimento }}" :selected="$idt_movimento == $mov->idt_movimento">
                                        {{ $mov->des_sigla }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>

                        <div>
                            <flux:select name="tip_evento" placeholder="Todos os Tipos">
                                <flux:select.option value="" :selected="empty($tip_evento)">Todos</flux:select.option>
                                <flux:select.option value="P" :selected="($tip_evento ?? null) === 'P'">Pós-encontro</flux:select.option>
                                <flux:select.option value="D" :selected="($tip_evento ?? null) === 'D'">Desafio</flux:select.option>
                                <flux:select.option value="E" :selected="($tip_evento ?? null) === 'E'">Encontro Anual</flux:select.option>
                            </flux:select>
                        </div>

                        <div class="flex gap-2">
                            <flux:button type="submit" variant="filled" color="blue" class="flex-1">Filtrar</flux:button>
                            @if ($search || $idt_movimento || ($tip_evento ?? null))
                                <flux:button href="{{ route('eventos.index', ['status' => $status ?? 'ativos']) }}" icon="x-mark" variant="ghost" />
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </nav>

        {{-- Grid de Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse ($eventos as $evento)
                <article class="flex flex-col bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 shadow-sm overflow-hidden hover:shadow-md transition-all duration-300">
                    
                    <div class="px-5 pt-5 flex justify-between items-start">
                        <span class="px-2 py-1 bg-gray-100 dark:bg-zinc-700 rounded text-[10px] font-black uppercase text-gray-400">
                            Nº {{ $evento->num_evento }}
                        </span>
                        <x-badge-movimento :sigla="$evento->movimento->des_sigla" />
                    </div>

                    <div class="p-5 flex-grow">
                        <h2 class="text-lg font-bold text-gray-800 dark:text-white mb-3 line-clamp-2 min-h-[3rem]">
                            {{ $evento->des_evento }}
                        </h2>

                        <div class="space-y-3">
                            <div class="flex items-center text-gray-600 dark:text-gray-300 text-sm">
                                <x-heroicon-o-calendar class="w-4 h-4 mr-2 text-blue-500" />
                                <span>{{ $evento->getDataInicioFormatada() }} a {{ $evento->getDataTerminoFormatada() }}</span>
                            </div>

                            <div class="flex items-center text-gray-500 dark:text-gray-400 text-xs font-bold uppercase tracking-wider">
                                <x-heroicon-o-tag class="w-4 h-4 mr-2 shrink-0" />
                                <span class="flex-1">{{ $evento->tip_evento->label() }}</span>
                                @if (Auth::user()?->isAdmin() || (Auth::user()?->isEspec() && (int) Auth::user()->idt_movimento === (int) $evento->idt_movimento))
                                    @can('acessar-gerenciamento-evento', $evento)
                                        <a href="{{ route('eventos.gerenciamento', $evento) }}"
                                           title="Gerenciamento"
                                           class="ml-2 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                                            <x-heroicon-o-cog-6-tooth class="w-8 h-8" />
                                        </a>
                                    @endcan
                                @endif
                            </div>
                        </div>
                    </div>

                    <footer class="p-4 bg-gray-50 dark:bg-zinc-800/50 border-t border-gray-100 dark:border-zinc-700 mt-auto">
                        {{-- Botão de participação — visível para todos os perfis --}}
                        @if ($evento->ja_inscrito_participante || $evento->ja_inscrito_voluntario)
                            <div class="w-full py-2 bg-gray-100 dark:bg-zinc-700 text-gray-500 dark:text-gray-400 rounded-md font-bold text-center flex items-center justify-center gap-2 border border-gray-200 dark:border-zinc-600">
                                <x-heroicon-s-check-circle class="w-5 h-5 text-green-500" />
                                Inscrição Confirmada
                            </div>
                        @else
                            @php
                                $tipoValue = $evento->tip_evento instanceof \UnitEnum ? $evento->tip_evento->value : $evento->tip_evento;
                            @endphp

                            @if ($tipoValue === 'E')
                                <flux:button href="{{ route('trabalhadores.create', ['evento' => $evento]) }}" color="green" class="w-full">
                                    Quero Trabalhar
                                </flux:button>
                            @elseif (Auth::user()->pessoa)
                                @php
                                    $textoBotao = ($tipoValue === 'P') ? 'Vou Participar' : 'Bora pro Desafio';
                                @endphp

                                <form method="POST" action="{{ route('participantes.confirm', ['evento' => $evento, 'pessoa' => Auth::user()->pessoa]) }}">
                                    @csrf
                                    <flux:button type="submit" color="green" class="w-full" loading>
                                        {{ $textoBotao }}
                                    </flux:button>
                                </form>
                            @else
                                <div class="w-full py-2 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Complete seu cadastro para participar.
                                </div>
                            @endif
                        @endif
                    </footer>
                </article>
            @empty
                <div class="col-span-full">
                    <x-sem-registro icon="heroicon-o-calendar" title="Nenhum evento encontrado" />
                </div>
            @endforelse
        </div>

        <div class="mt-8">
            {{ $eventos->links() }}
        </div>
    </section>
</x-layouts.app>