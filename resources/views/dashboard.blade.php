<x-layouts.app :title="__('Dashboard')">
    <div class="p-6 w-full max-w-7xl mx-auto space-y-8">

        {{-- Cabeçalho de Boas-vindas --}}
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Olá, {{ Auth::user()->name }}!</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Essa é a força da sua comunidade</p>
        </div>

        {{-- Card de Saldo do Mercadinho (Pessoal) --}}
        @if($contaMercadinho)
            @php
                $saldo = (float) $contaMercadinho->val_saldo;
            @endphp
            <div class="bg-gradient-to-r from-blue-600 to-indigo-700 dark:from-zinc-800 dark:to-zinc-900 text-white rounded-2xl p-6 shadow-md flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border border-blue-500/20">
                <div class="space-y-1">
                    <span class="text-xs font-bold uppercase tracking-wider text-blue-200 dark:text-zinc-400">Meu Extrato do Mercadinho</span>
                    <h2 class="text-lg font-bold text-white flex items-center gap-2">
                        <x-heroicon-s-shopping-cart class="w-5 h-5 text-white" />
                        {{ $contaMercadinho->evento->des_evento }}
                    </h2>
                </div>

                <div class="flex items-center gap-6">
                    <div class="text-right">
                        <div class="text-xs text-blue-200 dark:text-zinc-400">Meu Saldo Atual</div>
                        <div class="text-2xl font-black {{ $saldo < 0 ? 'text-red-300' : 'text-green-300' }}">
                            R$ {{ number_format($saldo, 2, ',', '.') }}
                        </div>
                    </div>
                    
                    <flux:modal.trigger name="meu-extrato">
                        <flux:button variant="filled" size="sm" class="bg-white hover:bg-zinc-150 text-blue-600 dark:bg-zinc-700 dark:text-white dark:hover:bg-zinc-650 font-bold px-4 py-2 rounded-xl cursor-pointer">
                            Ver Extrato
                        </flux:button>
                    </flux:modal.trigger>
                </div>
            </div>
            
            {{-- Modal do Extrato Pessoal --}}
            <flux:modal name="meu-extrato" class="w-full max-w-2xl">
                <div class="space-y-6">
                    <div>
                        <flux:heading size="lg">Meu Extrato do Mercadinho</flux:heading>
                        <flux:subheading>{{ $contaMercadinho->evento->des_evento }}</flux:subheading>
                    </div>

                    <div class="border border-zinc-200 dark:border-zinc-700 rounded-xl overflow-hidden max-h-96 overflow-y-auto">
                        @if($contaMercadinho->transacoes->isEmpty())
                            <div class="p-8 text-center text-zinc-500 italic text-sm">
                                Nenhuma compra ou depósito registrado neste evento.
                            </div>
                        @else
                            <flux:table>
                                <flux:table.columns>
                                    <flux:table.column>Data</flux:table.column>
                                    <flux:table.column>Item / Descrição</flux:table.column>
                                    <flux:table.column>Valor</flux:table.column>
                                </flux:table.columns>

                                <flux:table.rows>
                                    @foreach($contaMercadinho->transacoes as $trans)
                                        @php
                                            $isDebito = $trans->tip_transacao === 'C';
                                        @endphp
                                        <flux:table.row>
                                            <flux:table.cell class="text-xs">
                                                {{ $trans->dat_transacao->format('d/m/Y H:i') }}
                                            </flux:table.cell>
                                            <flux:table.cell class="font-medium text-xs">
                                                {{ $trans->nom_item ?? $trans->des_transacao }}
                                                @if($trans->qtd_item)
                                                    <span class="text-zinc-400"> (x{{ $trans->qtd_item }})</span>
                                                @endif
                                            </flux:table.cell>
                                            <flux:table.cell class="font-bold text-xs">
                                                <span class="{{ $isDebito ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                                    {{ $isDebito ? '-' : '+' }} R$ {{ number_format($trans->val_transacao, 2, ',', '.') }}
                                                </span>
                                            </flux:table.cell>
                                        </flux:table.row>
                                    @endforeach
                                </flux:table.rows>
                            </flux:table>
                        @endif
                    </div>
                </div>
            </flux:modal>
        @endif

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
            <livewire:vendas.consulta-saldo />
        </div>
    </div>
</x-layouts.app>
