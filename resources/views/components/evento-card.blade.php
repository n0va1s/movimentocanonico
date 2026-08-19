@props(['label', 'count', 'href' => null, 'icon', 'color' => 'blue'])

@php
    $colors = [
        'blue' =>
            'bg-blue-50 text-blue-700 border-blue-100 dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-800/30',
        'green' =>
            'bg-green-50 text-green-700 border-green-100 dark:bg-green-900/20 dark:text-green-400 dark:border-green-800/30',
        'orange' =>
            'bg-orange-50 text-orange-700 border-orange-100 dark:bg-orange-900/20 dark:text-orange-400 dark:border-orange-800/30',
        'zinc' =>
            'bg-gray-50 text-gray-700 border-gray-100 dark:bg-zinc-700/50 dark:text-zinc-300 dark:border-zinc-600',
    ];

    $colorClass = $colors[$color] ?? $colors['blue'];

    // Define se o elemento deve se comportar como um link ou uma div estática
    $tag = $href ? 'a' : 'div';
    $interactiveClasses = $href ? 'hover:opacity-80 transition-all group cursor-pointer' : 'cursor-default';
@endphp

<{{ $tag }} {{ $href ? 'href=' . $href : '' }}
    class="flex flex-wrap items-center p-2 rounded-lg border {{ $colorClass }} {{ $interactiveClasses }} min-w-0 shadow-sm relative">

    <div class="flex items-center w-full mb-1">
        <x-dynamic-component :component="$icon" class="w-4 h-4 flex-shrink-0" />

        <p class="text-[9px] font-bold uppercase tracking-tighter opacity-70 truncate ml-2">
            {{ $label }}
        </p>
    </div>

    <div class="w-full text-center">
        <p class="text-lg font-black leading-none tabular-nums">
            {{ $count }}
        </p>
    </div>

    {{-- A setinha só aparece se houver um link --}}
    @if ($href)
        <div class="absolute right-1 top-1 opacity-0 group-hover:opacity-100 transition-opacity">
            <flux:icon.chevron-right class="w-2 h-2" />
        </div>
    @endif
    </{{ $tag }}>
