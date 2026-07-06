<x-layouts.app :title="'Perfil Usuario'">
    <section class="p-6 w-full max-w-[80vw] ml-auto">
        <div class="mb-6">
            <flux:heading size="xl" class="text-indigo-900 dark:text-indigo-100 font-bold tracking-tight mb-1">Perfil</flux:heading>
            <p class="text-gray-600 dark:text-gray-300 text-sm">
                Defina as permissões de acesso do usuário selecionando o perfil desejado para cada pessoa na lista
                abaixo.
            </p>
        </div>
        <div class="mb-6">
            <div>
            </div>
            <div>
                <x-botao-navegar href="{{ route('configuracoes.index') }}" aria-label="Voltar para Configurações">
                    <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                    Voltar
                </x-botao-navegar>
            </div>
        </div>

        {{-- Filtros  --}}
        <nav class="bg-white dark:bg-zinc-800 p-5 rounded-xl border border-gray-200 dark:border-zinc-700 shadow-sm mb-6">
            <form method="GET" action="{{ route('role.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center">
                <div class="md:col-span-4">
                    <flux:input name="nome" value="{{ request('nome') }}" icon="magnifying-glass" placeholder="Buscar por nome..." />
                </div>

                <div class="md:col-span-3">
                    <flux:select name="perfil" placeholder="Todos os Perfis">
                        <flux:select.option value="all" :selected="request('perfil') == 'all' || !request('perfil')">Todos os Perfis</flux:select.option>
                        <flux:select.option value="admin" :selected="request('perfil') == 'admin'">Admin</flux:select.option>
                        <flux:select.option value="coord" :selected="request('perfil') == 'coord'">Coordenador</flux:select.option>
                        <flux:select.option value="espec" :selected="request('perfil') == 'espec'">Especialista</flux:select.option>
                        <flux:select.option value="sales" :selected="request('perfil') == 'sales'">Mercadinho</flux:select.option>
                        <flux:select.option value="user" :selected="request('perfil') == 'user'">Usuário</flux:select.option>
                    </flux:select>
                </div>

                <div class="md:col-span-3">
                    <flux:select name="movimento" placeholder="Todos os Movimentos">
                        <flux:select.option value="all" :selected="request('movimento') == 'all' || !request('movimento')">Todos os Movimentos</flux:select.option>
                        <flux:select.option value="none" :selected="request('movimento') == 'none'">Sem Movimento</flux:select.option>
                        @foreach ($movements as $mov)
                            <flux:select.option value="{{ $mov->idt_movimento }}" :selected="request('movimento') == $mov->idt_movimento">
                                {{ $mov->des_sigla }} ({{ $mov->nom_movimento }})
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="md:col-span-2 flex gap-2">
                    <flux:button type="submit" variant="filled" color="blue" class="flex-1">Filtrar</flux:button>
                    @if (request('nome') || (request('perfil') && request('perfil') !== 'all') || (request('movimento') && request('movimento') !== 'all'))
                        <flux:button href="{{ route('role.index') }}" icon="x-mark" variant="ghost" />
                    @endif
                </div>
            </form>
        </nav>

        <form method="POST" action="{{ route('role.change') }}">
            @csrf
            <table
                class="w-full text-left border border-gray-200 dark:border-zinc-700 rounded-md overflow-hidden text-sm">
                <thead class="bg-gray-100 dark:bg-zinc-700">
                    <tr>
                        <th class="p-3 font-semibold text-gray-900 dark:text-gray-100">Nome</th>
                        <th class="p-3 font-semibold text-gray-900 dark:text-gray-100">Email</th>
                        <th class="p-3 font-semibold text-gray-900 dark:text-gray-100">Perfil</th>
                        <th class="p-3 font-semibold text-gray-900 dark:text-gray-100">Movimento</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($perfis as $pessoa)
                        <tr>
                            <td class="p-3 text-gray-700 dark:text-gray-300">{{ $pessoa->name ?? '-' }}</td>
                            <td class="p-3 text-gray-700 dark:text-gray-300">{{ $pessoa->email ?? '-' }}</td>
                            <td>
                                @php
                                    $roleLabels = [
                                        'user' => 'Usuário',
                                        'admin' => 'Administrador',
                                        'coord' => 'Coordenador',
                                        'espec' => 'Especialista',
                                        'sales' => 'Mercadinho'
                                    ];
                                @endphp
                                <select name='role[{{ $pessoa->id ?? '' }}]'
                                    class="w-full px-2 py-1 rounded-md border border-gray-300 dark:border-zinc-600 dark:bg-zinc-800 text-gray-900 dark:text-gray-100">
                                    @foreach (['user', 'admin', 'coord', 'espec', 'sales'] as $role)
                                        <option value="{{ $role }}" @selected(strtolower($pessoa->role ?? '') == $role)>
                                            {{ $roleLabels[$role] }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <select name='movimento[{{ $pessoa->id ?? '' }}]'
                                    class="w-full px-2 py-1 rounded-md border border-gray-300 dark:border-zinc-600 dark:bg-zinc-800 text-gray-900 dark:text-gray-100">
                                    <option value="">Nenhum</option>
                                    @foreach ($movements as $mov)
                                        <option value="{{ $mov->idt_movimento }}" @selected(($pessoa->idt_movimento ?? '') == $mov->idt_movimento)>
                                            {{ $mov->des_sigla }} ({{ $mov->nom_movimento }})
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="flex gap-3 justify-end mt-4">
                <button type="submit" x-bind:disabled="bloqueado"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Salvar
                </button>
            </div>

            <div class="mt-6">
                {{ $perfis->links() }}
            </div>
    </section>
</x-layouts.app>
