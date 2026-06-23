<x-layouts.app>
    <h2 id="titulo-pagina" class="text-2xl font-bold mb-6">{{ $equipe->exists ? 'Editar Tipo de Equipe' : 'Cadastrar Tipo de Equipe' }}</h2>

    <form method="POST" action="{{ $equipe->exists ? route('equipe.update', $equipe) : route('equipe.store') }}"
        class="space-y-6">
        @csrf
        @if ($equipe->exists)
            @method('PUT')
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="idt_movimento" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Movimento <span class="text-red-600">*</span>
                </label>
                <select id="idt_movimento" name="idt_movimento" required aria-required="true"
                    class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-zinc-800">
                    <option value="">Selecione um Movimento</option>
                    @foreach ($movimentos as $movimento)
                        <option value="{{ $movimento->idt_movimento }}" {{ old('idt_movimento', $equipe->idt_movimento) == $movimento->idt_movimento ? 'selected' : '' }}>{{ $movimento->nom_movimento }} ({{ $movimento->des_sigla }})</option>
                    @endforeach
                </select>
                @error('idt_movimento')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="des_grupo" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Descrição do Equipe <span class="text-red-600">*</span>
                </label>
                <input type="text" id="des_grupo" name="des_grupo" required
                    value="{{ old('des_grupo', $equipe->des_grupo) }}" aria-required="true"
                    class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Ex: Pai, Mãe, Padrinho...">
                @error('des_grupo')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="flex items-center gap-4">
            <button type="submit"
                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                aria-label="Salvar tipo de equipe">
                <x-heroicon-c-arrow-long-right class="w-5 h-5 mr-2" />
                Salvar
            </button>

            <a href="{{ route('equipe.index') }}"
                class="inline-flex items-center px-4 py-2 bg-gray-300 hover:bg-gray-400 rounded-md focus:ring-2 focus:ring-gray-500 focus:outline-none text-gray-800"
                aria-label="Voltar para a lista de equipes">
                <x-heroicon-o-x-mark class="w-5 h-5 mr-2" />
                Cancelar
            </a>
        </div>
    </form>
</x-layouts.app>
