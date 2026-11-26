<div class="{{ $porEquipe ? 'border-t border-zinc-200 dark:border-zinc-700' : '' }}">
    <p x-show="!{{ $lista }}?.itens.length"
        class="px-4 py-4 text-sm text-zinc-700 dark:text-zinc-300">Nenhum trabalhador encontrado para esta visualização.</p>
    <ul @if ($porEquipe) x-bind:aria-labelledby="'resumo-equipe-' + grupo.id" @else aria-labelledby="trabalhadores-listados" @endif
        class="{{ $porEquipe ? 'divide-y divide-zinc-200 dark:divide-zinc-700' : 'grid gap-3 md:grid-cols-2 xl:grid-cols-3' }}">
        <template x-for="trabalhador in {{ $lista }}?.itens ?? []" :key="trabalhador.id">
            <li @if ($porEquipe) x-bind:class="{ 'bg-indigo-50/50 dark:bg-indigo-950/50': trabalhador.coordenador }" @endif
                class="min-w-0 {{ $porEquipe ? 'flex flex-wrap items-center justify-between gap-3 px-4 py-3' : 'rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900' }}">
                <div class="{{ $porEquipe ? 'min-w-0 space-y-1' : 'space-y-3 p-4' }}">
                    <div class="flex items-start gap-3">
                        @unless ($porEquipe)
                            <span aria-hidden="true" x-text="trabalhador.iniciais"
                                class="grid size-10 shrink-0 place-items-center rounded-full bg-indigo-100 font-semibold text-indigo-800 dark:bg-indigo-900 dark:text-indigo-100"></span>
                        @endunless
                        <div class="min-w-0">
                            <p class="break-words font-semibold" x-text="trabalhador.nome"></p>
                            <p x-show="trabalhador.apelido || trabalhador.telefone" class="break-words text-sm text-zinc-600 dark:text-zinc-300"
                                x-text="[trabalhador.apelido, trabalhador.telefone].filter(Boolean).join(' · ')"></p>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        @unless ($porEquipe)
                            <flux:badge color="indigo" size="sm"><span x-text="trabalhador.equipe"></span></flux:badge>
                        @endunless
                        <flux:badge color="indigo" size="sm" x-show="trabalhador.coordenador">Coordenador</flux:badge>
                        <flux:badge size="sm" x-show="trabalhador.tipoCandidatura"><span x-text="trabalhador.tipoCandidatura"></span></flux:badge>
                        <flux:badge color="green" size="sm" x-show="trabalhador.avaliado">Avaliado</flux:badge>
                        @if ($porEquipe)
                            <flux:badge size="sm" x-show="!trabalhador.avaliado">Pendente</flux:badge>
                        @endif
                        <flux:badge size="sm" x-show="trabalhador.primeiraVez">Primeira vez</flux:badge>
                        <flux:badge color="amber" size="sm" x-show="trabalhador.menor">Menor de idade</flux:badge>
                    </div>
                </div>
                <div x-show="!trabalhador.avaliado"
                    class="flex flex-wrap items-center gap-2 {{ $porEquipe ? '' : 'border-t border-zinc-200 p-3 dark:border-zinc-700' }}">
                    <flux:button type="button" size="sm" variant="primary" class="bg-indigo-700! text-white! hover:bg-indigo-800!"
                        x-on:click="$wire.abrirAvaliacao(trabalhador.id)" x-bind:aria-label="'Avaliar ' + trabalhador.nome">Avaliar</flux:button>
                    <flux:button type="button" size="sm" variant="outline" x-on:click="$wire.abrirAlterarEquipe(trabalhador.id)"
                        x-bind:aria-label="'Alterar alocação de ' + trabalhador.nome">Alterar alocação</flux:button>
                    <flux:button type="button" size="sm" variant="ghost" icon="trash" x-on:click="remover(trabalhador.id)"
                        x-bind:aria-label="'Remover ' + trabalhador.nome + ' da escalação'">Remover</flux:button>
                </div>
            </li>
        </template>
    </ul>
    <nav x-show="{{ $lista }} && paginas({{ $lista }}) > 1"
        aria-label="{{ $porEquipe ? 'Páginas da equipe' : 'Páginas de trabalhadores' }}"
        class="mt-2 flex flex-wrap items-center justify-between gap-2 p-3">
        <flux:button type="button" size="sm" x-bind:disabled="({{ $lista }}?.pagina ?? 1) <= 1"
            x-on:click="$wire.setPage({{ $lista }}.pagina - 1, '{{ $nomePagina }}')" wire:loading.attr="disabled" wire:target="setPage">Anterior</flux:button>
        <span class="text-sm tabular-nums" x-text="{{ $lista }} ? 'Página ' + {{ $lista }}.pagina + ' de ' + paginas({{ $lista }}) : ''"></span>
        <flux:button type="button" size="sm" x-bind:disabled="!{{ $lista }} || {{ $lista }}.pagina >= paginas({{ $lista }})"
            x-on:click="$wire.setPage({{ $lista }}.pagina + 1, '{{ $nomePagina }}')" wire:loading.attr="disabled" wire:target="setPage">Próxima</flux:button>
    </nav>
</div>
