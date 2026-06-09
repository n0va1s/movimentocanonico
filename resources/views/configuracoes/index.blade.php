<x-layouts.app :title="'Configurações do Sistema'">
    <section class="p-6 w-full max-w-[80vw] ml-auto">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Configurações</h1>
            <p class="text-gray-700 dark:text-gray-400 mt-1">Gerencie os tipos e classificações do sistema</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @if (Auth::user()->isAdmin())
                <!-- Card 1 -->
                <a href="{{ route('movimento.index') }}"
                    class="block bg-white dark:bg-zinc-600 rounded-xl shadow hover:shadow-lg transition p-6 text-center">
                    <div class="flex flex-col items-center justify-center h-full">
                        <x-heroicon-o-flag class="w-12 h-12 text-blue-600 mb-4" />
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Tipos de Movimentos</h2>
                        <p class="text-gray-600 dark:text-gray-400 mt-2 text-sm">Gerencie os tipos de movimentos e siglas
                            como ECC, VEM, Segue-Me, etc.</p>
                    </div>
                </a>

                <!-- Card 2 -->
                <a href="{{ route('responsavel.index') }}"
                    class="block bg-white dark:bg-zinc-600 rounded-xl shadow hover:shadow-lg transition p-6 text-center">
                    <div class="flex flex-col items-center justify-center h-full">
                        <x-heroicon-o-user-group class="w-12 h-12 text-green-600 mb-4" />
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Tipos de Responsáveis</h2>
                        <p class="text-gray-600 dark:text-gray-400 mt-2 text-sm">Configure os vínculos familiares como pai,
                            mãe, padrinho e outros.</p>
                    </div>
                </a>

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

                <!-- Card 4 -->
                <a href="{{ route('restricao.index') }}"
                    class="block bg-white dark:bg-zinc-600 rounded-xl shadow hover:shadow-lg transition p-6 text-center">
                    <div class="flex flex-col items-center justify-center h-full">
                        <x-heroicon-o-exclamation-circle class="w-12 h-12 text-red-600 mb-4" />
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Tipos de Restrições</h2>
                        <p class="text-gray-600 dark:text-gray-400 mt-2 text-sm">Adicione restrições alimentares ou de saúde
                            como alergias ou PNE.</p>
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
            <a href="{{ route('vem.index') }}"
                class="block bg-white dark:bg-zinc-600 rounded-xl shadow hover:shadow-lg transition p-6 text-center">
                <div class="flex flex-col items-center justify-center h-full">
                    <x-heroicon-o-document-text class="w-12 h-12 text-indigo-600 mb-4" />
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Fichas VEM</h2>
                    <p class="text-gray-600 dark:text-gray-400 mt-2 text-sm">Gerencie as inscrições, status e aprovações de Fichas VEM.</p>
                </div>
            </a>

            <!-- Card 8: Fichas SGM -->
            <a href="{{ route('sgm.index') }}"
                class="block bg-white dark:bg-zinc-600 rounded-xl shadow hover:shadow-lg transition p-6 text-center">
                <div class="flex flex-col items-center justify-center h-full">
                    <x-heroicon-o-document-text class="w-12 h-12 text-rose-600 mb-4" />
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Fichas SGM</h2>
                    <p class="text-gray-600 dark:text-gray-400 mt-2 text-sm">Gerencie as inscrições, status e aprovações de Fichas SGM.</p>
                </div>
            </a>

            <!-- Card 9: Fichas ECC -->
            <a href="{{ route('ecc.index') }}"
                class="block bg-white dark:bg-zinc-600 rounded-xl shadow hover:shadow-lg transition p-6 text-center">
                <div class="flex flex-col items-center justify-center h-full">
                    <x-heroicon-o-document-text class="w-12 h-12 text-emerald-600 mb-4" />
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Fichas ECC</h2>
                    <p class="text-gray-600 dark:text-gray-400 mt-2 text-sm">Gerencie as inscrições, status e aprovações de Fichas ECC.</p>
                </div>
            </a>
        </div>
    </section>
</x-layouts.app>
