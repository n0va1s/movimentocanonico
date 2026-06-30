<x-layouts.public :title="'Ficha do ECC'">
    <section class="px-4 py-6 w-full max-w-3xl mx-auto" aria-labelledby="page-title">
         @php
            $eventosJson = json_encode((object) $eventos->mapWithKeys(fn($e) => [
                (string)$e->idt_evento => [
                    'faixa' => $e->tip_faixa_etaria?->label() ?? 'Livre',
                    'data_limite' => $e->dat_limite_inscricao?->format('d/m/Y') ?? '--/--/----',
                    'vaga' => $e->qtd_vaga,
                    'valor' => number_format($e->val_venista, 2, ',', '.'),
                    'dat_inicio'  => $e->dat_inicio?->format('d/m/Y') ?? '--/--/----',
                    'dat_termino' => $e->dat_termino?->format('d/m/Y') ?? '--/--/----'
                ]
            ])->all());
        @endphp

        <div x-data="{ 
            bloqueado: {{ ($ficha->ind_aprovado ?? false) ? 'true' : 'false' }}, 
            enviando: false,
            selectedEventoId: '{{ old('idt_evento', $ficha->idt_evento ?? '') }}',
            eventosData: {{ $eventosJson }},
            get info() {
                return this.eventosData[String(this.selectedEventoId)] || { faixa: '---', data_limite: '---', vaga: '---', valor: '0,00' };
            },
            qtdFilhos: {{ old('qtd_filhos', $ficha->fichaEcc?->qtd_filhos ?? 0) }},
            filhos: {{ Js::from(old('filhos', $ficha->fichaEcc?->filhos?->map(fn($f) => [
                'num_cpf_filho'            => $f->num_cpf_filho,
                'nom_filho'            => $f->nom_filho,
                'tel_filho'            => $f->tel_filho,
                'eml_filho'            => $f->eml_filho,
                'dat_nascimento_filho' => $f->dat_nascimento_filho?->format('Y-m-d'),
            ])->toArray() ?? [])) }},
        }">

        {{-- ===== CABEÇALHO ===== --}}
        <div class="mb-6 space-y-4">
            <div>
                <h1 id="page-title" class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-gray-100">Ficha do ECC</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1 text-sm sm:text-base">Paróquia Nossa Senhora do Lago</p>
            </div>

            {{-- Etapas --}}
            <div class="bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-zinc-700 p-4">
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Como funciona</p>
                <ol class="flex flex-col sm:flex-row gap-4" aria-label="Etapas do processo de inscrição">
                    <li class="flex-1 flex items-start gap-3">
                        <span
                            class="w-7 h-7 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 text-xs font-bold flex items-center justify-center shrink-0"
                            aria-hidden="true">1</span>
                        <div>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">Pré-inscrição</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Preencha esta ficha e fique
                                atento às informações enviadas por email</p>
                        </div>
                    </li>
                    <li class="hidden sm:flex items-center text-gray-300 dark:text-zinc-600" aria-hidden="true">
                        <x-heroicon-o-chevron-right class="w-5 h-5" />
                    </li>
                    <li class="flex-1 flex items-start gap-3">
                        <span
                            class="w-7 h-7 rounded-full bg-amber-100 dark:bg-amber-900 text-amber-700 dark:text-amber-300 text-xs font-bold flex items-center justify-center shrink-0"
                            aria-hidden="true">2</span>
                        <div>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">Seleção de fichas</p>
                            <ul class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 space-y-0.5"
                                aria-label="Critérios de seleção">
                                <li>• Casais comprometidos com a paróquia</li>
                                <li>• Preferência por casamentos mais recentes</li>
                                <li>• Paroquianos do Lago Norte</li>
                            </ul>
                        </div>
                    </li>
                    <li class="hidden sm:flex items-center text-gray-300 dark:text-zinc-600" aria-hidden="true">
                        <x-heroicon-o-chevron-right class="w-5 h-5" />
                    </li>
                    <li class="flex-1 flex items-start gap-3">
                        <span
                            class="w-7 h-7 rounded-full bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300 text-xs font-bold flex items-center justify-center shrink-0"
                            aria-hidden="true">3</span>
                        <div>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">Confirmação</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Contato por email ou WhatsApp</p>
                        </div>
                    </li>
                </ol>
            </div>
        </div>

        {{-- Botão voltar (admin) --}}
        @if (Auth::user()?->isAdmin())
            <div class="flex justify-end mb-4">
                <a href="{{ route('ecc.index') }}"
                    class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 dark:hover:bg-green-500 focus:ring-2 focus:ring-green-500 focus:outline-none focus-visible:ring-offset-2"
                    aria-label="Voltar para a lista de fichas">
                    <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" aria-hidden="true" />
                    Fichas
                </a>
            </div>
        @endif

        @if (Auth::user()?->hasRole('admin', 'dirig', 'coord') && $ficha->exists)
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow border border-gray-200 dark:border-zinc-700 p-4 sm:p-6 mb-6">
                <p class="text-xs font-semibold text-gray-400 dark:text-zinc-500 uppercase tracking-wider mb-3">Mudar Situação para:</p>
                <div class="flex flex-wrap gap-2">
                    @php
                        $situacoes = \App\Enums\TipoSituacao::cases();
                    @endphp
                    @foreach($situacoes as $situacao)
                        @php
                            $isCurrent = $ficha->tip_situacao === $situacao;
                            $style = $situacao->badge();
                        @endphp
                        
                        @if($isCurrent)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-bold text-white {{ $style['bg'] }} shadow-sm">
                                <x-heroicon-s-check class="w-4 h-4" />
                                {{ $situacao->label() }}
                            </span>
                        @else
                            <form method="POST" action="{{ route('ecc.situacao', $ficha->idt_ficha) }}" class="inline">
                                @csrf
                                <input type="hidden" name="tip_situacao" value="{{ $situacao->value }}">
                                <button type="submit" 
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-semibold border transition-all duration-200 cursor-pointer hover:text-white {{ $style['light'] }} {{ $style['hover'] }}">
                                    @if($situacao->value === 'N')
                                        <x-heroicon-o-document-text class="w-4 h-4" />
                                    @elseif($situacao->value === 'S')
                                        <x-heroicon-o-check-circle class="w-4 h-4" />
                                    @elseif($situacao->value === 'E')
                                        <x-heroicon-o-envelope class="w-4 h-4" />
                                    @elseif($situacao->value === 'R')
                                        <x-heroicon-o-document-check class="w-4 h-4" />
                                    @elseif($situacao->value === 'P')
                                        <x-heroicon-o-credit-card class="w-4 h-4" />
                                    @elseif($situacao->value === 'C')
                                        <x-heroicon-o-x-circle class="w-4 h-4" />
                                    @elseif($situacao->value === 'A')
                                        <x-heroicon-o-sparkles class="w-4 h-4" />
                                    @elseif($situacao->value === 'F')
                                        <x-heroicon-o-phone class="w-4 h-4" />
                                    @elseif($situacao->value === 'W')
                                        <x-heroicon-o-clock class="w-4 h-4" />
                                    @elseif($situacao->value === 'V')
                                        <x-heroicon-o-check-circle class="w-4 h-4" />
                                    @endif
                                    {{ $situacao->label() }}
                                </button>
                            </form>
                        @endif
                    @endforeach
                </div>
                @if(Auth::user()?->hasRole('admin', 'dirig'))
                    <div class="mt-4 border-t border-gray-100 dark:border-zinc-700 pt-4">
                        <form method="POST" action="{{ route('fichas.designar-visitador', $ficha->idt_ficha) }}">
                            @csrf
                            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
                                <label for="idt_pessoa_visitacao" class="text-xs font-semibold text-gray-500 dark:text-zinc-400 uppercase tracking-wider">Responsável pela Visitação:</label>
                                <div class="flex items-center gap-2 w-full sm:w-auto">
                                    <select name="idt_pessoa_visitacao" id="idt_pessoa_visitacao" 
                                        class="text-xs rounded-md border border-gray-300 dark:border-zinc-600 dark:bg-zinc-800 text-gray-900 dark:text-gray-100 px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">Sem responsável designado</option>
                                        @if(isset($visitadores))
                                            @foreach($visitadores as $v)
                                                <option value="{{ $v->idt_pessoa }}" @selected($ficha->idt_pessoa_visitacao === $v->idt_pessoa)>
                                                    {{ $v->nom_pessoa }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                    <button type="submit" class="px-3 py-1.5 bg-blue-600 text-white rounded-md text-xs font-semibold hover:bg-blue-700">
                                        Designar
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                @endif
            </div>
        @endif

        @if ($eventos->count() > 0)
            <form method="POST" enctype="multipart/form-data"
                @submit="setTimeout(() => enviando = true, 50)"
                action="{{ $ficha->exists ? route('ecc.update', $ficha) : route('ecc.store') }}" 
                class="space-y-6" novalidate>
                @csrf
                @if ($ficha->exists) @method('PUT') @endif

                {{-- ===== DADOS DO(A) PARTICIPANTE ===== --}}
                <fieldset class="bg-white dark:bg-zinc-800 rounded-md shadow p-4 sm:p-6">
                    <legend class="text-lg sm:text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">
                        Dados do(a) Participante
                    </legend>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                        
                        {{-- Foto --}}
                        <div class="flex flex-col items-center gap-3" x-data="{ photoPreview: '{{ $ficha->foto?->med_conjuge ? Storage::url($ficha->foto->med_conjuge) : '' }}' }">
                            <div
                                class="w-28 h-28 rounded-full bg-gray-100 dark:bg-zinc-700 border-2 border-gray-300 dark:border-zinc-600 flex items-center justify-center overflow-hidden">
                                <template x-if="photoPreview">
                                    <img :src="photoPreview" alt="Foto do participante"
                                        class="w-full h-full object-cover" />
                                </template>
                                <template x-if="!photoPreview">
                                    <x-heroicon-o-user class="w-14 h-14 text-gray-400 dark:text-gray-500"
                                        aria-hidden="true" />
                                </template>
                            </div>
                            <div>
                                <label for="med_conjuge"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 text-center">
                                    Foto
                                </label>
                                <input type="file" name="med_conjuge" id="med_conjuge" accept="image/*"
                                    x-bind:disabled="bloqueado"
                                    @change="
                                        const file = $event.target.files[0];
                                        if (file) {
                                            photoPreview = URL.createObjectURL(file);
                                        } else {
                                            photoPreview = '{{ $ficha->foto?->med_conjuge ? Storage::url($ficha->foto->med_conjuge) : '' }}';
                                        }
                                    "
                                    class="block text-sm text-gray-600 dark:text-gray-400 file:mr-4 file:py-1.5 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-900/30 dark:file:text-blue-300" />
                                @error('med_conjuge')
                                    <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- CPF --}}
                        <div>
                            <label for="num_cpf_candidato"
                                class="block font-medium text-gray-700 dark:text-gray-300 mb-1 text-sm sm:text-base">
                                CPF <span class="text-red-600" aria-hidden="true">*</span><span class="sr-only">(obrigatório)</span>
                            </label>
                            <input type="text" name="num_cpf_candidato" id="num_cpf_candidato"
                                x-bind:disabled="bloqueado" required maxlength="14" autocomplete="off"
                                value="{{ old('num_cpf_candidato', $ficha->num_cpf_candidato) }}"
                                @blur="buscarPorCpf()"
                                placeholder="000.000.000-00" aria-required="true"
                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('num_cpf_candidato') border-red-500 @enderror" />
                            @error('num_cpf_candidato')
                                <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Nome completo --}}
                        <div>
                            <label for="nom_candidato"
                                class="block font-medium text-gray-700 dark:text-gray-300 mb-1 text-sm sm:text-base">
                                Nome completo <span class="text-red-600" aria-hidden="true">*</span><span class="sr-only">(obrigatório)</span>
                            </label>
                            <input type="text" name="nom_candidato" id="nom_candidato"
                                x-bind:disabled="bloqueado" required maxlength="255" autocomplete="name"
                                value="{{ old('nom_candidato', $ficha->nom_candidato) }}"
                                placeholder="Digite o nome completo" aria-required="true"
                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('nom_candidato') border-red-500 @enderror" />
                            @error('nom_candidato')
                                <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Apelido --}}
                        <div>
                            <label for="nom_apelido"
                                class="block font-medium text-gray-700 dark:text-gray-300 mb-1 text-sm sm:text-base">
                                Apelido 
                            </label>
                            <input type="text" name="nom_apelido" id="nom_apelido"
                                x-bind:disabled="bloqueado" maxlength="100"
                                value="{{ old('nom_apelido', $ficha->nom_apelido) }}"
                                placeholder="Como gosta de ser chamado(a)"
                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('nom_apelido') border-red-500 @enderror" />
                            @error('nom_apelido')
                                <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Sexo --}}
                        <div>
                            <label for="tip_genero"
                                class="block font-medium text-gray-700 dark:text-gray-300 mb-1 text-sm sm:text-base">
                                Sexo <span class="text-red-600" aria-hidden="true">*</span><span class="sr-only">(obrigatório)</span>
                            </label>
                            <select name="tip_genero" id="tip_genero" required x-bind:disabled="bloqueado"
                                aria-required="true"
                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('tip_genero') border-red-500 @enderror">
                                <option value="">Selecione o sexo</option>
                                @foreach(\App\Enums\Genero::cases() as $genero)
                                    <option value="{{ $genero->value }}"
                                        {{ old('tip_genero', $ficha->tip_genero?->value) == $genero->value ? 'selected' : '' }}>
                                        {{ $genero->label() }}
                                    </option>
                                @endforeach
                            </select>
                            @error('tip_genero')
                                <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Data de Nascimento --}}
                        <div>
                            <label for="dat_nascimento"
                                class="block font-medium text-gray-700 dark:text-gray-300 mb-1 text-sm sm:text-base">
                                Data de Nascimento <span class="text-red-600" aria-hidden="true">*</span><span class="sr-only">(obrigatório)</span>
                            </label>
                            <input type="date" name="dat_nascimento" id="dat_nascimento"
                                x-bind:disabled="bloqueado" required autocomplete="bday"
                                value="{{ old('dat_nascimento', $ficha->dat_nascimento?->format('Y-m-d')) }}"
                                aria-required="true"
                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('dat_nascimento') border-red-500 @enderror" />
                            @error('dat_nascimento')
                                <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Celular --}}
                        <div>
                            <label for="tel_candidato"
                                class="block font-medium text-gray-700 dark:text-gray-300 mb-1 text-sm sm:text-base">
                                Celular
                            </label>
                            <input type="tel" name="tel_candidato" id="tel_candidato"
                                x-bind:disabled="bloqueado" maxlength="20" autocomplete="tel"
                                value="{{ old('tel_candidato', $ficha->tel_candidato) }}"
                                placeholder="(61) 90000-0000"
                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('tel_candidato') border-red-500 @enderror" />
                            @error('tel_candidato')
                                <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Email --}}
                        <div>
                            <label for="eml_candidato"
                                class="block font-medium text-gray-700 dark:text-gray-300 mb-1 text-sm sm:text-base">
                                Email <span class="text-red-600" aria-hidden="true">*</span><span class="sr-only">(obrigatório)</span>
                            </label>
                            <input type="email" name="eml_candidato" id="eml_candidato"
                                x-bind:disabled="bloqueado" required maxlength="255" autocomplete="email"
                                value="{{ old('eml_candidato', $ficha->eml_candidato) }}"
                                placeholder="exemplo@email.com" aria-required="true"
                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('eml_candidato') border-red-500 @enderror" />
                            @error('eml_candidato')
                                <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Profissão --}}
                        <div>
                            <label for="nom_profissao"
                                class="block font-medium text-gray-700 dark:text-gray-300 mb-1 text-sm sm:text-base">
                                Profissão
                            </label>
                            <input type="text" name="nom_profissao" id="nom_profissao"
                                x-bind:disabled="bloqueado" maxlength="150"
                                value="{{ old('nom_profissao', $ficha->nom_profissao) }}"
                                placeholder="Sua profissão"
                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('nom_profissao') border-red-500 @enderror" />
                            @error('nom_profissao')
                                <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- É católico? --}}
                        <div class="flex items-center gap-3">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="hidden" name="ind_catolico" value="0">
                                <input type="checkbox" name="ind_catolico" value="1"
                                    x-bind:disabled="bloqueado"
                                    {{ old('ind_catolico', $ficha->ind_catolico) ? 'checked' : '' }}
                                    class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="font-medium text-gray-700 dark:text-gray-300 text-sm sm:text-base">É católico(a)?</span>
                            </label>
                        </div>

                        {{-- Habilidade Principal --}}
                        <div>
                            <label for="tip_habilidade"
                                class="block font-medium text-gray-700 dark:text-gray-300 mb-1 text-sm sm:text-base">
                                Habilidade principal
                            </label>
                            <select name="tip_habilidade" id="tip_habilidade" x-bind:disabled="bloqueado"
                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('tip_habilidade') border-red-500 @enderror">
                                <option value="">Selecione uma habilidade</option>
                                @foreach(\App\Enums\HabilidadePrincipal::cases() as $habilidade)
                                    <option value="{{ $habilidade->value }}"
                                        {{ old('tip_habilidade', $ficha->tip_habilidade?->value) == $habilidade->value ? 'selected' : '' }}>
                                        {{ $habilidade->label() }}
                                    </option>
                                @endforeach
                            </select>
                            @error('tip_habilidade')
                                <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Tamanho da Camiseta --}}
                        <div>
                            <label for="tam_camiseta"
                                class="block font-medium text-gray-700 dark:text-gray-300 mb-1 text-sm sm:text-base">
                                Tamanho da Camiseta <span class="text-red-600" aria-hidden="true">*</span><span class="sr-only">(obrigatório)</span>
                            </label>
                            <select name="tam_camiseta" id="tam_camiseta" required x-bind:disabled="bloqueado"
                                aria-required="true"
                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('tam_camiseta') border-red-500 @enderror">
                                <option value="">Selecione o tamanho</option>
                                @foreach(\App\Enums\TamanhoCamiseta::cases() as $tamanho)
                                    <option value="{{ $tamanho->value }}"
                                        {{ old('tam_camiseta', $ficha->tam_camiseta?->value) == $tamanho->value ? 'selected' : '' }}>
                                        {{ $tamanho->value }}
                                    </option>
                                @endforeach
                            </select>
                            @error('tam_camiseta')
                                <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                    </div>
                </fieldset>

                {{-- ===== DADOS DO(A) CÔNJUGE ===== --}}
                <fieldset class="bg-white dark:bg-zinc-800 rounded-md shadow p-4 sm:p-6">
                    <legend class="text-lg sm:text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">
                        Dados do(a) Cônjuge
                    </legend>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                        
                        {{-- Foto --}}
                        <div class="flex flex-col items-center gap-3" x-data="{ photoPreview: '{{ $ficha->foto?->med_conjuge ? Storage::url($ficha->foto->med_conjuge) : '' }}' }">
                            <div
                                class="w-28 h-28 rounded-full bg-gray-100 dark:bg-zinc-700 border-2 border-gray-300 dark:border-zinc-600 flex items-center justify-center overflow-hidden">
                                <template x-if="photoPreview">
                                    <img :src="photoPreview" alt="Foto do participante"
                                        class="w-full h-full object-cover" />
                                </template>
                                <template x-if="!photoPreview">
                                    <x-heroicon-o-user class="w-14 h-14 text-gray-400 dark:text-gray-500"
                                        aria-hidden="true" />
                                </template>
                            </div>
                            <div>
                                <label for="med_conjuge"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 text-center">
                                    Foto
                                </label>
                                <input type="file" name="med_conjuge" id="med_conjuge" accept="image/*"
                                    x-bind:disabled="bloqueado"
                                    @change="
                                        const file = $event.target.files[0];
                                        if (file) {
                                            photoPreview = URL.createObjectURL(file);
                                        } else {
                                            photoPreview = '{{ $ficha->foto?->med_conjuge ? Storage::url($ficha->foto->med_conjuge) : '' }}';
                                        }
                                    "
                                    class="block text-sm text-gray-600 dark:text-gray-400 file:mr-4 file:py-1.5 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-900/30 dark:file:text-blue-300" />
                                @error('med_conjuge')
                                    <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- CPF --}}
                        <div>
                            <label for="num_cpf_conjuge"
                                class="block font-medium text-gray-700 dark:text-gray-300 mb-1 text-sm sm:text-base">
                                CPF <span class="text-red-600" aria-hidden="true">*</span><span class="sr-only">(obrigatório)</span>
                            </label>
                            <input type="text" name="num_cpf_conjuge" id="num_cpf_conjuge"
                                x-bind:disabled="bloqueado" required maxlength="14" autocomplete="off"
                                value="{{ old('num_cpf_conjuge', $ficha->num_cpf_conjuge) }}"
                                @blur="buscarPorCpf()"
                                placeholder="000.000.000-00" aria-required="true"
                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('num_cpf_conjuge') border-red-500 @enderror" />
                            @error('num_cpf_conjuge')
                                <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Nome cônjuge --}}
                        <div>
                            <label for="nom_conjuge"
                                class="block font-medium text-gray-700 dark:text-gray-300 mb-1 text-sm sm:text-base">
                                Nome completo <span class="text-red-600" aria-hidden="true">*</span><span class="sr-only">(obrigatório)</span>
                            </label>
                            <input type="text" name="nom_conjuge" id="nom_conjuge"
                                x-bind:disabled="bloqueado" required maxlength="255" autocomplete="off"
                                value="{{ old('nom_conjuge', $ficha->fichaEcc?->nom_conjuge) }}"
                                placeholder="Digite o nome completo" aria-required="true"
                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('nom_conjuge') border-red-500 @enderror" />
                            @error('nom_conjuge')
                                <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Apelido cônjuge --}}
                        <div>
                            <label for="nom_apelido_conjuge"
                                class="block font-medium text-gray-700 dark:text-gray-300 mb-1 text-sm sm:text-base">
                                Apelido 
                            </label>
                            <input type="text" name="nom_apelido_conjuge" id="nom_apelido_conjuge"
                                x-bind:disabled="bloqueado" maxlength="100"
                                value="{{ old('nom_apelido_conjuge', $ficha->fichaEcc?->nom_apelido_conjuge) }}"
                                placeholder="Como gosta de ser chamado(a)"
                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('nom_apelido_conjuge') border-red-500 @enderror" />
                            @error('nom_apelido_conjuge')
                                <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Sexo cônjuge --}}
                        <div>
                            <label for="tip_genero_conjuge"
                                class="block font-medium text-gray-700 dark:text-gray-300 mb-1 text-sm sm:text-base">
                                Sexo <span class="text-red-600" aria-hidden="true">*</span><span class="sr-only">(obrigatório)</span>
                            </label>
                            <select name="tip_genero_conjuge" id="tip_genero_conjuge" required x-bind:disabled="bloqueado"
                                aria-required="true"
                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('tip_genero_conjuge') border-red-500 @enderror">
                                <option value="">Selecione o sexo</option>
                                @foreach(\App\Enums\Genero::cases() as $genero)
                                    <option value="{{ $genero->value }}"
                                        {{ old('tip_genero_conjuge', $ficha->fichaEcc?->tip_genero_conjuge?->value) == $genero->value ? 'selected' : '' }}>
                                        {{ $genero->label() }}
                                    </option>
                                @endforeach
                            </select>
                            @error('tip_genero_conjuge')
                                <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Data de Nascimento cônjuge --}}
                        <div>
                            <label for="dat_nascimento_conjuge"
                                class="block font-medium text-gray-700 dark:text-gray-300 mb-1 text-sm sm:text-base">
                                Data de Nascimento <span class="text-red-600" aria-hidden="true">*</span><span class="sr-only">(obrigatório)</span>
                            </label>
                            <input type="date" name="dat_nascimento_conjuge" id="dat_nascimento_conjuge"
                                x-bind:disabled="bloqueado" required autocomplete="off"
                                value="{{ old('dat_nascimento_conjuge', $ficha->fichaEcc?->dat_nascimento_conjuge?->format('Y-m-d')) }}"
                                aria-required="true"
                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('dat_nascimento_conjuge') border-red-500 @enderror" />
                            @error('dat_nascimento_conjuge')
                                <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Celular cônjuge --}}
                        <div>
                            <label for="tel_conjuge"
                                class="block font-medium text-gray-700 dark:text-gray-300 mb-1 text-sm sm:text-base">
                                Celular
                            </label>
                            <input type="tel" name="tel_conjuge" id="tel_conjuge"
                                x-bind:disabled="bloqueado" maxlength="20" autocomplete="off"
                                value="{{ old('tel_conjuge', $ficha->fichaEcc?->tel_conjuge) }}"
                                placeholder="(61) 90000-0000"
                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('tel_conjuge') border-red-500 @enderror" />
                            @error('tel_conjuge')
                                <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Email cônjuge --}}
                        <div>
                            <label for="eml_conjuge"
                                class="block font-medium text-gray-700 dark:text-gray-300 mb-1 text-sm sm:text-base">
                                Email
                            </label>
                            <input type="email" name="eml_conjuge" id="eml_conjuge"
                                x-bind:disabled="bloqueado" maxlength="255" autocomplete="off"
                                value="{{ old('eml_conjuge', $ficha->fichaEcc?->eml_conjuge) }}"
                                placeholder="exemplo@email.com"
                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('eml_conjuge') border-red-500 @enderror" />
                            @error('eml_conjuge')
                                <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Profissão cônjuge --}}
                        <div>
                            <label for="nom_profissao_conjuge"
                                class="block font-medium text-gray-700 dark:text-gray-300 mb-1 text-sm sm:text-base">
                                Profissão
                            </label>
                            <input type="text" name="nom_profissao_conjuge" id="nom_profissao_conjuge"
                                x-bind:disabled="bloqueado" maxlength="150"
                                value="{{ old('nom_profissao_conjuge', $ficha->fichaEcc?->nom_profissao_conjuge) }}"
                                placeholder="Profissão do cônjuge"
                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('nom_profissao_conjuge') border-red-500 @enderror" />
                            @error('nom_profissao_conjuge')
                                <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- É católico? cônjuge --}}
                        <div class="flex items-center gap-3">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="hidden" name="ind_catolico_conjuge" value="0">
                                <input type="checkbox" name="ind_catolico_conjuge" value="1"
                                    x-bind:disabled="bloqueado"
                                    {{ old('ind_catolico_conjuge', $ficha->fichaEcc?->ind_catolico_conjuge) ? 'checked' : '' }}
                                    class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="font-medium text-gray-700 dark:text-gray-300 text-sm sm:text-base">É católico(a)?</span>
                            </label>
                        </div>

                        {{-- Habilidade Principal cônjuge --}}
                        <div>
                            <label for="tip_habilidade_conjuge"
                                class="block font-medium text-gray-700 dark:text-gray-300 mb-1 text-sm sm:text-base">
                                Habilidade principal
                            </label>
                            <select name="tip_habilidade_conjuge" id="tip_habilidade_conjuge" x-bind:disabled="bloqueado"
                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('tip_habilidade_conjuge') border-red-500 @enderror">
                                <option value="">Selecione uma habilidade</option>
                                @foreach(\App\Enums\HabilidadePrincipal::cases() as $habilidade)
                                    <option value="{{ $habilidade->value }}"
                                        {{ old('tip_habilidade_conjuge', $ficha->fichaEcc?->tip_habilidade_conjuge?->value) == $habilidade->value ? 'selected' : '' }}>
                                        {{ $habilidade->label() }}
                                    </option>
                                @endforeach
                            </select>
                            @error('tip_habilidade_conjuge')
                                <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Tamanho da Camiseta cônjuge --}}
                        <div>
                            <label for="tam_camiseta_conjuge"
                                class="block font-medium text-gray-700 dark:text-gray-300 mb-1 text-sm sm:text-base">
                                Tamanho da Camiseta <span class="text-red-600" aria-hidden="true">*</span><span class="sr-only">(obrigatório)</span>
                            </label>
                            <select name="tam_camiseta_conjuge" id="tam_camiseta_conjuge" required x-bind:disabled="bloqueado"
                                aria-required="true"
                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('tam_camiseta_conjuge') border-red-500 @enderror">
                                <option value="">Selecione o tamanho</option>
                                @foreach(\App\Enums\TamanhoCamiseta::cases() as $tamanho)
                                    <option value="{{ $tamanho->value }}"
                                        {{ old('tam_camiseta_conjuge', $ficha->fichaEcc?->tam_camiseta_conjuge?->value) == $tamanho->value ? 'selected' : '' }}>
                                        {{ $tamanho->value }}
                                    </option>
                                @endforeach
                            </select>
                            @error('tam_camiseta_conjuge')
                                <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                    </div>
                </fieldset>

                {{-- ===== OUTRAS INFORMAÇÕES ===== --}}
                <fieldset class="bg-white dark:bg-zinc-800 rounded-md shadow p-4 sm:p-6">
                    <legend class="text-lg sm:text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">
                        Outras informações
                    </legend>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">

                        {{-- Movimento --}}
                        <div>
                            <label for="idt_movimento"
                                class="block font-medium text-gray-700 dark:text-gray-300 mb-1 text-sm sm:text-base">
                                Movimento
                            </label>
                            <select id="idt_movimento" name="idt_movimento" disabled
                                aria-describedby="idt_movimento_hint"
                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('idt_movimento') border-red-500 @enderror">
                                <option value="">Selecione um movimento</option>
                                @foreach ($movimentos as $movimento)
                                    <option value="{{ $movimento->idt_movimento }}"
                                        {{ old('idt_movimento', $movimentopadrao) == $movimento->idt_movimento ? 'selected' : '' }}>
                                        {{ $movimento->nom_movimento }} ({{ $movimento->des_sigla }})
                                    </option>
                                @endforeach
                            </select>
                            <p id="idt_movimento_hint" class="mt-1 text-xs text-gray-500 dark:text-gray-400">Filtra as
                                opções de evento disponíveis.</p>
                            @error('idt_movimento')
                                <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Evento --}}
                        <div>
                            <label for="idt_evento" ...>Evento</label>
                            <select name="idt_evento" id="idt_evento" 
                                x-model="selectedEventoId"
                                x-bind:disabled="bloqueado"
                                required
                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 @error('idt_evento') border-red-500 @enderror">
                                <option value="">Selecione um evento</option>
                                @foreach ($eventos as $evento)
                                    <option value="{{ $evento->idt_evento }}"
                                        {{ old('idt_evento', $ficha->idt_evento) == $evento->idt_evento ? 'selected' : '' }}>
                                        {{ $evento->des_evento }} - {{ $evento->dat_inicio->format('d/m/Y') }}
                                    </option>
                                @endforeach
                            </select>
                            @error('idt_evento')
                                <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Como soube do evento --}}
                        <div>
                            <label for="tip_como_soube"
                                class="block font-medium text-gray-700 dark:text-gray-300 mb-1 text-sm sm:text-base">
                                Como souberam do evento
                            </label>
                            <select name="tip_como_soube" id="tip_como_soube" x-bind:disabled="bloqueado"
                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('tip_como_soube') border-red-500 @enderror">
                                <option value="">Selecione uma opção</option>
                                @foreach(\App\Enums\ComoSoube::cases() as $comoSoube)
                                    <option value="{{ $comoSoube->value }}"
                                        {{ old('tip_como_soube', $ficha->tip_como_soube?->value) == $comoSoube->value ? 'selected' : '' }}>
                                        {{ $comoSoube->label() }}
                                    </option>
                                @endforeach
                            </select>
                            @error('tip_como_soube')
                                <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Paróquia que frequentam --}}
                        <div>
                            <label for="nom_paroquia"
                                class="block font-medium text-gray-700 dark:text-gray-300 mb-1 text-sm sm:text-base">
                                Paróquia que frequentam
                            </label>
                            <input type="text" name="nom_paroquia" id="nom_paroquia"
                                x-bind:disabled="bloqueado" maxlength="150"
                                value="{{ old('nom_paroquia', $ficha->fichaEcc?->nom_paroquia) }}"
                                placeholder="Nome da paróquia"
                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('nom_paroquia') border-red-500 @enderror" />
                            @error('nom_paroquia')
                                <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Endereço --}}
                        <div>
                            <label for="des_endereco"
                                class="block font-medium text-gray-700 dark:text-gray-300 mb-1 text-sm sm:text-base">
                                Endereço <span class="text-red-600" aria-hidden="true">*</span><span class="sr-only">(obrigatório)</span>
                            </label>
                            <input type="text" name="des_endereco" id="des_endereco" x-bind:disabled="bloqueado"
                                required aria-required="true"
                                maxlength="255" autocomplete="street-address"
                                value="{{ old('des_endereco', $ficha->des_endereco) }}"
                                placeholder="Rua, número, bairro, cidade"
                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('des_endereco') border-red-500 @enderror" />
                            @error('des_endereco')
                                <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Estado Civil --}}
                        <div>
                            <label for="tip_estado_civil"
                                class="block font-medium text-gray-700 dark:text-gray-300 mb-1 text-sm sm:text-base">
                                Estado Civil <span class="text-red-600" aria-hidden="true">*</span><span class="sr-only">(obrigatório)</span>
                            </label>
                            <select name="tip_estado_civil" id="tip_estado_civil" required x-bind:disabled="bloqueado"
                                aria-required="true"
                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('tip_estado_civil') border-red-500 @enderror">
                                <option value="">Selecione o regime</option>
                                @foreach(\App\Enums\EstadoCivil::cases() as $estado)
                                    <option value="{{ $estado->value }}"
                                        {{ old('tip_estado_civil', $ficha->fichaEcc?->tip_estado_civil?->value) == $estado->value ? 'selected' : '' }}>
                                        {{ $estado->label() }}
                                    </option>
                                @endforeach
                            </select>
                            @error('tip_estado_civil')
                                <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Data de casamento religioso --}}
                        <div>
                            <label for="dat_casamento"
                                class="block font-medium text-gray-700 dark:text-gray-300 mb-1 text-sm sm:text-base">
                                Data de casamento religioso
                            </label>
                            <input type="date" name="dat_casamento" id="dat_casamento"
                                x-bind:disabled="bloqueado"
                                value="{{ old('dat_casamento', $ficha->fichaEcc?->dat_casamento?->format('Y-m-d')) }}"
                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('dat_casamento') border-red-500 @enderror" />
                            @error('dat_casamento')
                                <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Nº de filhos --}}
                        <div>
                            <label for="qtd_filhos"
                                class="block font-medium text-gray-700 dark:text-gray-300 mb-1 text-sm sm:text-base">
                                Nº de filhos
                            </label>
                            <input type="number" name="qtd_filhos" id="qtd_filhos"
                                x-bind:disabled="bloqueado" min="0" max="20"
                                x-model.number="qtdFilhos"
                                value="{{ old('qtd_filhos', $ficha->fichaEcc?->qtd_filhos ?? 0) }}"
                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('qtd_filhos') border-red-500 @enderror" />
                            @error('qtd_filhos')
                                <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Fale um pouco sobre vocês --}}
                        <div class="sm:col-span-2">
                            <label for="txt_observacao"
                                class="block font-medium text-gray-700 dark:text-gray-300 mb-1 text-sm sm:text-base">
                                Falem um pouco sobre vocês
                            </label>
                            <textarea name="txt_observacao" id="txt_observacao" rows="4" maxlength="1000"
                                x-bind:disabled="bloqueado"
                                placeholder="Participam de outros movimentos? Quais habilidades vocês podem contribuir com a paróquia? Moram há quanto tempo na região?"
                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-sm sm:text-base text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('txt_observacao') border-red-500 @enderror">{{ old('txt_observacao', $ficha->txt_observacao) }}</textarea>
                            @error('txt_observacao')
                                <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Máximo de 1000 caracteres</p>
                        </div>

                    </div>
                </fieldset>

                {{-- ===== DADOS DOS FILHOS ===== --}}
                <div x-show="qtdFilhos > 0" x-transition>
                    <fieldset class="bg-white dark:bg-zinc-800 rounded-md shadow p-4 sm:p-6">
                        <legend class="text-lg sm:text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            Dados dos Filhos
                        </legend>
                        <div class="space-y-6">
                            <template x-for="(filho, index) in Array.from({ length: qtdFilhos })" :key="index">
                                <div class="border border-gray-200 dark:border-zinc-700 rounded-md p-4">
                                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3"
                                        x-text="`${index + 1}º filho(a)`"></p>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">

                                        {{-- CPF filho --}}
                                        <div>
                                            <label :for="`num_cpf_filho_${index}`"
                                                class="block text-sm text-gray-600 dark:text-gray-400 mb-1">CPF</label>
                                            <input type="text"
                                                :name="`filhos[${index}][num_cpf_filho]`"
                                                :id="`num_cpf_filho_${index}`"
                                                x-bind:disabled="bloqueado" maxlength="14" autocomplete="off"
                                                :value="filhos[index]?.num_cpf_filho ?? ''"
                                                placeholder="000.000.000-00"
                                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500" />
                                        </div>

                                        {{-- Nome filho --}}
                                        <div>
                                            <label :for="`nom_filho_${index}`"
                                                class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Nome</label>
                                            <input type="text"
                                                :name="`filhos[${index}][nom_filho]`"
                                                :id="`nom_filho_${index}`"
                                                x-bind:disabled="bloqueado" maxlength="255" autocomplete="off"
                                                :value="filhos[index]?.nom_filho ?? ''"
                                                placeholder="Nome completo"
                                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500" />
                                        </div>

                                        {{-- Telefone filho --}}
                                        <div>
                                            <label :for="`tel_filho_${index}`"
                                                class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Telefone</label>
                                            <input type="tel"
                                                :name="`filhos[${index}][tel_filho]`"
                                                :id="`tel_filho_${index}`"
                                                x-bind:disabled="bloqueado" maxlength="20" autocomplete="off"
                                                :value="filhos[index]?.tel_filho ?? ''"
                                                placeholder="(61) 90000-0000"
                                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500" />
                                        </div>

                                        {{-- Email filho --}}
                                        <div>
                                            <label :for="`eml_filho_${index}`"
                                                class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Email</label>
                                            <input type="email"
                                                :name="`filhos[${index}][eml_filho]`"
                                                :id="`eml_filho_${index}`"
                                                x-bind:disabled="bloqueado" maxlength="255" autocomplete="off"
                                                :value="filhos[index]?.eml_filho ?? ''"
                                                placeholder="exemplo@email.com"
                                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500" />
                                        </div>

                                        {{-- Data de nascimento filho --}}
                                        <div>
                                            <label :for="`dat_nascimento_filho_${index}`"
                                                class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Data de nascimento</label>
                                            <input type="date"
                                                :name="`filhos[${index}][dat_nascimento_filho]`"
                                                :id="`dat_nascimento_filho_${index}`"
                                                x-bind:disabled="bloqueado" autocomplete="off"
                                                :value="filhos[index]?.dat_nascimento_filho ?? ''"
                                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500" />
                                        </div>

                                    </div>
                                </div>
                            </template>
                        </div>
                    </fieldset>
                </div>

                {{-- ===== SAÚDE E RESTRIÇÕES ===== --}}
                <div x-data="{ mostrarRestricoes: {{ old('ind_restricao', $ficha->ind_restricao ?? false) ? 'true' : 'false' }} }">
                    <label
                        class="flex items-start gap-4 p-4 rounded-lg border-2 cursor-pointer transition-colors
                        border-amber-300 bg-amber-50 hover:bg-amber-100
                        dark:border-amber-500 dark:bg-amber-900/20 dark:hover:bg-amber-900/30"
                        aria-describedby="saude-hint">
                        <div class="flex items-center pt-0.5">
                            <input type="hidden" name="ind_restricao" value="0">
                            <input type="checkbox" name="ind_restricao" value="1" x-model="mostrarRestricoes"
                                x-bind:disabled="bloqueado"
                                {{ old('ind_restricao', $ficha->ind_restricao) ? 'checked' : '' }}
                                class="w-5 h-5 rounded border-amber-400 text-amber-500 focus:ring-amber-400">
                        </div>
                        <div>
                            <span class="block font-semibold text-amber-800 dark:text-amber-300">
                                Informações de saúde
                            </span>
                            <span id="saude-hint" class="text-sm text-amber-700 dark:text-amber-400">
                                Há alguma informação sobre saúde que julgam importante sabermos? (alergias,
                                restrições alimentares, necessidades especiais)
                            </span>
                        </div>
                        <x-heroicon-o-heart class="w-6 h-6 text-amber-400 dark:text-amber-500 ml-auto shrink-0"
                            aria-hidden="true" />
                    </label>

                    <div x-show="mostrarRestricoes" x-transition
                        class="mt-3 bg-gray-50 dark:bg-zinc-700 rounded-md p-4" role="region"
                        aria-label="Restrições e Alergias">
                        <h3 class="text-base sm:text-lg font-medium mb-3 text-gray-900 dark:text-gray-100">
                            Restrições e Alergias
                        </h3>
                        <div class="space-y-4">
                            @php
                                $restricoesSelecionadas = $ficha->fichaSaude->pluck('idt_restricao')->toArray();
                                $complementos = $ficha->fichaSaude
                                    ->pluck('txt_complemento', 'idt_restricao')
                                    ->toArray();
                            @endphp

                            @foreach ($restricoes as $restricao)
                                @php
                                    $checked = in_array($restricao->idt_restricao, $restricoesSelecionadas);
                                    $complemento = old(
                                        "complementos.{$restricao->idt_restricao}",
                                        $complementos[$restricao->idt_restricao] ?? '',
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
                                        aria-label="Complemento para {{ $restricao->des_restricao }}"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md text-sm text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-zinc-800" />
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- ===== CONSENTIMENTO ===== --}}
                <fieldset class="bg-white dark:bg-zinc-800 rounded-md shadow p-4 sm:p-6">
                    <legend class="text-lg sm:text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">
                        Consentimento</legend>
                    <div class="space-y-3">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <div class="flex items-center pt-0.5 shrink-0">
                                <input type="hidden" name="ind_consentimento" value="0">
                                <input type="checkbox" name="ind_consentimento" value="1" required
                                    x-bind:disabled="bloqueado"
                                    {{ old('ind_consentimento', $ficha->ind_consentimento) ? 'checked' : '' }}
                                    aria-required="true"
                                    class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 @error('ind_consentimento') border-red-500 @enderror">
                            </div>
                            <span class="text-sm sm:text-base text-gray-800 dark:text-gray-100">
                                Estamos cientes de que <strong>NÃO É PERMITIDO</strong> sair durante o encontro nem
                                levar o celular para o ECC.
                                <span class="text-red-600" aria-hidden="true">*</span><span class="sr-only">(obrigatório)</span>
                            </span>
                        </label>
                        @error('ind_consentimento')
                            <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mt-6">
                        <label for="txt_observacao"
                            class="block font-medium text-gray-700 dark:text-gray-300 mb-1 text-sm sm:text-base">
                            Com sinceridade na resposta (confidencial): quais os maiores problemas / dificuldades e por
                            que vocês querem participar do ECC?
                        </label>
                        <textarea name="txt_observacao" id="txt_observacao" rows="4" maxlength="1000" x-bind:disabled="bloqueado"
                            aria-describedby="txt_observacao_hint" placeholder="Escrevam com sinceridade. Essa resposta é confidencial."
                            class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-sm sm:text-base text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('txt_observacao') border-red-500 @enderror">{{ old('txt_observacao', $ficha->txt_observacao) }}</textarea>
                        @error('txt_observacao')
                            <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                        @enderror
                        <p id="txt_observacao_hint" class="mt-1 text-xs text-gray-500 dark:text-gray-400">Máximo de
                            1000 caracteres</p>
                    </div>
                </fieldset>

                {{-- ===== AÇÕES ===== --}}
                <div class="mt-8 flex flex-col-reverse sm:flex-row gap-3 sm:justify-end">
                    <button type="submit" x-bind:disabled="bloqueado || enviando"
                        class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-2.5 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:outline-none focus-visible:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                        <x-heroicon-o-check class="w-5 h-5 mr-2" aria-hidden="true" />
                        <span x-text="enviando ? 'Salvando...' : 'Salvar'"></span>
                    </button>
                </div>
            </form>

            <fieldset class="bg-white dark:bg-zinc-800 rounded-md shadow p-4 sm:p-6">
                <legend class="text-lg sm:text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">
                    Resumindo</legend>
                    {{-- Cards de informações rápidas --}}
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-3" role="list" aria-label="Informações do evento">
                    <div role="listitem" class="flex items-center gap-2 sm:gap-3 bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-zinc-700 p-2 sm:p-3">
                        <x-heroicon-o-users class="w-5 h-5 text-blue-500 shrink-0" />
                        <div>
                            <p class="text-xs text-gray-500">Público</p>
                            <p class="text-xs sm:text-sm font-medium" x-text="info.faixa"></p>
                        </div>
                    </div>

                    <div role="listitem" class="flex items-center gap-2 sm:gap-3 bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-zinc-700 p-2 sm:p-3">
                        <x-heroicon-o-calendar class="w-5 h-5 text-red-500 shrink-0" />
                        <div>
                            <p class="text-xs text-gray-500">Inscrições até</p>
                            <p class="text-xs sm:text-sm font-medium" x-text="info.data_limite"></p>
                        </div>
                    </div>

                    <div role="listitem" class="flex items-center gap-2 sm:gap-3 bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-zinc-700 p-2 sm:p-3">
                        <x-heroicon-o-ticket class="w-5 h-5 text-green-500 shrink-0" />
                        <div>
                            <p class="text-xs text-gray-500">Vagas</p>
                            <p class="text-xs sm:text-sm font-medium"><span x-text="info.vaga"></span> vagas</p>
                        </div>
                    </div>

                    <div role="listitem" class="flex items-center gap-2 sm:gap-3 bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-zinc-700 p-2 sm:p-3">
                        <x-heroicon-o-currency-dollar class="w-5 h-5 text-yellow-500 shrink-0" />
                        <div>
                            <p class="text-xs text-gray-500">Taxa</p>
                            <p class="text-xs sm:text-sm font-medium">R$ <span x-text="info.valor"></span></p>
                        </div>
                    </div>
                </div>
                {{-- Datas, local, roupas, garrafinha --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-3">
                    <div class="flex items-start gap-3 bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-zinc-700 p-3">
                        <x-heroicon-o-calendar-days class="w-5 h-5 text-purple-500 shrink-0 mt-0.5" aria-hidden="true" />
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Datas</p>
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-100">
                                De <span class="font-semibold" x-text="info.dat_inicio"></span> a <span class="font-semibold" x-text="info.dat_termino"></span> 
                            </p>
                        </div>
                    </div>
                    <div
                        class="flex items-start gap-3 bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-zinc-700 p-3">
                        <x-heroicon-o-map-pin class="w-5 h-5 text-rose-500 shrink-0 mt-0.5" aria-hidden="true" />
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Local</p>
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-100">Paróquia Nossa Senhora do Lago
                            </p>
                        </div>
                    </div>
                    <div
                        class="flex items-start gap-3 bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-zinc-700 p-3">
                        <x-heroicon-o-sparkles class="w-5 h-5 text-indigo-500 shrink-0 mt-0.5" aria-hidden="true" />
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Roupas</p>
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-100">Leves e adequadas para a capela
                            </p>
                        </div>
                    </div>
                    <div
                        class="flex items-start gap-3 bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-zinc-700 p-3">
                        <x-heroicon-o-beaker class="w-5 h-5 text-cyan-500 shrink-0 mt-0.5" aria-hidden="true" />
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Lembrete</p>
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-100">Leve sua garrafinha</p>
                        </div>
                    </div>
                </div>
            </fieldset>
        @else
            <div role="status" aria-live="polite">
                <div
                    class="flex flex-col items-center justify-center text-center p-8 sm:p-10 mt-4 bg-white dark:bg-zinc-800 rounded-xl shadow border border-dashed border-gray-300 dark:border-zinc-600">
                    <x-heroicon-o-user-group class="w-12 h-12 text-gray-400 dark:text-gray-500 mb-4"
                        aria-hidden="true" />
                    <p class="text-base sm:text-lg font-medium text-gray-600 dark:text-gray-300">Nenhum evento
                        disponível no momento</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Em breve abriremos novas inscrições</p>
                </div>
            </div>
        @endif
        </div>
    </section>
</x-layouts.public>