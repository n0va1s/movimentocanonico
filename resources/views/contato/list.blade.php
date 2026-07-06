<x-layouts.app :title="'Contato'">
    <section class="p-6 w-full max-w-[80vw] ml-auto">
        <div>
        </div>
        <div class="mb-6">
            <flux:heading size="xl" class="text-indigo-900 dark:text-indigo-100 font-bold tracking-tight mb-1">Contatos</flux:heading>
            <p class="text-gray-700 mt-1 dark:text-gray-400">Após responder exclua a mensagem</p>
        </div>

        {{-- Abas Pendentes / Resolvidos --}}
        <div class="border-b border-gray-200 dark:border-zinc-700 mb-6 mt-4">
            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                <a href="{{ route('contatos.index', array_merge(request()->except('page'), ['status' => 'pendentes'])) }}" 
                   class="border-b-2 py-4 px-1 text-sm font-semibold transition-colors duration-200 flex items-center gap-2 {{ ($status ?? 'pendentes') === 'pendentes' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-zinc-400 dark:hover:text-zinc-200' }}">
                    <x-heroicon-o-inbox class="w-4 h-4" />
                    <span>Pendentes</span>
                </a>
                <a href="{{ route('contatos.index', array_merge(request()->except('page'), ['status' => 'resolvidos'])) }}" 
                   class="border-b-2 py-4 px-1 text-sm font-semibold transition-colors duration-200 flex items-center gap-2 {{ ($status ?? 'pendentes') === 'resolvidos' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-zinc-400 dark:hover:text-zinc-200' }}">
                    <x-heroicon-o-archive-box class="w-4 h-4" />
                    <span>Resolvidos</span>
                </a>
            </nav>
        </div>

        {{-- Filtros --}}
        <div class="mb-6 bg-white dark:bg-zinc-800 p-5 rounded-xl border border-gray-200 dark:border-zinc-700 shadow-sm">
            <form method="GET" action="{{ route('contatos.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                <input type="hidden" name="status" value="{{ $status ?? 'pendentes' }}">
                
                <div>
                    <flux:input name="search" value="{{ $search }}" icon="magnifying-glass" placeholder="Buscar por nome..." />
                </div>

                <div>
                    <flux:select name="idt_movimento" placeholder="Todos os Movimentos">
                        <flux:select.option value="">Todos</flux:select.option>
                        @foreach ($movimentos as $mov)
                            <flux:select.option value="{{ $mov->idt_movimento }}" :selected="$idt_movimento == $mov->idt_movimento">
                                {{ $mov->des_sigla }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="flex gap-2">
                    <flux:button type="submit" variant="primary" class="bg-gradient-to-r from-indigo-500 to-purple-500 hover:from-indigo-600 hover:to-purple-600 border-none shadow-md flex-1" icon="magnifying-glass">Filtrar</flux:button>
                    @if ($search || $idt_movimento)
                        <flux:button href="{{ route('contatos.index', ['status' => $status ?? 'pendentes']) }}" icon="x-mark" variant="ghost" />
                    @endif
                </div>
            </form>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mt-4">
            @forelse ($contatos as $contato)
                <article class="flex flex-col bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 shadow-sm overflow-hidden hover:shadow-md transition-all duration-300">
                    
                    <div class="px-5 pt-5 flex justify-end items-start">
                        <x-badge-movimento :sigla="$contato->movimento->des_sigla" />
                    </div>

                    <div class="p-5 flex-grow">
                        <h2 class="text-lg font-bold text-gray-800 dark:text-white mb-3">
                            {{ $contato->nom_contato }}
                        </h2>

                        <div class="space-y-3">
                            <div class="flex items-center text-gray-600 dark:text-gray-300 text-sm">
                                <x-heroicon-o-envelope class="w-4 h-4 mr-2 text-blue-500" />
                                <span class="truncate" title="{{ $contato->eml_contato }}">{{ $contato->eml_contato }}</span>
                            </div>

                            <div class="flex items-center text-gray-600 dark:text-gray-300 text-sm">
                                <x-heroicon-o-phone class="w-4 h-4 mr-2 text-blue-500" />
                                <span>{{ $contato->tel_contato }}</span>
                            </div>

                            <div class="mt-4 text-sm text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-zinc-700/30 p-3 rounded-lg border border-gray-100 dark:border-zinc-700 h-24 overflow-y-auto">
                                {{ $contato->txt_mensagem }}
                            </div>
                        </div>
                    </div>

                    <footer class="p-4 bg-gray-50 dark:bg-zinc-800/50 border-t border-gray-100 dark:border-zinc-700 mt-auto">
                        @if(($status ?? 'pendentes') === 'pendentes')
                            <form method="POST" action="{{ route('contatos.destroy', $contato->idt_contato) }}"
                                onsubmit="return confirm('Tem certeza que deseja concluir este contato?');">
                                @csrf
                                @method('DELETE')
                                <flux:button type="submit" variant="primary" class="w-full bg-gradient-to-r from-indigo-500 to-purple-500 hover:from-indigo-600 hover:to-purple-600 border-none shadow-md" icon="check">
                                    Concluir
                                </flux:button>
                            </form>
                        @else
                            <div class="w-full py-2 bg-gray-100 dark:bg-zinc-700 text-gray-500 dark:text-gray-400 rounded-md font-bold text-center flex items-center justify-center gap-2 border border-gray-200 dark:border-zinc-600 text-sm">
                                <x-heroicon-s-check-circle class="w-5 h-5 text-green-500" />
                                Resolvido em {{ $contato->deleted_at?->format('d/m/Y') }}
                            </div>
                        @endif
                    </footer>
                </article>
            @empty
                <div class="col-span-full">
                    <x-sem-registro icon="heroicon-o-inbox" title="Nenhum contato encontrado" />
                </div>
            @endforelse
        </div>

        <div class="mt-6">
            {{ $contatos->links() }}
        </div>
    </section>
</x-layouts.app>
