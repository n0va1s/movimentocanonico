<x-layouts.app :title="'Contato'">
    <section class="p-6 w-full max-w-[80vw] ml-auto">
        <div>
            <x-session-alert />
        </div>
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Contatos</h1>
            <p class="text-gray-700 mt-1 dark:text-gray-400">Após responder exclua a mensagem</p>
        </div>

        <div class="mb-4">
            {{-- Removido o max-w-md e ajustado o flex para lidar com o preenchimento total --}}
            <form method="GET" action="{{ route('contatos.index') }}" class="flex items-center gap-2 w-full">
                <label for="search" class="sr-only">Buscar</label>

                {{-- O input com w-full aqui fará com que ele estique para preencher o espaço restante --}}
                <input type="text" name="search" id="search" value="{{ $search }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Buscar por nome ou número" />

                <button type="submit"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:outline-none whitespace-nowrap">
                    <x-heroicon-s-magnifying-glass class="w-5 h-5 mr-2" />
                    Filtrar
                </button>

                @if ($search)
                    <a href="{{ route('contatos.index') }}"
                        class="inline-flex items-center px-4 py-2 bg-gray-300 hover:bg-gray-400 focus:ring-2 focus:ring-gray-500 focus:outline-none whitespace-nowrap">
                        <x-heroicon-o-x-circle class="w-5 h-5 mr-2" />
                        Limpar
                    </a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto mt-4">
            <table
                class="w-full text-left border border-gray-200 dark:border-zinc-700 rounded-md overflow-hidden text-sm">

                <caption class="sr-only">Lista de Contatos</caption>
                <thead class="bg-gray-100">
                    <tr>

                        <th class="p-3 font-semibold dark:text-gray-800">Nome</th>
                        <th class="p-3 font-semibold dark:text-gray-800">E-mail</th>
                        <th class="p-3 font-semibold dark:text-gray-800">Telefone</th>
                        <th class="p-3 font-semibold dark:text-gray-800">Mensagem</th>
                        <th class="p-3 font-semibold dark:text-gray-800">Movimento</th>
                        <th class="p-3 font-semibold dark:text-gray-800 text-center w-36">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($contatos as $contato)
                        <tr class="border-t dark:hover:bg-gray-500">
                            <td class="p-3 text-gray-900 dark:text-gray-300">{{ $contato->nom_contato }}</td>
                            <td class="p-3 text-gray-700 dark:text-gray-300">{{ $contato->eml_contato }}</td>
                            <td class="p-3 text-gray-700 dark:text-gray-300">{{ $contato->tel_contato }}</td>
                            <td class="p-3 text-gray-700 max-w-xs dark:text-gray-300">{{ $contato->txt_mensagem }}</td>
                            <td class="p-3">
                                @php
                                    $sig_movimento = $contato->movimento->des_sigla;
                                @endphp

                                @if ($sig_movimento === 'ECC')
                                    <span
                                        class="inline-flex items-center rounded-full bg-lime-400 px-2 py-0.5 text-xs font-medium text-green-700">
                                        {{ $sig_movimento }}
                                    </span>
                                @elseif ($sig_movimento === 'Segue-Me')
                                    <span
                                        class="inline-flex items-center rounded-full bg-orange-300 px-2 py-0.5 text-xs font-medium text-red-700">
                                        {{ $sig_movimento }}
                                    </span>
                                @else
                                    <span
                                        class="inline-flex items-center rounded-full bg-sky-400 px-2 py-0.5 text-xs font-medium text-blue-700">
                                        {{ $sig_movimento }}
                                    </span>
                                @endif
                            </td>

                            {{-- Coluna Ações --}}
                            <td class="p-3 align-middle gap-2">
                                <form method="POST" action="{{ route('contatos.destroy', $contato->idt_contato) }}"
                                    onsubmit="return confirm('Tem certeza que deseja excluir este contato?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="inline-flex items-center gap-1 text-red-600 items-center hover:text-red-800 dark:hover:text-red-400 focus:outline-none focus:ring-2 focus:ring-red-500 px-2 py-1 rounded-md cursor-pointer">
                                        <x-heroicon-o-trash class="w-5 h-5" />
                                        <span class="sr-only sm:not-sr-only">Excluir</span>
                                    </button>
                                </form>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-4 text-center text-gray-500">Nenhum evento encontrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $contatos->links() }}
        </div>
    </section>
</x-layouts.app>
