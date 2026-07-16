<x-layouts.app :title="'Aura — Sua Jornada'">
    <section class="p-4 md:p-8 w-full max-w-7xl mx-auto space-y-8">

        <div class="relative overflow-hidden bg-gradient-to-br from-indigo-700 via-purple-700 to-indigo-900 rounded-[2rem] p-6 md:p-10 shadow-2xl text-white">
            <div class="absolute top-0 right-0 -mt-10 -mr-10 opacity-10 rotate-12">
                <x-heroicon-s-sparkles class="w-80 h-80" />
            </div>

            <div class="relative z-10 flex flex-col lg:flex-row items-center justify-between gap-8">
                <div class="flex flex-col md:flex-row items-center gap-6 text-center md:text-left">
                    <div class="relative group">
                        <div class="w-28 h-28 rounded-3xl p-1 bg-gradient-to-tr from-yellow-400 to-orange-500 shadow-lg transform group-hover:rotate-3 transition-transform">
                            <img src="{{ $pessoa->foto ? asset('storage/' . $pessoa->foto->med_foto) : asset('images/default-avatar.png') }}"
     class="w-full h-full rounded-[1.4rem] object-cover border-4 border-indigo-700 shadow-inner"
     alt="Avatar de {{ $pessoa->nom_apelido ?? $pessoa->nom_pessoa }}"
     fetchpriority="high"
     decoding="sync">
                        </div>
                        <div class="absolute -bottom-2 -right-2 bg-yellow-400 text-indigo-900 text-[10px] font-black px-2 py-1 rounded-lg uppercase tracking-tighter shadow-xl">
                            Lv. {{ floor(($pessoa->qtd_pontos_total ?? 0) / 1000) + 1 }}
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-center md:justify-start gap-2 mb-1">
                            <h1 class="text-3xl font-black tracking-tight" aria-label="Nome do usuário: {{ $pessoa->nom_apelido ?? $pessoa->nom_pessoa }}">{{ $pessoa->nom_apelido ?? $pessoa->nom_pessoa }}</h1>
                            <x-heroicon-s-check-badge class="w-6 h-6 text-blue-400" aria-label="Conta verificada" />
                        </div>
                        <p class="text-indigo-100 font-medium opacity-90 max-w-sm">
                            Sua Aura reflete sua dedicação. Participe de eventos para desbloquear novas conquistas!
                        </p>
                        <div class="flex flex-wrap justify-center md:justify-start gap-2 mt-4">
                            @if($pessoa->tip_habilidade)
                                <span class="bg-white/20 backdrop-blur-sm text-[10px] font-bold px-3 py-1 rounded-full border border-white/10 uppercase tracking-widest">
                                    Classe: {{ $pessoa->tip_habilidade }}
                                </span>
                            @endif
                            <span class="bg-indigo-500/40 backdrop-blur-sm text-[10px] font-bold px-3 py-1 rounded-full border border-white/10 uppercase tracking-widest">
                                {{ $pessoa->tip_estado_civil == 'S' ? 'Desbravador' : 'Comunidade' }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-4 w-full lg:w-auto">
                    <div class="flex-1 bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl p-5 flex items-center gap-4 min-w-[200px]">
                        <div class="bg-yellow-400 p-3 rounded-xl shadow-lg shadow-yellow-500/40">
                            <x-heroicon-s-bolt class="w-6 h-6 text-indigo-900" />
                        </div>
                        <div>
                            <p class="text-[10px] font-black uppercase text-indigo-200 tracking-widest leading-none">Total XP</p>
                            <p class="text-3xl font-black italic">{{ number_format($pessoa->qtd_pontos_total ?? 0, 0, ',', '.') }}</p>
                        </div>
                    </div>
                    <div class="flex-1 bg-indigo-950/40 backdrop-blur-xl border border-white/10 rounded-2xl p-5 flex items-center gap-4 min-w-[200px]">
                        <div class="bg-blue-500 p-3 rounded-xl shadow-lg shadow-blue-500/40">
                            <x-heroicon-s-trophy class="w-6 h-6 text-white" />
                        </div>
                        <div>
                            <p class="text-[10px] font-black uppercase text-blue-200 tracking-widest leading-none">Ranking</p>
                            <p class="text-3xl font-black italic">#{{ $posicaoNoRanking ?? '--' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

            <aside class="lg:col-span-3 space-y-6 lg:sticky lg:top-8">
                <div class="bg-white dark:bg-zinc-800 rounded-3xl p-6 border border-gray-100 dark:border-zinc-700 shadow-sm">
                    <h3 class="text-sm font-black uppercase text-gray-400 dark:text-zinc-500 mb-4 flex items-center gap-2" aria-hidden="true">
                        <x-heroicon-o-chart-bar class="w-4 h-4" /> Resumo de Atividades
                    </h3>
                    <div class="space-y-4">
                        <div class="flex justify-between items-end">
                            <span class="text-sm font-bold text-gray-600 dark:text-zinc-400">Total de Eventos</span>
                            <span class="text-xl font-black text-indigo-600 dark:text-indigo-400">{{ count($timeline) > 0 ? collect($timeline)->pluck('events')->flatten(1)->count() : 0 }}</span>
                        </div>
                        <div class="space-y-1">
                            <div class="flex justify-between text-[10px] font-bold uppercase text-gray-400">
                                <span>Progresso Nível</span>
                                <span>{{ ($pessoa->qtd_pontos_total % 1000) / 10 }}%</span>
                            </div>
                            <div class="w-full h-2 bg-gray-100 dark:bg-zinc-900 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full" style="width: {{ ($pessoa->qtd_pontos_total % 1000) / 10 }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded-3xl p-6 border border-indigo-100 dark:border-indigo-800/50">
                    <h3 class="text-sm font-bold text-indigo-900 dark:text-indigo-300 mb-3">Próximo Desafio?</h3>
                    <p class="text-xs text-indigo-700 dark:text-indigo-400 mb-4 italic">"Onde dois ou três estiverem reunidos..."</p>
                    <a href="{{ route('eventos.index') }}" aria-label="Acessar página de eventos">
                       <button class="w-full py-2 bg-white dark:bg-zinc-800 text-indigo-600 dark:text-indigo-400 rounded-xl text-xs font-black uppercase shadow-sm hover:shadow-md transition-all">Ver Eventos</button>
                    </a>
                </div>
            </aside>

            <main class="lg:col-span-9 space-y-10">
                <div class="flex items-center gap-4 mb-2">
                    <div class="h-[2px] flex-1 bg-gray-100 dark:bg-zinc-800"></div>
                    <h2 class="text-sm font-black uppercase tracking-[0.2em] text-gray-400">Sua Linha do Tempo</h2>
                    <div class="h-[2px] flex-1 bg-gray-100 dark:bg-zinc-800"></div>
                </div>

                @forelse ($timeline as $yearData)
                    <div class="relative">
                        <div class="flex items-center gap-4 mb-6">
                            <div class="bg-gray-900 dark:bg-white text-white dark:text-gray-900 px-4 py-1 rounded-full text-sm font-black italic shadow-lg">
                                {{ $yearData['year'] }}
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach ($yearData['events'] as $eventEntry)
                                <div class="group relative bg-white dark:bg-zinc-800 p-5 rounded-3xl border border-gray-100 dark:border-zinc-700 hover:border-indigo-300 dark:hover:border-indigo-500/50 transition-all hover:shadow-xl hover:shadow-indigo-500/5">

                                    <div class="flex justify-between items-start mb-3">
                                        <div class="text-center bg-gray-50 dark:bg-zinc-900 rounded-2xl px-3 py-2 border border-gray-100 dark:border-zinc-700">
                                            <span class="block text-lg font-black leading-none text-indigo-600 dark:text-indigo-400">{{ \Carbon\Carbon::parse($eventEntry['date'])->format('d') }}</span>
                                            <span class="text-[9px] font-bold uppercase text-gray-400 tracking-tighter">{{ \Carbon\Carbon::parse($eventEntry['date'])->translatedFormat('M') }}</span>
                                        </div>

                                        @if ($eventEntry['event']->movimento)
                                            <x-badge-movimento :sigla="$eventEntry['event']->movimento->des_sigla" class="shadow-sm" />
                                        @endif
                                    </div>

                                    <div>
                                        <h4 class="text-lg font-bold text-gray-900 dark:text-white leading-tight mb-1 group-hover:text-indigo-600 transition-colors">
                                            {{ $eventEntry['event']->des_evento }}
                                        </h4>
                                        <div class="flex items-center justify-between gap-2">
                                            <div class="flex items-center gap-2">
                                                <span class="text-[10px] font-black px-2 py-0.5 rounded-lg bg-zinc-100 dark:bg-zinc-700 text-zinc-500 dark:text-zinc-400 uppercase">
                                                    {{ $eventEntry['type'] }}
                                                </span>
                                                @if ($eventEntry['details']['coordenador'] ?? false)
                                                    <span class="flex items-center gap-1 text-[10px] font-bold text-yellow-600 dark:text-yellow-400 uppercase italic">
                                                        <x-heroicon-s-star class="w-3 h-3" /> Liderança
                                                    </span>
                                                @endif
                                            </div>
                                            
                                            <span class="bg-yellow-400/20 text-yellow-700 dark:text-yellow-400 text-[10px] font-black px-2.5 py-1 rounded-full border border-yellow-400/30 flex items-center gap-1 shadow-sm">
                                                <x-heroicon-s-bolt class="w-3.5 h-3.5 text-yellow-500" />
                                                +{{ $eventEntry['pontos'] }} XP
                                            </span>
                                        </div>
                                    </div>

                                    @if ($eventEntry['type'] === 'Trabalhador')
                                        <div class="mt-4 pt-3 border-t border-dashed border-gray-100 dark:border-zinc-700 flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <x-heroicon-o-users class="w-4 h-4 text-gray-400" />
                                                <span class="text-[11px] font-bold text-gray-500">{{ $eventEntry['details']['equipe'] }}</span>
                                            </div>
                                            <x-heroicon-o-chevron-right class="w-4 h-4 text-gray-300 group-hover:translate-x-1 transition-transform" />
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="py-20">
                        <x-sem-registro icon="heroicon-o-sparkles" title="Sua aura ainda está crescendo"
                            description="Participe do seu primeiro evento e veja sua história ganhar vida aqui!" />
                    </div>
                @endforelse
            </main>
        </div>
    </section>
</x-layouts.app>
