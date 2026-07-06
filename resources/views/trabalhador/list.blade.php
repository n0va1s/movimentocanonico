<x-layouts.app :title="'Trabalhador'">
    <section class="p-6 w-full max-w-[80vw] ml-auto">
        <div class="mb-6">
            <flux:heading size="xl" class="text-indigo-900 dark:text-indigo-100 font-bold tracking-tight mb-1">Lista de Trabalhadores</flux:heading>
            @if ($evento?->exists)
                <p class="text-sm sm:text-base text-gray-600 dark:text-gray-300 mt-1">
                    Evento: <b>{{ $evento->des_evento }}</b>
                </p>
            @endif
        </div>
        <div class="flex justify-between items-center mb-4">
            <form method="GET" action="{{ route('trabalhadores.index') }}"
                class="flex items-center gap-2 w-full max-w-md">
                <input type="hidden" name="evento" value="{{ $evento?->idt_evento }}">
                <div>
                    <label for="search" class="sr-only">Buscar</label>
                    <input type="text" name="search" id="search" value="{{ $search }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Buscar por nome ou apelido" />
                </div>
                <div>
                    <label for="equipe" class="sr-only">Equipes</label>
                    <select id="equipe" name="equipe"
                        class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Equipe</option>
                        @foreach ($equipes as $equipe)
                            <option value="{{ $equipe->idt_equipe }}"
                                {{ old('idt_equipe', $idt_equipe) == $equipe->idt_equipe ? 'selected' : '' }}>
                                {{ $equipe->des_grupo }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    <x-heroicon-c-arrow-long-right class="w-5 h-5 mr-2" />
                    Buscar
                </button>

                @if ($search || $idt_equipe)
                    <a href="{{ route('trabalhadores.index', ['evento' => $evento]) }}"
                        class="inline-flex items-center px-4 py-2 bg-gray-300 hover:bg-gray-400">
                        <x-heroicon-o-x-circle class="w-5 h-5 mr-2" />
                        Limpar
                    </a>
                @endif
            </form>
            <div class="flex justify-end mt-4">
                <a href="{{ route('eventos.index') }}"
                    class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 dark:hover:bg-green-500 focus:ring-2 focus:ring-green-500 focus:outline-none"
                    aria-label="Voltar para a lista de eventos">
                    <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                    Eventos
                </a>
            </div>
        </div>
        <div class="overflow-x-auto mt-4">
            @if ($trabalhadores->isNotEmpty())
                <table
                    class="w-full text-left border border-gray-200 dark:border-zinc-700 rounded-md overflow-hidden text-sm">
                    <thead class="bg-gray-100 dark:bg-zinc-700">
                        <tr>
                            <th class="p-3 font-semibold text-gray-900 dark:text-gray-100">Foto</th>
                            <th class="p-3 font-semibold text-gray-900 dark:text-gray-100">Nome</th>
                            <th class="p-3 font-semibold text-gray-900 dark:text-gray-100">Apelido</th>
                            <th class="p-3 font-semibold text-gray-900 dark:text-gray-100">Telefone</th>
                            <th class="p-3 font-semibold text-gray-900 dark:text-gray-100">Equipe</th>
                            <th class="p-3 font-semibold text-gray-900 dark:text-gray-100">Coordenador(a)</th>
                            <th class="p-3 font-semibold text-gray-900 dark:text-gray-100">Primeira vez</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($trabalhadores as $trabalhador)
                            <tr class="border-t dark:border-zinc-600 dark:hover:bg-zinc-800">
                                <td class="p-3">
                                    @if ($trabalhador->pessoa->foto && $trabalhador->pessoa->foto->url_foto)
                                        <img src="{{ asset('storage/' . $trabalhador->pessoa->foto->url_foto) }}"
                                            alt="Foto de {{ $trabalhador->pessoa->nom_pessoa }}"
                                            class="w-10 h-10 rounded-full object-cover border border-gray-300 dark:border-zinc-600 shadow-sm">
                                    @else
                                        <div
                                            class="w-10 h-10 rounded-full bg-gray-200 dark:bg-zinc-700 flex items-center justify-center text-gray-400">
                                            <x-heroicon-o-user class="w-5 h-5" />
                                        </div>
                                    @endif
                                </td>

                                <td class="p-3 text-gray-900 dark:text-gray-200">{{ $trabalhador->pessoa->nom_pessoa }}
                                </td>
                                <td class="p-3 text-gray-900 dark:text-gray-200">
                                    {{ $trabalhador->pessoa->nom_apelido }}
                                </td>
                                <td class="p-3 text-gray-700 dark:text-gray-300">{{ $trabalhador->pessoa->tel_pessoa }}
                                </td>
                                <td class="p-3 text-gray-700 dark:text-gray-300">{{ $trabalhador->equipe->des_grupo }}
                                </td>
                                <td class="p-3 text-gray-700 dark:text-gray-300">
                                    @if ($trabalhador->ind_coordenador)
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded text-sm font-medium bg-purple-100 text-purple-800 dark:bg-purple-800 dark:text-purple-100">
                                            Sim
                                        </span>
                                    @else
                                        Não
                                    @endif
                                </td>
                                <td class="p-3 text-gray-700 dark:text-gray-300">
                                    @if ($trabalhador->ind_primeira_vez)
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded text-sm font-medium bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100">
                                            Sim
                                        </span>
                                    @else
                                        Não
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="col-span-full">
                    <div
                        class="flex flex-col items-center justify-center text-center p-10 bg-white dark:bg-zinc-800 rounded-xl shadow border border-dashed border-gray-300 dark:border-zinc-600">
                        <x-heroicon-o-briefcase class="w-12 h-12 text-gray-400 dark:text-gray-500 mb-4" />
                        <p class="text-lg font-medium text-gray-600 dark:text-gray-300">{{ __('messages.empty.trabalhador.title') }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            {{ __('messages.empty.trabalhador.description') }}
                        </p>
                    </div>
                </div>
            @endif
        </div>

        <div class="mt-6">
            {{ $trabalhadores->links() }}
        </div>
    </section>
</x-layouts.app>
