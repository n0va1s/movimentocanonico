<x-layouts.app :title="'Ficha do ECC'">
    <section class="p-6 w-full max-w-[80vw] ml-auto">

        {{-- Alerta de sucesso/erro --}}
        <div>
            <x-session-alert />
        </div>

        {{-- Título e ações principais --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Gerenciar Fichas do ECC</h1>
                @if ($evento?->exists)
                    <p class="text-sm sm:text-base text-gray-600 dark:text-gray-300 mt-1">
                        Evento: <strong>{{ $evento->des_evento }}</strong>
                    </p>
                @endif
            </div>

            {{-- Botões --}}
            <div class="flex items-center gap-2">
                <a href="{{ route('ecc.create') }}"
                    class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                    <x-heroicon-s-plus class="w-5 h-5 mr-2" />
                    Nova Ficha
                </a>

                <a href="{{ route('eventos.index') }}"
                    class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 dark:hover:bg-green-500 focus:ring-2 focus:ring-green-500 focus:outline-none">
                    <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                    Eventos
                </a>
            </div>
        </div>

        {{-- Filtros de Busca --}}
        <div class="mb-6">
            <form method="GET" action="{{ route('ecc.index') }}" class="flex flex-wrap items-center gap-2 w-full">
                <input type="text" name="search" id="search" value="{{ $search }}"
                    class="px-3 py-2 border border-gray-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-gray-900 dark:text-gray-100 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm w-full sm:w-64"
                    placeholder="Buscar por nome ou apelido" />

                {{-- Filtro por Evento --}}
                <select name="evento" onchange="this.form.submit()"
                    class="px-3 py-2 border border-gray-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-gray-900 dark:text-gray-100 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm w-full sm:w-auto">
                    <option value="">Todos os Eventos</option>
                    @foreach ($eventos as $e)
                        <option value="{{ $e->idt_evento }}" {{ request('evento') == $e->idt_evento ? 'selected' : '' }}>
                            {{ $e->des_evento }}
                        </option>
                    @endforeach
                </select>

                {{-- Filtro por Situação --}}
                <select name="situacao" onchange="this.form.submit()"
                    class="px-3 py-2 border border-gray-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-gray-900 dark:text-gray-100 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm w-full sm:w-auto">
                    <option value="">Todas as Situações</option>
                    @foreach (\App\Enums\TipoSituacao::cases() as $s)
                        <option value="{{ $s->value }}" {{ request('situacao') == $s->value ? 'selected' : '' }}>
                            {{ $s->label() }}
                        </option>
                    @endforeach
                </select>

                <button type="submit"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm w-full sm:w-auto justify-center">
                    <x-heroicon-c-arrow-long-right class="w-4 h-4 mr-2" />
                    Buscar
                </button>

                @if ($search || request('evento') || request('situacao'))
                    <a href="{{ route('ecc.index') }}"
                        class="inline-flex items-center px-4 py-2 bg-gray-300 hover:bg-gray-400 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-gray-700 dark:text-gray-200 rounded-md text-sm w-full sm:w-auto justify-center">
                        <x-heroicon-o-x-circle class="w-4 h-4 mr-2" />
                        Limpar
                    </a>
                @endif
            </form>
        </div>

        {{-- Lista ou vazio --}}
        <div class="overflow-x-auto mt-4">
            @if ($fichas->isNotEmpty())
                <table class="w-full text-left border border-gray-200 rounded-md overflow-hidden text-sm">
                    <thead class="bg-gray-100 dark:bg-zinc-700 text-gray-800 dark:text-gray-200">
                        <tr>
                            <th class="p-3 font-semibold">Nome</th>
                            <th class="p-3 font-semibold">Apelido</th>
                            <th class="p-3 font-semibold">Nascimento</th>
                            <th class="p-3 font-semibold">Evento</th>
                            <th class="p-3 font-semibold">Situação</th>
                            <th class="p-3 font-semibold text-center w-24">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($fichas as $ficha)
                            <tr class="border-t hover:bg-gray-100 dark:hover:bg-gray-700">
                                <td class="p-3 text-gray-900 dark:text-gray-100">{{ $ficha->nom_candidato }}</td>
                                <td class="p-3 text-gray-700 dark:text-gray-100">{{ $ficha->nom_apelido }}</td>
                                <td class="p-3 text-gray-700 dark:text-gray-100">
                                    {{ \Carbon\Carbon::parse($ficha->dat_nascimento)->format('d/m/Y') }}
                                </td>
                                <td class="p-3 text-gray-700 dark:text-gray-100">
                                    {{ $ficha->evento->des_evento ?? '—' }}
                                </td>
                                <td class="p-3 dark:text-gray-100">
                                    @php
                                        $situacao = $ficha->tip_situacao ?? \App\Enums\TipoSituacao::NOVA;
                                        $style = $situacao->badge();
                                    @endphp
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $style['light'] }} border">
                                        {{ $situacao->label() }}
                                    </span>
                                </td>
                                <td class="p-3 flex justify-end items-center gap-2">
                                    <a href="{{ route('ecc.edit', $ficha) }}"
                                        class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-800 px-2 py-1 rounded-md">
                                        <x-heroicon-o-pencil-square class="w-5 h-5" />
                                        <span class="sr-only sm:not-sr-only">Editar</span>
                                    </a>
                                    <form method="POST" action="{{ route('ecc.destroy', $ficha) }}"
                                        onsubmit="return confirm('Tem certeza que deseja excluir esta ficha?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="inline-flex items-center gap-1 text-red-600 hover:text-red-800 px-2 py-1 rounded-md">
                                            <x-heroicon-o-trash class="w-5 h-5" />
                                            <span class="sr-only sm:not-sr-only">Excluir</span>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                {{-- Estado vazio --}}
                <div class="col-span-full">
                    <div
                        class="flex flex-col items-center justify-center text-center p-10 bg-white dark:bg-zinc-800 rounded-xl shadow border border-dashed border-gray-300 dark:border-zinc-600">
                        <x-heroicon-o-document-text class="w-12 h-12 text-gray-400 dark:text-gray-500 mb-4" />
                        <p class="text-lg font-medium text-gray-600 dark:text-gray-300">Nenhuma ficha do ECC encontrada
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Quando houver fichas cadastradas, elas aparecerão aqui.
                        </p>
                    </div>
                </div>
            @endif
        </div>
        {{-- Paginação --}}
        <div class="mt-6">
            {{ $fichas->links() }}
        </div>
    </section>
</x-layouts.app>
