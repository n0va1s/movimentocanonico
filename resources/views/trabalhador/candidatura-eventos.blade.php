<x-layouts.public title="Selecione o evento">
    <section class="mx-auto w-full max-w-7xl space-y-4 sm:space-y-5" aria-labelledby="eventos-titulo">
        <header class="space-y-2 rounded-xl border border-indigo-100 bg-indigo-50 p-4 sm:p-6 dark:border-indigo-900 dark:bg-indigo-950">
            <flux:heading id="eventos-titulo" size="xl" level="1"
                class="font-bold tracking-tight text-indigo-900 dark:text-indigo-100">Selecione o evento</flux:heading>
            <flux:subheading>Escolha o evento em que você deseja se candidatar para trabalhar.</flux:subheading>
        </header>

        <ul class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($eventos as $evento)
                <li>
                    <flux:card class="flex h-full flex-col gap-4 bg-white shadow-sm motion-safe:transition-shadow hover:shadow-md dark:bg-zinc-900">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <flux:heading level="2" size="lg" class="break-words font-bold">{{ $evento->des_evento }}</flux:heading>
                            @if ($evento->movimento?->des_sigla)
                                <x-badge-movimento :sigla="$evento->movimento->des_sigla" />
                            @endif
                        </div>
                        <div class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                            <flux:icon.calendar class="size-4 shrink-0 text-blue-500" aria-hidden="true" />
                            <time datetime="{{ $evento->dat_inicio->toDateString() }}">{{ $evento->dat_inicio->format('d/m/Y') }}</time>
                        </div>
                        <flux:button href="{{ route('trabalhadores.candidatura', $evento) }}"
                            :aria-label="'Selecionar evento '.$evento->des_evento"
                            variant="primary" icon:trailing="chevron-right"
                            class="mt-auto w-full border-none bg-gradient-to-r from-indigo-700 to-purple-700 text-white shadow-md hover:from-indigo-800 hover:to-purple-800 dark:text-white">
                            Selecionar evento
                        </flux:button>
                    </flux:card>
                </li>
            @empty
                <li><flux:text>Nenhum evento ativo disponível no momento.</flux:text></li>
            @endforelse
        </ul>
    </section>
</x-layouts.public>
