@props([
    'title' => 'Nenhum registro encontrado',
    'description' => 'Não encontramos resultados para a sua busca ou filtro atual.',
    'icon' => 'document-magnifying-glass',
    'buttonText' => null,
    'buttonHref' => null,
    'buttonIcon' => 'plus',
])

@php
    $iconName = str_replace(['heroicon-o-', 'heroicon-s-', 'heroicon-c-'], '', $icon);
    $btnIconName = str_replace(['heroicon-o-', 'heroicon-s-', 'heroicon-c-'], '', $buttonIcon);
@endphp

<div
    {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center text-center p-10 bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-dashed border-gray-300 dark:border-zinc-700']) }}>

    {{-- Ícone Principal --}}
    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-50 dark:bg-zinc-900 mb-4">
        <flux:icon :name="$iconName" class="w-10 h-10 text-gray-400 dark:text-zinc-500" />
    </div>

    {{-- Textos --}}
    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 leading-tight">
        {{ $title }}
    </h3>
    <p class="mt-1 text-sm text-gray-500 dark:text-zinc-400 max-w-xs mx-auto">
        {{ $description }}
    </p>

    {{-- Botão de Ação (Opcional) --}}
    @if ($buttonText && $buttonHref)
        <div class="mt-6">
            <a href="{{ $buttonHref }}"
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                <flux:icon :name="$btnIconName" class="w-4 h-4 mr-2" />
                {{ $buttonText }}
            </a>
        </div>
    @endif
</div>
