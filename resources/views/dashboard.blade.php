<x-layouts.app :title="__('Dashboard')">
    <div class="p-6 w-full max-w-7xl mx-auto space-y-8">

        {{-- Cabeçalho de Boas-vindas --}}
        <div>
        <flux:heading size="xl" class="text-indigo-900 dark:text-indigo-100 font-bold tracking-tight mb-1">
            Olá, {{ Auth::user()->name }}!
        </flux:heading>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Essa é a força da sua comunidade</p>
        </div>


        {{-- Grid de Estatísticas (Totalizadores) --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <a href="{{ route('eventos.index') }}">
                <x-dashboard-stat-card title="Eventos Ativos" :value="$qtdEventosAtivos" icon="heroicon-o-calendar" color="blue" />
            </a>
            <x-dashboard-stat-card title="Total de Fichas" :value="$qtdFichasCadastradas" icon="heroicon-o-clipboard-document"
                color="yellow" />
            <x-dashboard-stat-card title="Participantes" :value="$qtdParticipantesCadastrados" icon="heroicon-o-users" color="green" />
            <x-dashboard-stat-card title="Trabalhadores" :value="$qtdTrabalhadoresCadastrados" icon="heroicon-o-briefcase" color="purple" />
        </div>

        <div class="grid gap-8 lg:grid-cols-2">

            {{-- Lado Esquerdo: Próximos Eventos --}}
            <section
                class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-gray-200 dark:border-zinc-700 overflow-hidden">
                <header
                    class="px-6 py-4 border-b border-gray-100 dark:border-zinc-700 flex justify-between items-center bg-gray-50/50 dark:bg-zinc-800/50">
                    <h2 class="font-bold text-gray-800 dark:text-white flex items-center gap-2">
                        <x-heroicon-s-calendar-days class="w-5 h-5 text-blue-600" />
                        Próximos Eventos
                    </h2>
                    <a href="{{ route('eventos.index') }}"
                        class="text-xs font-bold text-blue-600 hover:text-blue-700 uppercase tracking-wider">Ver
                        Todos</a>
                </header>

                <div class="p-6">
                    <ul class="divide-y divide-gray-100 dark:divide-zinc-700 -my-4">
                        @forelse ($proximoseventos as $evento)
                            <li class="py-4 flex items-center gap-4 group">
                                <div
                                    class="flex-shrink-0 w-12 h-12 bg-blue-50 dark:bg-blue-900/20 text-blue-600 rounded-xl flex flex-col items-center justify-center border border-blue-100 dark:border-blue-800/30">
                                    <span
                                        class="text-sm font-bold leading-none">{{ \Carbon\Carbon::parse($evento->dat_inicio)->format('d') }}</span>
                                    <span
                                        class="text-[10px] uppercase font-black">{{ \Carbon\Carbon::parse($evento->dat_inicio)->translatedFormat('M') }}</span>
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
                                    class="p-2 text-gray-400 hover:text-blue-600 transition-colors">
                                    <x-heroicon-s-chevron-right class="w-5 h-5" />
                                </a>
                            </li>
                        @empty
                            <div class="py-8 text-center">
                                <p class="text-sm text-gray-500">Nenhum evento agendado.</p>
                            </div>
                        @endforelse
                    </ul>
                </div>
            </section>

            {{-- Lado Direito: Consulta de Saldo do Mercadinho --}}
            <livewire:mercadinho.consulta-saldo />
        </div>
    </div>
</x-layouts.app>
