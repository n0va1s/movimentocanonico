<x-layouts.app :title="'Configurações do Sistema'">
    <section class="p-6 w-full max-w-[80vw] ml-auto">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Configurações</h1>
            <p class="text-gray-700 dark:text-gray-400 mt-1">Gerencie os tipos e classificações do sistema</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @if (Auth::user()->isAdmin())
                <!-- Card 3 -->
                <a href="{{ route('equipe.index') }}"
                    class="block bg-white dark:bg-zinc-600 rounded-xl shadow hover:shadow-lg transition p-6 text-center">
                    <div class="flex flex-col items-center justify-center h-full">
                        <x-heroicon-o-cog-8-tooth class="w-12 h-12 text-purple-600 mb-4" />
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Tipos de Equipes</h2>
                        <p class="text-gray-600 dark:text-gray-400 mt-2 text-sm">Organize as equipes como cozinha, oração,
                            comunicação e outras.</p>
                    </div>
                </a>

                <!-- Card 5 -->
                <a href="{{ route('role.index') }}"
                    class="block bg-white dark:bg-zinc-600 rounded-xl shadow hover:shadow-lg transition p-6 text-center">
                    <div class="flex flex-col items-center justify-center h-full">
                        <x-heroicon-o-user class="w-12 h-12 text-sky-400 mb-4" />
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Perfil de Usuário</h2>
                        <p class="text-gray-600 dark:text-gray-400 mt-2 text-sm">Defina as permissões de acesso do usuário.
                        </p>
                    </div>
                </a>

                <!-- Card: Pessoas -->
                <a href="{{ route('pessoas.index') }}"
                    class="block bg-white dark:bg-zinc-600 rounded-xl shadow hover:shadow-lg transition p-6 text-center">
                    <div class="flex flex-col items-center justify-center h-full">
                        <x-heroicon-o-users class="w-12 h-12 text-indigo-600 mb-4" />
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Pessoas</h2>
                        <p class="text-gray-600 dark:text-gray-400 mt-2 text-sm">Gerencie o cadastro de pessoas no sistema.</p>
                    </div>
                </a>

                <!-- Card: Limpar Cache -->
                <a href="/limpar-tudo"
                    class="block bg-white dark:bg-zinc-600 rounded-xl shadow hover:shadow-lg transition p-6 text-center">
                    <div class="flex flex-col items-center justify-center h-full">
                        <x-heroicon-o-trash class="w-12 h-12 text-yellow-600 mb-4" />
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Limpar Cache</h2>
                        <p class="text-gray-600 dark:text-gray-400 mt-2 text-sm">Limpa as configurações, views e otimizações em cache do sistema.</p>
                    </div>
                </a>

                <!-- Card: Otimizar Tudo -->
                <a href="/otimizar-tudo"
                    class="block bg-white dark:bg-zinc-600 rounded-xl shadow hover:shadow-lg transition p-6 text-center">
                    <div class="flex flex-col items-center justify-center h-full">
                        <x-heroicon-o-bolt class="w-12 h-12 text-amber-500 mb-4" />
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Otimizar Tudo</h2>
                        <p class="text-gray-600 dark:text-gray-400 mt-2 text-sm">Executa a otimização de cache de rotas e arquivos do Laravel.</p>
                    </div>
                </a>

                <!-- Card: Criar Link Storage -->
                <a href="/storage-link"
                    class="block bg-white dark:bg-zinc-600 rounded-xl shadow hover:shadow-lg transition p-6 text-center">
                    <div class="flex flex-col items-center justify-center h-full">
                        <x-heroicon-o-link class="w-12 h-12 text-blue-500 mb-4" />
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Storage Link</h2>
                        <p class="text-gray-600 dark:text-gray-400 mt-2 text-sm">Recria o link simbólico da pasta storage pública para arquivos.</p>
                    </div>
                </a>

                <!-- Card: Encerrar Eventos Finalizados -->
                <a href="/encerrar-eventos"
                    class="block bg-white dark:bg-zinc-600 rounded-xl shadow hover:shadow-lg transition p-6 text-center">
                    <div class="flex flex-col items-center justify-center h-full">
                        <x-heroicon-o-archive-box class="w-12 h-12 text-red-500 mb-4" />
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Encerrar Eventos</h2>
                        <p class="text-gray-600 dark:text-gray-400 mt-2 text-sm">Busca por eventos cuja data de término já passou e os encerra/arquiva.</p>
                    </div>
                </a>
            @endif

            <!-- Card 6 -->
            <a href="{{ route('eventos.importar') }}"
                class="block bg-white dark:bg-zinc-600 rounded-xl shadow hover:shadow-lg transition p-6 text-center">
                <div class="flex flex-col items-center justify-center h-full">
                    <x-heroicon-o-cloud-arrow-up class="w-12 h-12 text-teal-600 mb-4" />
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Importar Planilhas</h2>
                    <p class="text-gray-600 dark:text-gray-400 mt-2 text-sm">Importe participantes e trabalhadores em lote a partir de planilhas Excel/CSV.</p>
                </div>
            </a>

            <!-- Card 7: Fichas VEM -->
            @if (Auth::user()->isAdmin() || (Auth::user()->isDirig() && Auth::user()->idt_movimento === \App\Models\TipoMovimento::VEM))
            <a href="{{ route('vem.index') }}"
                class="block bg-white dark:bg-zinc-600 rounded-xl shadow hover:shadow-lg transition p-6 text-center">
                <div class="flex flex-col items-center justify-center h-full">
                    <x-heroicon-o-document-text class="w-12 h-12 text-indigo-600 mb-4" />
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Fichas VEM</h2>
                    <p class="text-gray-600 dark:text-gray-400 mt-2 text-sm">Gerencie as inscrições, status e aprovações de Fichas VEM.</p>
                </div>
            </a>
            @endif

            <!-- Card 8: Fichas SGM -->
            @if (Auth::user()->isAdmin() || (Auth::user()->isDirig() && Auth::user()->idt_movimento === \App\Models\TipoMovimento::SegueMe))
            <a href="{{ route('sgm.index') }}"
                class="block bg-white dark:bg-zinc-600 rounded-xl shadow hover:shadow-lg transition p-6 text-center">
                <div class="flex flex-col items-center justify-center h-full">
                    <x-heroicon-o-document-text class="w-12 h-12 text-rose-600 mb-4" />
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Fichas SGM</h2>
                    <p class="text-gray-600 dark:text-gray-400 mt-2 text-sm">Gerencie as inscrições, status e aprovações de Fichas SGM.</p>
                </div>
            </a>
            @endif

            <!-- Card 9: Fichas ECC -->
            @if (Auth::user()->isAdmin() || (Auth::user()->isDirig() && Auth::user()->idt_movimento === \App\Models\TipoMovimento::ECC))
            <a href="{{ route('ecc.index') }}"
                class="block bg-white dark:bg-zinc-600 rounded-xl shadow hover:shadow-lg transition p-6 text-center">
                <div class="flex flex-col items-center justify-center h-full">
                    <x-heroicon-o-document-text class="w-12 h-12 text-emerald-600 mb-4" />
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Fichas ECC</h2>
                    <p class="text-gray-600 dark:text-gray-400 mt-2 text-sm">Gerencie as inscrições, status e aprovações de Fichas ECC.</p>
                </div>
            </a>
            @endif
        </div>
    </section>
</x-layouts.app>
