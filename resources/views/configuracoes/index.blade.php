<x-layouts.app :title="'Configurações do Sistema'">
    <section class="p-6 w-full max-w-[80vw] ml-auto">
        <div class="mb-6">
            <flux:heading size="xl" class="text-indigo-900 dark:text-indigo-100 font-bold tracking-tight mb-1">Configurações</flux:heading>
            <p class="text-gray-700 dark:text-gray-400 mt-1">Gerencie os tipos e classificações do sistema</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @if (Auth::user()->isAdmin())
                <!-- Card: Organização -->
                <a href="{{ route('organizacao.index') }}"
                    class="block bg-white dark:bg-zinc-600 rounded-xl shadow hover:shadow-lg transition p-6 text-center">
                    <div class="flex flex-col items-center justify-center h-full">
                        <x-heroicon-o-building-library class="w-12 h-12 text-violet-600 mb-4" />
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Organização</h2>
                        <p class="text-gray-600 dark:text-gray-400 mt-2 text-sm">Gerencie paróquias, movimentos e equipes de forma unificada.</p>
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

        </div>
    </section>
</x-layouts.app>
