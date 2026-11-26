<x-layouts.public title="Candidatura para trabalhar">
    <section class="mx-auto w-full max-w-7xl space-y-4 sm:space-y-5" aria-labelledby="candidatura-titulo">
        <header class="flex flex-wrap items-start justify-between gap-4 rounded-xl border border-indigo-100 bg-indigo-50 p-4 sm:p-6 dark:border-indigo-900 dark:bg-indigo-950">
            <div class="min-w-0 space-y-2">
                <flux:heading id="candidatura-titulo" size="xl" level="1"
                    class="font-bold tracking-tight text-indigo-950 dark:text-indigo-100">Candidatura para trabalhar</flux:heading>
                <div class="flex flex-wrap items-center gap-2 text-sm font-semibold text-indigo-950 dark:text-indigo-100">
                    <span>{{ $evento->des_evento }}</span>
                    @if ($evento->movimento?->des_sigla)
                        <x-badge-movimento :sigla="$evento->movimento->des_sigla" />
                    @endif
                    <time datetime="{{ $evento->dat_inicio->toDateString() }}" class="font-normal">{{ $evento->getDataInicioFormatada() }}</time>
                </div>
            </div>
            <flux:button href="{{ route('trabalhadores.candidatura.eventos') }}" icon="arrow-left"
                variant="outline" size="sm">Trocar evento</flux:button>
        </header>

        @if (session('candidatura_enviada'))
            <flux:callout class="mb-6" icon="check-circle" variant="success">
                <flux:callout.text>{{ session('candidatura_enviada') }}</flux:callout.text>
            </flux:callout>
        @endif
        @if ($errors->any())
            <flux:callout id="erros-candidatura" class="mb-6" icon="exclamation-circle" variant="danger" tabindex="-1">
                <flux:callout.text>{{ $errors->first() }}</flux:callout.text>
            </flux:callout>
        @endif

        <form method="POST" action="{{ route('trabalhadores.candidatura.store', $evento) }}" class="space-y-4 sm:space-y-5"
            @if ($errors->any())
                x-data x-init="$nextTick(() => ($el.querySelector('[aria-invalid=true]') ?? document.getElementById('erros-candidatura')).focus())"
            @endif>
            @csrf
            <fieldset class="space-y-4 rounded-xl border border-zinc-200 bg-white p-4 sm:p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <legend class="sr-only">Seus dados</legend>
                <flux:heading level="2" size="lg" class="font-bold">Seus dados</flux:heading>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" x-data="{ tipo: @js(old('tipo_candidatura', '')) }">
                    <flux:field class="lg:col-span-2">
                        <flux:label>Nome completo <span class="ms-1 text-red-600 dark:text-red-400" aria-hidden="true">*</span></flux:label>
                        <flux:input name="nome" :value="old('nome')" autocomplete="name" required />
                    </flux:field>
                    <flux:input label="Apelido (opcional)" name="apelido" :value="old('apelido')" autocomplete="nickname" />
                    <flux:field>
                        <flux:label>E-mail <span class="ms-1 text-red-600 dark:text-red-400" aria-hidden="true">*</span></flux:label>
                        <flux:input name="email" :value="old('email')" type="email" autocomplete="email" required />
                    </flux:field>
                    <flux:field>
                        <flux:label>CPF <span class="ms-1 text-red-600 dark:text-red-400" aria-hidden="true">*</span></flux:label>
                        <flux:input name="cpf" :value="old('cpf')" inputmode="numeric" maxlength="14" placeholder="000.000.000-00"
                            :aria-describedby="$errors->has('cpf') ? 'candidatura-cpf-erro' : null" required />
                        @error('cpf')
                            <p id="candidatura-cpf-erro" class="text-sm text-red-700 dark:text-red-300">{{ $message }}</p>
                        @enderror
                    </flux:field>
                    <flux:field class="sm:col-span-2 lg:col-span-1">
                        <flux:label>Tipo de candidatura <span class="ms-1 text-red-600 dark:text-red-400" aria-hidden="true">*</span></flux:label>
                        <flux:select name="tipo_candidatura" required x-model="tipo" aria-controls="campos-conjuge"
                            :aria-invalid="$errors->has('tipo_candidatura') ? 'true' : null"
                            :aria-describedby="$errors->has('tipo_candidatura') ? 'tipo-candidatura-erro' : null">
                            <option value="">Selecione</option>
                            <option value="jovem" @selected(old('tipo_candidatura') === 'jovem')>Jovem</option>
                            <option value="casal" @selected(old('tipo_candidatura') === 'casal')>Casal</option>
                        </flux:select>
                        @error('tipo_candidatura')
                            <p id="tipo-candidatura-erro" class="text-sm text-red-700 dark:text-red-300">{{ $message }}</p>
                        @enderror
                    </flux:field>

                    <section id="campos-conjuge" x-show="tipo === 'casal'" x-cloak aria-labelledby="titulo-dados-conjuge"
                        class="col-span-full grid gap-4 border-t border-zinc-200 pt-4 sm:grid-cols-2 dark:border-zinc-700">
                        <flux:heading id="titulo-dados-conjuge" level="3" class="col-span-full">Dados do cônjuge</flux:heading>
                        @foreach ([
                            'nome_conjuge' => ['Nome completo', 'text', 255, true],
                            'apelido_conjuge' => ['Apelido (opcional)', 'text', 255, false],
                            'telefone_conjuge' => ['Telefone', 'tel', 32, true],
                            'email_conjuge' => ['E-mail', 'email', 255, true],
                        ] as $campo => [$rotulo, $tipoInput, $limite, $obrigatorio])
                            <flux:field>
                                <flux:label>{{ $rotulo }} @if ($obrigatorio)<span class="ms-1 text-red-600 dark:text-red-400" aria-hidden="true">*</span>@endif</flux:label>
                                <flux:input :name="$campo" :value="old($campo)" :type="$tipoInput" :maxlength="$limite"
                                    x-bind:required="tipo === 'casal' && {{ $obrigatorio ? 'true' : 'false' }}"
                                    x-bind:disabled="tipo !== 'casal'" :aria-invalid="$errors->has($campo) ? 'true' : null"
                                    :aria-describedby="$errors->has($campo) ? $campo.'-erro' : null" />
                                @error($campo)
                                    <p id="{{ $campo }}-erro" class="text-sm text-red-700 dark:text-red-300">{{ $message }}</p>
                                @enderror
                            </flux:field>
                        @endforeach
                    </section>
                </div>
            </fieldset>

            <fieldset class="space-y-3 rounded-xl border border-zinc-200 bg-zinc-50 p-4 sm:p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <legend class="sr-only">Experiência anterior (opcional)</legend>
                <flux:heading level="2" size="lg">Experiência anterior <span class="text-sm font-normal text-zinc-600 dark:text-zinc-300">(opcional)</span></flux:heading>
                <flux:text>Selecione as equipes que se aplicam. Se for sua primeira vez, deixe em branco.</flux:text>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    @foreach ($equipesAnteriores as $equipe)
                        <flux:checkbox name="equipes_anteriores[]" value="{{ $equipe->idt_equipe }}" :label="$equipe->des_grupo"
                            :checked="in_array($equipe->idt_equipe, (array) old('equipes_anteriores', []))" />
                    @endforeach
                </div>
            </fieldset>

            <fieldset class="space-y-3 rounded-xl border border-indigo-200 bg-white p-4 sm:p-5 dark:border-indigo-800 dark:bg-zinc-900">
                <legend class="sr-only">Equipes em que você gostaria de trabalhar</legend>
                <flux:heading level="2" size="lg" class="font-bold text-indigo-950 dark:text-indigo-100">Equipes de interesse</flux:heading>
                @if ($equipesDisponiveis->isNotEmpty())
                    <flux:text class="font-semibold text-zinc-900 dark:text-zinc-100">Selecione pelo menos uma equipe e informe o motivo de cada escolha.</flux:text>
                @endif
                <flux:text>A coordenação considera suas preferências na montagem. A seleção de uma equipe não garante vaga nela.</flux:text>
                <div class="grid items-start gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    @forelse ($equipesDisponiveis as $equipe)
                        <flux:card class="min-w-0 space-y-3 p-3" x-data="{ selecionada: {{ old('equipes.'.$equipe->idt_equipe.'.selecionado') ? 'true' : 'false' }} }"
                            x-bind:class="{ 'border-indigo-500 bg-indigo-50 dark:border-indigo-400 dark:bg-indigo-950/60': selecionada }">
                            <flux:checkbox name="equipes[{{ $equipe->idt_equipe }}][selecionado]" value="1"
                                :label="$equipe->des_grupo" x-model="selecionada"
                                aria-controls="motivo-equipe-{{ $equipe->idt_equipe }}" />
                            <div id="motivo-equipe-{{ $equipe->idt_equipe }}" x-show="selecionada" x-cloak>
                                <flux:textarea :label="'Motivo da escolha — '.$equipe->des_grupo"
                                    name="equipes[{{ $equipe->idt_equipe }}][habilidade]" rows="2"
                                    placeholder="Por que você quer trabalhar nesta equipe?"
                                    x-bind:required="selecionada" x-bind:disabled="!selecionada">{{ old('equipes.'.$equipe->idt_equipe.'.habilidade') }}</flux:textarea>
                            </div>
                        </flux:card>
                    @empty
                        <flux:text>Nenhuma equipe disponível para candidatura neste evento.</flux:text>
                    @endforelse
                </div>
            </fieldset>

            <div class="space-y-4 rounded-xl border border-zinc-200 bg-white p-4 sm:p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading level="2" size="lg" class="font-bold">Habilidades e observações</flux:heading>
                <div class="grid items-center gap-3 sm:grid-cols-2" x-data="{ tocaInstrumento: {{ old('toca_instrumento') ? 'true' : 'false' }} }">
                    <flux:checkbox name="toca_instrumento" value="1" label="Toca algum instrumento?"
                        x-model="tocaInstrumento" aria-controls="campo-instrumento" />
                    <div id="campo-instrumento" style="visibility: hidden"
                        x-bind:style="{ visibility: tocaInstrumento ? 'visible' : 'hidden' }">
                        <flux:field>
                            <flux:label>Qual instrumento? <span class="ms-1 text-red-600 dark:text-red-400" aria-hidden="true">*</span></flux:label>
                            <flux:input name="instrumento" :value="old('instrumento')" maxlength="120" disabled
                                x-bind:required="tocaInstrumento" x-bind:disabled="!tocaInstrumento" />
                        </flux:field>
                    </div>
                </div>
                <flux:checkbox name="canta" value="1" label="Canto" :checked="(bool) old('canta')" />
                <flux:textarea label="Observações (opcional)" name="observacoes" rows="4"
                    placeholder="Conte quais habilidades ou experiências podem ajudar na escolha da sua equipe.">{{ old('observacoes') }}</flux:textarea>
            </div>

            <div class="flex justify-end">
                <flux:button type="submit" variant="primary" class="w-full bg-indigo-700! text-white! hover:bg-indigo-800! sm:w-auto"
                    :disabled="$equipesDisponiveis->isEmpty()">Enviar candidatura</flux:button>
            </div>
        </form>
    </section>
</x-layouts.public>
