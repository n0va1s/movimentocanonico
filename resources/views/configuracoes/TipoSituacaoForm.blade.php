<x-layouts.app>
    <h2 id="titulo-pagina" class="text-2xl font-bold mb-6">Cadastrar Tipo de Situação</h2>

    <form method="POST" action="{{ $situacao->exists ? route('situacao.update', $situacao) : route('situacao.store') }}"
        class="space-y-6" novalidate>
        @csrf
        @if ($situacao->exists)
            @method('PUT')
        @endif

        {{-- Nome da Situação --}}
        <div>
            <label for="nome" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Nome da Situação <span class="text-red-600">*</span>
            </label>
            <input type="text" id="des_situacao" name="des_situacao" required maxlength="10"
                value="{{ old('des_situacao', $situacao->des_situacao) }}" aria-required="true"
                aria-describedby="des_situacao-help"
                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="Ex: Ativo, Inativo">
            <p id="des_situacao-help" class="sr-only">Máximo 10 caracteres.</p>
            @error('des_situacao')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>


        {{-- Botões --}}
        <div class="flex items-center gap-4">
            <button type="submit"
                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                aria-label="Salvar tipo de situação">
                <flux:icon.arrow-right class="w-5 h-5 mr-2" />
                Salvar
            </button>

            <a href="{{ route('situacao.index') }}"
                class="inline-flex items-center px-4 py-2 bg-gray-300 hover:bg-gray-400 rounded-md focus:ring-2 focus:ring-gray-500 focus:outline-none text-gray-800"
                aria-label="Voltar para a lista de tipos de situação">
                <flux:icon.x-mark class="w-5 h-5 mr-2" />
                Cancelar
            </a>
        </div>
    </form>
</x-layouts.app>
