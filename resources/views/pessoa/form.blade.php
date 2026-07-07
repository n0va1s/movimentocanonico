<x-layouts.app :title="'Pessoa'">
    <section class="p-6 w-full max-w-[80vw] ml-auto">
        <div class="mb-6">
            <flux:heading size="xl" class="text-indigo-900 dark:text-indigo-100 font-bold tracking-tight mb-1">
                {{ $pessoa->exists ? 'Atualizar Seus Dados' : 'Se Cadastrar' }}</flux:heading>
            <p class="text-gray-700 mt-1 dark:text-gray-400">
                {{ $pessoa->exists ? 'Esses dados serão utilizados no próximo encontro' : 'Esses dados serão utilizados no próximo encontro' }}
            </p>
            @if (!$pessoa->exists)
                <p class="text-sm text-yellow-600 dark:text-yellow-400 mt-2 flex items-center gap-1">
                    <x-heroicon-o-information-circle class="w-4 h-4" />
                    Não é possível cadastrar pessoas que estão com fichas em andamento.
                </p>
            @endif
        </div>

        <div class="mb-6 bg-white dark:bg-zinc-800 rounded-md shadow p-6">

            <form method="POST"
                action="{{ $pessoa->exists ? route('pessoas.update', $pessoa) : route('pessoas.store') }}"
                enctype="multipart/form-data" class="space-y-6"
                x-data="{
                    buscarPorCpf() {
                        let cpf = document.getElementById('num_cpf_pessoa').value.replace(/\D/g, '');
                        if (cpf.length === 11) {
                            fetch(`/pessoas/${cpf}/busca/`)
                                .then(response => {
                                    if (response.ok) return response.json();
                                    throw new Error('Not found');
                                })
                                .then(data => {
                                    document.getElementById('nom_pessoa').value = data.nom_pessoa || '';
                                    document.getElementById('nom_apelido').value = data.nom_apelido || '';
                                    if (data.dat_nascimento) document.getElementById('dat_nascimento').value = data.dat_nascimento.split('T')[0];
                                    document.getElementById('tel_pessoa').value = data.tel_pessoa || '';
                                    document.getElementById('eml_pessoa').value = data.eml_pessoa || '';
                                    if (document.getElementById('des_endereco')) document.getElementById('des_endereco').value = data.des_endereco || '';
                                    if (data.tam_camiseta && document.getElementById('tam_camiseta')) document.getElementById('tam_camiseta').value = data.tam_camiseta;
                                    if (data.tip_genero && document.getElementById('tip_genero')) document.getElementById('tip_genero').value = data.tip_genero;
                                })
                                .catch(() => {});
                        }
                    }
                }">
                @csrf
                @if ($pessoa->exists)
                    @method('PUT')
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                        
                    {{-- Foto --}}
                    <div class="flex flex-col items-center gap-3" x-data="{ photoPreview: '{{ $pessoa->foto?->med_foto ? asset('storage/' . $pessoa->foto->med_foto) : '' }}' }">
                        <div
                            class="w-28 h-28 rounded-full bg-gray-100 dark:bg-zinc-700 border-2 border-gray-300 dark:border-zinc-600 flex items-center justify-center overflow-hidden">
                            <template x-if="photoPreview">
                                <img :src="photoPreview" alt="Foto do participante"
                                    class="w-full h-full object-cover"
                                    @@error="photoPreview = ''" />
                            </template>
                            <template x-if="!photoPreview">
                                <x-heroicon-o-user class="w-14 h-14 text-gray-400 dark:text-gray-500"
                                    aria-hidden="true" />
                            </template>
                        </div>
                        <div>
                            <label for="med_foto"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 text-center">
                                Foto
                            </label>
                            <input type="file" name="med_foto" id="med_foto" accept="image/*"
                                @change="
                                    const file = $event.target.files[0];
                                    if (file) {
                                        photoPreview = URL.createObjectURL(file);
                                    } else {
                                        photoPreview = '{{ $pessoa->foto?->med_foto ? asset('storage/' . $pessoa->foto->med_foto) : '' }}';
                                    }
                                "
                                class="block text-sm text-gray-600 dark:text-gray-400 file:mr-4 file:py-1.5 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-900/30 dark:file:text-blue-300" />
                            @error('med_foto')
                                <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- CPF --}}
                    <div>
                        <label for="num_cpf_pessoa"
                            class="block font-medium text-gray-700 dark:text-gray-300 mb-1 text-sm sm:text-base">
                            CPF <span class="text-red-600" aria-hidden="true">*</span><span class="sr-only">(obrigatório)</span>
                        </label>
                        <input type="text" name="num_cpf_pessoa" id="num_cpf_pessoa"
                            required maxlength="14" autocomplete="off"
                            value="{{ old('num_cpf_pessoa', $pessoa->num_cpf_pessoa) }}"
                            @blur="buscarPorCpf()"
                            placeholder="000.000.000-00" aria-required="true"
                            class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('num_cpf_pessoa') border-red-500 @enderror" />
                        @error('num_cpf_pessoa')
                            <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="nom_pessoa" class="block font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Nome <span class="text-red-600">*</span>
                        </label>
                        <input type="text" id="nom_pessoa" name="nom_pessoa" maxlength="255"
                            value="{{ old('nom_pessoa', $pessoa->nom_pessoa) }}" placeholder="Digite o nome completo"
                            class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('nom_pessoa') border-red-500 @enderror" />
                        @error('nom_pessoa')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="nom_apelido" class="block font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Apelido
                        </label>
                        <input type="text" id="nom_apelido" name="nom_apelido" maxlength="255"
                            value="{{ old('nom_apelido', $pessoa->nom_apelido) }}"
                            placeholder="Como prefere ser chamado"
                            class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('nom_apelido') border-red-500 @enderror" />
                        @error('nom_apelido')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="tel_pessoa" class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Telefone</label>
                        <input type="tel" id="tel_pessoa" name="tel_pessoa" value="{{ old('tel_pessoa', $pessoa->tel_pessoa) }}" placeholder="(99) 99999-9999"
                            class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 @error('tel_pessoa') border-red-500 @enderror" />
                        @error('tel_pessoa')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="dat_nascimento" class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Data de Nascimento <span class="text-red-600">*</span></label>
                        <input type="date" id="dat_nascimento" name="dat_nascimento" value="{{ old('dat_nascimento', $pessoa->getDataNascimentoFormatada()) }}"
                            class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 @error('dat_nascimento') border-red-500 @enderror" />
                        @error('dat_nascimento')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="eml_pessoa" class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Email <span class="text-red-600">*</span></label>
                        <input type="email" id="eml_pessoa" name="eml_pessoa" value="{{ old('eml_pessoa', $pessoa->eml_pessoa) }}"
                            class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 @error('eml_pessoa') border-red-500 @enderror" />
                        @error('eml_pessoa')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="des_endereco" class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Endereço</label>
                        <input type="text" id="des_endereco" name="des_endereco" value="{{ old('des_endereco', $pessoa->des_endereco) }}"
                            class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100
                            @error('des_endereco') border-red-500 @enderror"/>
                        @error('des_endereco')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="tam_camiseta" class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Tamanho da Camiseta <span class="text-red-600">*</span></label>
                        <select id="tam_camiseta" name="tam_camiseta" class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 @error('tam_camiseta') border-red-500 @enderror">
                            <option value="">Selecione o tamanho</option>
                            @foreach(\App\Enums\TamanhoCamiseta::cases() as $tamanho)
                                <option value="{{ $tamanho->value }}"
                                    {{ old('tam_camiseta', $pessoa->tam_camiseta?->value) == $tamanho->value ? 'selected' : '' }}>
                                    {{ $tamanho->value }}
                                </option>
                            @endforeach
                        </select>
                        @error('tam_camiseta')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="tip_genero" class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Sexo <span class="text-red-600">*</span></label>
                        <select id="tip_genero" name="tip_genero" class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 @error('tip_genero') border-red-500 @enderror">
                            @foreach(\App\Enums\Genero::cases() as $genero)
                                <option value="{{ $genero->value }}"
                                    {{ old('tip_genero', $pessoa->tip_genero) == $genero->value ? 'selected' : '' }}>
                                    {{ $genero->label() }}
                                </option>
                            @endforeach
                        </select>
                        @error('tip_genero')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6" x-data="{ estadoCivil: '{{ old('tip_estado_civil', $pessoa->tip_estado_civil) }}' }">

                        <div>
                            <label for="tip_estado_civil" class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Estado Civil <span class="text-red-600">*</span></label>
                            <select id="tip_estado_civil" name="tip_estado_civil" x-model="estadoCivil" class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 @error('tip_estado_civil') border-red-500 @enderror">
                                <option value="">Selecione o estado civil</option>
                                @foreach(\App\Enums\EstadoCivil::cases() as $estadoCivil)
                                    <option value="{{ $estadoCivil->value }}"
                                        {{ old('tip_estado_civil', $pessoa->tip_estado_civil) == $estadoCivil->value ? 'selected' : '' }}>
                                        {{ $estadoCivil->label() }}
                                    </option>
                                @endforeach
                            </select>
                            @error('tip_estado_civil')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div x-show="['C', 'E', 'U'].includes(estadoCivil)" x-cloak>
                            <label for="idt_parceiro" class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Cônjuge</label>
                            <select id="idt_parceiro" name="idt_parceiro" class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 @error('idt_parceiro') border-red-500 @enderror">
                                <option value="">Selecione o(a) cônjuge</option>
                                @foreach ($pessoasDisponiveis as $id => $nome)
                                    <option value="{{ $id }}"
                                        {{ old('idt_parceiro', $pessoa->idt_parceiro) == $id ? 'selected' : '' }}>
                                        {{ $nome }}</option>
                                @endforeach

                            </select>
                            @error('idt_parceiro')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Habilidade e Profissão --}}
                    <div>
                        <label for="tip_habilidade" class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Habilidade Principal</label>
                        <select id="tip_habilidade" name="tip_habilidade" class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 @error('tip_habilidade') border-red-500 @enderror">
                            <option value="">Selecione uma habilidade</option>
                            @foreach(\App\Enums\HabilidadePrincipal::cases() as $habilidade)
                                <option value="{{ $habilidade->value }}"
                                    {{ old('tip_habilidade', $pessoa->tip_habilidade instanceof \BackedEnum ? $pessoa->tip_habilidade->value : $pessoa->tip_habilidade) == $habilidade->value ? 'selected' : '' }}>
                                    {{ $habilidade->label() }}
                                </option>
                            @endforeach
                        </select>
                        @error('tip_habilidade')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p id="dat_termino_help" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            São habilidades que precisamos no momento
                        </p>
                    </div>
                    <div>
                        <label for="nom_profissao" class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Profissão</label>
                        <input type="text" id="nom_profissao" name="nom_profissao" value="{{ old('nom_profissao', $pessoa->nom_profissao) }}"
                            class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 
                            @error('nom_profissao') border-red-500 @enderror"/>
                        @error('nom_profissao')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div x-data="{
                        mostrarRestricoes: {{ old('ind_restricao', $pessoa->ind_restricao ?? false) ? 'true' : 'false' }},
                        temRestricaoSelecionada: true,
                        verificarRestricoes() {
                            if (!this.mostrarRestricoes) {
                                this.temRestricaoSelecionada = true;
                                return;
                            }
                            const container = this.$refs.restricoesContainer;
                            if (!container) return;
                            const checkboxes = container.querySelectorAll('input[type=\'checkbox\'][name^=\'restricoes\']:checked');
                            const inputs = Array.from(container.querySelectorAll('input[type=\'text\'][name^=\'complementos\']')).filter(i => i.value.trim() !== '');
                            this.temRestricaoSelecionada = checkboxes.length > 0 || inputs.length > 0;
                        }
                    }"
                    @change="verificarRestricoes"
                    @input="verificarRestricoes"
                    x-init="setTimeout(() => verificarRestricoes(), 100)"
                >
                    <label
                        class="flex items-start gap-4 p-4 rounded-lg border-2 cursor-pointer transition-colors
                        border-amber-300 bg-amber-50 hover:bg-amber-100
                        dark:border-amber-500 dark:bg-amber-900/20 dark:hover:bg-amber-900/30"
                        aria-describedby="saude-hint">
                        <div class="flex items-center pt-0.5">
                            <input type="hidden" name="ind_restricao" value="0">
                            <input type="checkbox" name="ind_restricao" value="1" x-model="mostrarRestricoes"
                                {{ old('ind_restricao', $pessoa->ind_restricao) ? 'checked' : '' }}
                                class="w-5 h-5 rounded border-amber-400 text-amber-500 focus:ring-amber-400">
                        </div>
                        <div>
                            <span class="block font-semibold text-amber-800 dark:text-amber-300">
                                Informações de saúde
                            </span>
                            <span id="saude-hint" class="text-sm text-amber-700 dark:text-amber-400">
                                Há alguma informação sobre sua saúde que julga importante sabermos? (alergias,
                                restrições alimentares, necessidades especiais)
                            </span>
                        </div>
                        <x-heroicon-o-heart class="w-6 h-6 text-amber-400 dark:text-amber-500 ml-auto shrink-0"
                            aria-hidden="true" />
                    </label>

                    <div x-show="mostrarRestricoes" x-transition
                        x-ref="restricoesContainer"
                        class="mt-3 bg-gray-50 dark:bg-zinc-700 rounded-md p-4" role="region"
                        aria-label="Restrições e Alergias">
                        <h3 class="text-base sm:text-lg font-medium mb-3 text-gray-900 dark:text-gray-100">
                            Restrições e Alergias
                        </h3>

                        <div x-show="mostrarRestricoes && !temRestricaoSelecionada" x-transition
                            class="mb-4 flex items-center gap-2 rounded-md bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 px-4 py-3"
                            role="alert">
                            <x-heroicon-o-exclamation-circle class="w-5 h-5 text-red-500 shrink-0" aria-hidden="true" />
                            <p class="text-sm text-red-700 dark:text-red-400">
                                Nenhuma restrição ou alergia foi informada. Desmarque a opção Informações de Saúde por favor.
                            </p>
                        </div>

                        <div class="space-y-4">
                            @php
                                $relacaoSaude = $pessoa->restricoes ?? collect();
                                $restricoesSelecionadas = $relacaoSaude->pluck('idt_restricao')->toArray();
                                $complementos = $relacaoSaude->mapWithKeys(function ($item) {
                                    return [$item->idt_restricao => $item->pivot->txt_complemento ?? ''];
                                })->toArray();
                            @endphp

                            @foreach ($restricoes as $restricao)
                                @php
                                    $id = $restricao->idt_restricao;
                                    if (old('_token')) {
                                        $checked = is_array(old('restricoes')) && array_key_exists($id, old('restricoes'));
                                    } else {
                                        $checked = in_array($id, $restricoesSelecionadas);
                                    }
                                    
                                    $complemento = old(
                                        "complementos.{$id}",
                                        $complementos[$id] ?? '',
                                    );
                                @endphp

                                <div class="space-y-2"
                                    x-data="{
                                        selecionado: {{ $checked ? 'true' : 'false' }},
                                        texto: '{{ addslashes($complemento) }}'
                                    }">

                                    <div class="flex items-center gap-2">
                                        <input type="checkbox"
                                            name="restricoes[{{ $restricao->idt_restricao }}]"
                                            id="restricao_{{ $restricao->idt_restricao }}"
                                            value="1"
                                            x-model="selecionado"
                                            class="w-4 h-4 text-blue-600 rounded border-gray-300 dark:border-zinc-600 focus:ring-blue-500 focus:ring-2" />

                                        <label for="restricao_{{ $restricao->idt_restricao }}"
                                            class="text-gray-800 dark:text-gray-100 flex items-center gap-2 cursor-pointer">
                                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-gray-200 text-gray-700 dark:bg-zinc-600 dark:text-gray-300">
                                                {{ $restricao->getTipo() }}
                                            </span>
                                            <span class="text-sm">{{ $restricao->des_restricao }}</span>
                                        </label>
                                    </div>

                                    <input type="text"
                                        name="complementos[{{ $restricao->idt_restricao }}]"
                                        x-model="texto"
                                        @input="if(texto.trim().length > 0) selecionado = true"
                                        placeholder="Complemento ou detalhes adicionais"
                                        maxlength="255"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md text-sm text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-zinc-800" />
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="flex gap-3 justify-end">
                    <flux:button type="submit" variant="primary" class="bg-gradient-to-r from-indigo-500 to-purple-500 hover:from-indigo-600 hover:to-purple-600 border-none shadow-md" icon="check">
                        Salvar
                    </flux:button>
                </div>
            </form>
        </div>
    </section>
</x-layouts.app>
