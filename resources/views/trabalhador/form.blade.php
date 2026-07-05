<x-layouts.app :title="'Trabalhador'">
    <section class="p-6 w-full max-w-5xl mx-auto">

        {{-- Flash messages --}}

        {{-- NAVEGAÇÃO --}}
        <flux:breadcrumbs class="mb-6">
            <flux:breadcrumbs.item href="/">Início</flux:breadcrumbs.item>
            <flux:breadcrumbs.item href="{{ route('eventos.index') }}">Eventos</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Quero trabalhar</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        {{-- CABEÇALHO --}}
        <flux:header class="mb-8">
            <div>
                <flux:heading size="xl" level="1">Inscrição de Voluntários</flux:heading>
                @if ($evento?->exists)
                <flux:subheading>
                    Evento: <span class="font-bold text-zinc-900 dark:text-white">{{ $evento->des_evento }}</span>
                </flux:subheading>
                @endif
            </div>

            <x-slot name="actions">
                <flux:button href="{{ route('eventos.index') }}" icon="arrow-left" variant="ghost">Voltar</flux:button>
            </x-slot>
        </flux:header>

        <flux:card>
            <form method="POST" action="{{ route('trabalhadores.store', $evento) }}" class="space-y-8"
                enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="idt_evento" value="{{ $evento->idt_evento }}">
                <flux:fieldset>
                    <flux:legend>Escolha das Equipes</flux:legend>
                    <flux:description>Marque até 3 equipes. Descreva como você pode ajudar a equipe.
                    </flux:description>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-6">
                        @foreach ($equipes as $equipe)
                        {{-- 1. x-data inicializa o estado local para cada card --}}
                        <flux:card class="p-4 flex flex-col justify-between space-y-4" 
                                x-data="{ selecionado: false, habilidade: '' }">
                            
                            <div class="flex items-start gap-3">
                                <flux:checkbox 
                                    name="equipes[{{ $equipe->idt_equipe }}][selecionado]" 
                                    value="1"
                                    id="equipe_{{ $equipe->idt_equipe }}" 
                                    label="{{ $equipe->des_grupo }}"
                                    class="font-semibold" 
                                    x-model="selecionado"
                                />
                            </div>

                            <flux:textarea 
                                name="equipes[{{ $equipe->idt_equipe }}][habilidade]"
                                placeholder="Como você pode ajudar esta equipe?" 
                                rows="2" 
                                size="sm" 
                                x-model="habilidade"
                                @input="if(habilidade.trim().length > 0) selecionado = true"
                            />
                        </flux:card>
                        @endforeach
                    </div>
                </flux:fieldset>

                {{-- AÇÕES --}}
               <div class="flex justify-end gap-3 pt-6 border-t border-zinc-100 dark:border-zinc-800">
                    <flux:button href="{{ route('eventos.index') }}" variant="ghost">Cancelar</flux:button>
                    
                    <flux:button type="submit" variant="primary" icon="check" loading>
                        Salvar Inscrição
                    </flux:button>
                </div>
            </form>
        </flux:card>
    </section>
</x-layouts.app>
