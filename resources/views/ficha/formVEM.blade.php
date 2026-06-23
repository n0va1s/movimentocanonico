<x-layouts.public :title="'Ficha do VEM'">
    <section class="px-4 py-6 w-full max-w-3xl mx-auto" aria-labelledby="page-title">
        @php
            $eventosJson = json_encode((object) $eventos->mapWithKeys(fn($e) => [
                (string)$e->idt_evento => [
                    'faixa' => $e->tip_faixa_etaria?->label() ?? 'Livre',
                    'data_limite' => $e->dat_limite_inscricao?->format('d/m/Y') ?? '--/--/----',
                    'vaga' => $e->qtd_vaga,
                    'valor' => number_format($e->val_venista, 2, ',', '.')
                ]
            ])->all());
        @endphp

        <div x-data="{
                bloqueado: {{ $ficha->ind_aprovado ? 'true' : 'false' }},
                enviando: false,
                selectedEventoId: '{{ old('idt_evento', $ficha->idt_evento ?? '') }}',
                eventosData: {{ $eventosJson }},

                get eventoSelecionado() {
                    const id = String(this.selectedEventoId);
                    const e = this.eventosData[id];
                    if (!e) return { faixa: '---', data_limite: '---', vaga: 0, valor: '0,00' };
                    return {
                        faixa:       e.faixa,
                        data_limite: e.data_limite,
                        vaga:        e.vaga,
                        valor:       e.valor,
                    };
                },
                buscarPorCpf() {
                    let cpf = document.getElementById('num_cpf_candidato').value.replace(/\D/g, '');
                    if (cpf.length === 11) {
                        fetch(`/pessoas/${cpf}/busca/`)
                            .then(response => {
                                if (response.ok) return response.json();
                                throw new Error('Not found');
                            })
                            .then(data => {
                                document.getElementById('nom_candidato').value = data.nom_pessoa || '';
                                document.getElementById('nom_apelido').value = data.nom_apelido || '';
                                if(data.dat_nascimento) document.getElementById('dat_nascimento').value = data.dat_nascimento.split('T')[0];
                                document.getElementById('tel_candidato').value = data.tel_pessoa || '';
                                document.getElementById('eml_candidato').value = data.eml_pessoa || '';
                                document.getElementById('des_endereco').value = data.des_endereco || '';
                                
                                if (data.tam_camiseta) document.getElementById('tam_camiseta').value = data.tam_camiseta;
                                if (data.tip_genero) document.getElementById('tip_genero').value = data.tip_genero;
                            })
                            .catch(error => console.log('Candidato ainda não existe, preenchimento manual necessário.'));
                    }
                }
            }">

        {{-- ===== CABEÇALHO ===== --}}
        <div class="mb-6 space-y-4">
            <div>
                <h1 id="page-title" class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-gray-100">Ficha do VEM</h1>
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
                                <li>• Pais trabalhando no encontro</li>
                                <li>• Preferência pelos mais velhos</li>
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

            <div
                class="bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 rounded-lg p-4 flex gap-3">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 shrink-0 mt-0.5" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div>
                    <p class="text-sm font-semibold text-blue-800 dark:text-blue-300">Aviso sobre a participação</p>
                    <p class="text-xs text-blue-700 dark:text-blue-400 mt-1 leading-relaxed">
                        A realização desta pré-inscrição <strong>não garante a participação</strong> automática no
                        encontro. Todas as fichas passarão por um processo de seleção baseado nos critérios acima, e
                        a confirmação final ocorrerá somente após o contato e a confirmação dos dados.
                    </p>
                </div>
            </div>
        </div>
        
        {{-- Botão voltar (admin) --}}
        @if (Auth::user()?->isAdmin())
            <div class="flex justify-end mb-4">
                <a href="{{ route('vem.index') }}"
                    class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 dark:hover:bg-green-500 focus:ring-2 focus:ring-green-500 focus:outline-none focus-visible:ring-offset-2"
                    aria-label="Voltar para a lista de fichas">
                    <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" aria-hidden="true" />
                    Fichas
                </a>
            </div>
        @endif

        @if (Auth::user()?->hasRole('admin', 'espec', 'coord') && $ficha->exists)
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
                            <form method="POST" action="{{ route('vem.situacao', $ficha->idt_ficha) }}" class="inline">
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
                @if(Auth::user()?->hasRole('admin', 'espec'))
                    <div class="mt-4 border-t border-gray-100 dark:border-zinc-700 pt-4">
                        <form method="POST" action="{{ route('fichas.designar-visitador', $ficha->idt_ficha) }}">
                            @csrf
                            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
                                <label for="idt_pessoa_visitacao" class="text-xs font-semibold text-gray-500 dark:text-zinc-400 uppercase tracking-wider">Responsável pela Visitação:</label>
                                <div class="flex items-center gap-2 w-full sm:w-auto">
                                    <select name="idt_pessoa_visitacao" id="idt_pessoa_visitacao" 
                                        class="text-xs rounded-md border border-gray-300 dark:border-zinc-600 dark:bg-zinc-800 text-gray-900 dark:text-gray-100 px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500 w-full sm:max-w-md">
                                        <option value="">Sem responsável designado</option>
                                        @if(isset($visitadores))
                                            @foreach($visitadores as $v)
                                                @php
                                                    $nomeLabel = $v->nom_pessoa;
                                                    if ($v->parceiro) {
                                                        $nomeLabel .= ' & ' . $v->parceiro->nom_pessoa;
                                                    }
                                                    if ($v->des_endereco) {
                                                        $nomeLabel .= ' — ' . $v->des_endereco;
                                                    }
                                                @endphp
                                                <option value="{{ $v->idt_pessoa }}" @selected($ficha->idt_pessoa_visitacao === $v->idt_pessoa)>
                                                    {{ $nomeLabel }}
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
            <form method="POST" 
                enctype="multipart/form-data"
                @submit="setTimeout(() => enviando = true, 50)"
                action="{{ $ficha->exists ? route('vem.update', $ficha) : route('vem.store') }}"
                class="space-y-6" novalidate>
                @csrf
                @if ($ficha->exists) @method('PUT') @endif

                {{-- ===== DADOS DO PARTICIPANTE ===== --}}
                <fieldset class="bg-white dark:bg-zinc-800 rounded-md shadow p-4 sm:p-6">
                    <legend class="text-lg sm:text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">Dados do
                        Participante</legend>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                        
                        {{-- Foto --}}
                        <div class="flex flex-col items-center gap-3" x-data="{ photoPreview: '{{ $ficha->foto?->med_foto ? Storage::url($ficha->foto->med_foto) : '' }}' }">
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
                                <label for="med_foto"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 text-center">
                                    Foto
                                </label>
                                <input type="file" name="med_foto" id="med_foto" accept="image/*"
                                    x-bind:disabled="bloqueado"
                                    @change="
                                        const file = $event.target.files[0];
                                        if (file) {
                                            photoPreview = URL.createObjectURL(file);
                                        } else {
                                            photoPreview = '{{ $ficha->foto?->med_foto ? Storage::url($ficha->foto->med_foto) : '' }}';
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
                            <label for="num_cpf_candidato"
                                class="block font-medium text-gray-700 dark:text-gray-300 mb-1 text-sm sm:text-base">
                                CPF
                            </label>
                            <input type="text" name="num_cpf_candidato" id="num_cpf_candidato"
                                x-bind:disabled="bloqueado" maxlength="14" autocomplete="off"
                                value="{{ old('num_cpf_candidato', $ficha->num_cpf_candidato) }}"
                                @blur="buscarPorCpf()"
                                placeholder="000.000.000-00"
                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('num_cpf_candidato') border-red-500 @enderror" />
                            @error('num_cpf_candidato')
                                <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

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
                            <label for="idt_evento" class="block font-medium text-gray-700 dark:text-gray-300 mb-1 text-sm sm:text-base">Evento</label>
                            <select name="idt_evento" id="idt_evento"
                                x-model="selectedEventoId"
                                x-bind:disabled="bloqueado"
                                required
                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                                <option value="">Selecione um evento</option>
                                @foreach ($eventos as $evento)
                                    <option value="{{ $evento->idt_evento }}">
                                        {{ $evento->des_evento }} - {{ $evento->dat_inicio->format('d/m/Y') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Sexo --}}
                        <div>
                            <label for="tip_genero"
                                class="block font-medium text-gray-700 dark:text-gray-300 mb-1 text-sm sm:text-base">
                                Sexo <span class="text-red-600" aria-hidden="true">*</span><span
                                    class="sr-only">(obrigatório)</span>
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

                        {{-- Nome completo --}}
                        <div>
                            <label for="nom_candidato"
                                class="block font-medium text-gray-700 dark:text-gray-300 mb-1 text-sm sm:text-base">
                                Nome completo <span class="text-red-600" aria-hidden="true">*</span><span
                                    class="sr-only">(obrigatório)</span>
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
                            <input type="text" name="nom_apelido" id="nom_apelido" x-bind:disabled="bloqueado"
                                maxlength="100" value="{{ old('nom_apelido', $ficha->nom_apelido) }}"
                                placeholder="Como gosta de ser chamado"
                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('nom_apelido') border-red-500 @enderror" />
                            @error('nom_apelido')
                                <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Data de Nascimento --}}
                        <div>
                            <label for="dat_nascimento"
                                class="block font-medium text-gray-700 dark:text-gray-300 mb-1 text-sm sm:text-base">
                                Data de Nascimento <span class="text-red-600" aria-hidden="true">*</span><span
                                    class="sr-only">(obrigatório)</span>
                            </label>
                            <input type="date" name="dat_nascimento" id="dat_nascimento"
                                x-bind:disabled="bloqueado" required autocomplete="bday"
                                value="{{ old('dat_nascimento', $ficha->getDataNascimentoFormatada()) }}"
                                aria-required="true"
                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('dat_nascimento') border-red-500 @enderror" />
                            @error('dat_nascimento')
                                <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Whatsapp --}}
                        <div>
                            <label for="tel_candidato"
                                class="block font-medium text-gray-700 dark:text-gray-300 mb-1 text-sm sm:text-base">
                                WhatsApp
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
                                Email <span class="text-red-600" aria-hidden="true">*</span><span
                                    class="sr-only">(obrigatório)</span>
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

                        {{-- Endereço --}}
                        <div>
                            <label for="des_endereco"
                                class="block font-medium text-gray-700 dark:text-gray-300 mb-1 text-sm sm:text-base">
                                Endereço <span class="text-red-600" aria-hidden="true">*</span><span class="sr-only">(obrigatório)</span>
                            </label>
                            <input type="text" name="des_endereco" id="des_endereco" x-bind:disabled="bloqueado"
                                required maxlength="500" autocomplete="street-address" aria-required="true"
                                value="{{ old('des_endereco', $ficha->des_endereco) }}"
                                placeholder="Rua, número, bairro, cidade"
                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('des_endereco') border-red-500 @enderror" />
                            @error('des_endereco')
                                <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Tamanho da Camiseta --}}
                        <div>
                            <label for="tam_camiseta"
                                class="block font-medium text-gray-700 dark:text-gray-300 mb-1 text-sm sm:text-base">
                                Tamanho da Camiseta <span class="text-red-600" aria-hidden="true">*</span><span
                                    class="sr-only">(obrigatório)</span>
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

                        {{-- Como soube do evento --}}
                        <div>
                            <label for="tip_como_soube"
                                class="block font-medium text-gray-700 dark:text-gray-300 mb-1 text-sm sm:text-base">
                                Como soube do evento
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

                    </div>
                </fieldset>

                {{-- ===== RESPONSÁVEIS ===== --}}
                <fieldset class="bg-white dark:bg-zinc-800 rounded-md shadow p-4 sm:p-6"
                    x-data="{
                        nomMae: '{{ old('nom_mae', optional($ficha->fichaVem)->nom_mae) }}',
                        nomPai: '{{ old('nom_pai', optional($ficha->fichaVem)->nom_pai) }}',
                        nomResp: '{{ old('nom_responsavel', optional($ficha->fichaVem)->nom_responsavel) }}',
                        get algumPreenchido() {
                            return this.nomMae.trim() !== '' || this.nomPai.trim() !== '' || this.nomResp.trim() !== '';
                        }
                    }">
                    <legend class="text-lg sm:text-xl font-semibold text-gray-900 dark:text-gray-100 mb-1">Responsáveis</legend>

                    {{-- Aviso de obrigatoriedade --}}
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                        Preencha ao menos um responsável: <strong>mãe</strong>, <strong>pai</strong> ou <strong>responsável</strong>.
                        Também indique com quem devemos falar em caso de necessidade.
                    </p>

                    {{-- Erro geral de responsáveis --}}
                    @error('responsaveis')
                        <div class="mb-4 flex items-center gap-2 rounded-md bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 px-4 py-3" role="alert">
                            <x-heroicon-o-exclamation-circle class="w-5 h-5 text-red-500 shrink-0" aria-hidden="true" />
                            <p class="text-sm text-red-700 dark:text-red-400">{{ $message }}</p>
                        </div>
                    @enderror

                    {{-- Alerta dinâmico (Alpine) --}}
                    <div x-show="!algumPreenchido" x-transition
                        class="mb-4 flex items-center gap-2 rounded-md bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 px-4 py-3"
                        role="alert" aria-live="polite">
                        <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-amber-500 shrink-0" aria-hidden="true" />
                        <p class="text-sm text-amber-700 dark:text-amber-400">Preencha o nome de ao menos um responsável.</p>
                    </div>

                    <div class="space-y-6">

                        {{-- Mãe --}}
                        <div>
                            <p class="font-medium text-gray-700 dark:text-gray-300 mb-2 text-sm sm:text-base">Mãe</p>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
                                <div>
                                    <label for="nom_mae"
                                        class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Nome</label>
                                    <input type="text" name="nom_mae" id="nom_mae" x-bind:disabled="bloqueado"
                                        x-model="nomMae"
                                        maxlength="255" autocomplete="off" placeholder="Nome completo"
                                        class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('nom_mae') border-red-500 @enderror" />
                                    @error('nom_mae')
                                        <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="tel_mae"
                                        class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Telefone</label>
                                    <input type="tel" name="tel_mae" id="tel_mae" x-bind:disabled="bloqueado"
                                        value="{{ old('tel_mae', optional($ficha->fichaVem)->tel_mae) }}"
                                        maxlength="20" autocomplete="off" placeholder="(61) 90000-0000"
                                        class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('tel_mae') border-red-500 @enderror" />
                                    @error('tel_mae')
                                        <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="eml_mae"
                                        class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Email</label>
                                    <input type="email" name="eml_mae" id="eml_mae" x-bind:disabled="bloqueado"
                                        value="{{ old('eml_mae', optional($ficha->fichaVem)->eml_mae) }}"
                                        maxlength="50" autocomplete="off" placeholder="email@exemplo.com"
                                        class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('eml_mae') border-red-500 @enderror" />
                                    @error('eml_mae')
                                        <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        {{-- Pai --}}
                        <div class="border-t border-gray-100 dark:border-zinc-700 pt-5">
                            <p class="font-medium text-gray-700 dark:text-gray-300 mb-2 text-sm sm:text-base">Pai</p>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
                                <div>
                                    <label for="nom_pai"
                                        class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Nome</label>
                                    <input type="text" name="nom_pai" id="nom_pai" x-bind:disabled="bloqueado"
                                        x-model="nomPai"
                                        maxlength="255" autocomplete="off" placeholder="Nome completo"
                                        class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('nom_pai') border-red-500 @enderror" />
                                    @error('nom_pai')
                                        <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="tel_pai"
                                        class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Telefone</label>
                                    <input type="tel" name="tel_pai" id="tel_pai" x-bind:disabled="bloqueado"
                                        value="{{ old('tel_pai', optional($ficha->fichaVem)->tel_pai) }}"
                                        maxlength="20" autocomplete="off" placeholder="(61) 90000-0000"
                                        class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('tel_pai') border-red-500 @enderror" />
                                    @error('tel_pai')
                                        <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="eml_pai"
                                        class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Email</label>
                                    <input type="email" name="eml_pai" id="eml_pai" x-bind:disabled="bloqueado"
                                        value="{{ old('eml_pai', optional($ficha->fichaVem)->eml_pai) }}"
                                        maxlength="50" autocomplete="off" placeholder="email@exemplo.com"
                                        class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('eml_pai') border-red-500 @enderror" />
                                    @error('eml_pai')
                                        <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        {{-- Responsável --}}
                        <div class="border-t border-gray-200 dark:border-zinc-600 pt-5">
                            <p class="font-medium text-gray-700 dark:text-gray-300 mb-1 text-sm sm:text-base">
                                Responsável
                                <span class="text-xs font-normal text-gray-500 dark:text-gray-400 ml-1">(caso não more com os pais)</span>
                            </p>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4 mt-2">
                                <div>
                                    <label for="nom_responsavel"
                                        class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Nome</label>
                                    <input type="text" name="nom_responsavel" id="nom_responsavel"
                                        x-bind:disabled="bloqueado"
                                        x-model="nomResp"
                                        maxlength="150" autocomplete="off" placeholder="Nome completo"
                                        class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('nom_responsavel') border-red-500 @enderror" />
                                    @error('nom_responsavel')
                                        <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="tel_responsavel"
                                        class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Telefone</label>
                                    <input type="tel" name="tel_responsavel" id="tel_responsavel"
                                        x-bind:disabled="bloqueado"
                                        value="{{ old('tel_responsavel', optional($ficha->fichaVem)->tel_responsavel) }}"
                                        maxlength="15" autocomplete="off" placeholder="(61) 90000-0000"
                                        class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('tel_responsavel') border-red-500 @enderror" />
                                    @error('tel_responsavel')
                                        <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="eml_responsavel"
                                        class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Email</label>
                                    <input type="email" name="eml_responsavel" id="eml_responsavel"
                                        x-bind:disabled="bloqueado"
                                        value="{{ old('eml_responsavel', optional($ficha->fichaVem)->eml_responsavel) }}"
                                        maxlength="50" autocomplete="off" placeholder="email@exemplo.com"
                                        class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('eml_responsavel') border-red-500 @enderror" />
                                    @error('eml_responsavel')
                                        <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        {{-- Falar com | Onde estuda | Mora com quem --}}
                        <div class="border-t border-gray-200 dark:border-zinc-600 pt-5 grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-6">
                            <div>
                                <label for="idt_falar_com"
                                    class="block font-medium text-gray-700 dark:text-gray-300 mb-1 text-sm sm:text-base">
                                    Falar com <span class="text-red-600" aria-hidden="true">*</span><span
                                        class="sr-only">(obrigatório)</span>
                                </label>
                                <select name="idt_falar_com" id="idt_falar_com" required x-bind:disabled="bloqueado"
                                    aria-required="true"
                                    class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('idt_falar_com') border-red-500 @enderror">
                                    <option value="" disabled {{ old('idt_falar_com', optional($ficha->fichaVem)->idt_falar_com) ? '' : 'selected' }}>Selecione</option>
                                    @foreach ($responsaveis as $responsavel)
                                        <option value="{{ $responsavel->idt_responsavel }}"
                                            {{ old('idt_falar_com', optional($ficha->fichaVem)->idt_falar_com) == $responsavel->idt_responsavel ? 'selected' : '' }}>
                                            {{ $responsavel->des_responsavel }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('idt_falar_com')
                                    <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="des_onde_estuda"
                                    class="block font-medium text-gray-700 dark:text-gray-300 mb-1 text-sm sm:text-base">
                                    Onde estuda? <span class="text-red-600" aria-hidden="true">*</span><span
                                        class="sr-only">(obrigatório)</span>
                                </label>
                                <input type="text" name="des_onde_estuda" id="des_onde_estuda"
                                    x-bind:disabled="bloqueado"
                                    value="{{ old('des_onde_estuda', optional($ficha->fichaVem)->des_onde_estuda) }}"
                                    required maxlength="255" placeholder="Nome da escola/universidade"
                                    aria-required="true"
                                    class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('des_onde_estuda') border-red-500 @enderror" />
                                @error('des_onde_estuda')
                                    <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="des_mora_quem"
                                    class="block font-medium text-gray-700 dark:text-gray-300 mb-1 text-sm sm:text-base">
                                    Mora com quem? <span class="text-red-600" aria-hidden="true">*</span><span
                                        class="sr-only">(obrigatório)</span>
                                </label>
                                <input type="text" name="des_mora_quem" id="des_mora_quem"
                                    x-bind:disabled="bloqueado"
                                    value="{{ old('des_mora_quem', optional($ficha->fichaVem)->des_mora_quem) }}"
                                    required maxlength="255" placeholder="Ex: Pais, avós, sozinho..."
                                    aria-required="true"
                                    class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('des_mora_quem') border-red-500 @enderror" />
                                @error('des_mora_quem')
                                    <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                    </div>
                </fieldset>

                {{-- ===== FÉ ===== --}}
                <fieldset class="bg-white dark:bg-zinc-800 rounded-md shadow p-4 sm:p-6">
                    <legend class="text-lg sm:text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">Outras
                        informações</legend>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">

                        {{-- Sacramentos --}}
                        <div>
                            <p class="font-medium text-gray-700 dark:text-gray-300 mb-2 text-sm sm:text-base"
                                id="sacramentos-label">
                                Sacramentos
                            </p>
                            <div class="flex flex-col gap-2" role="group" aria-labelledby="sacramentos-label">
                                <label class="flex items-center gap-2.5 py-1.5 cursor-pointer">
                                    <input type="hidden" name="ind_catolico" value="0">
                                    <input type="checkbox" name="ind_catolico" value="1"
                                        x-bind:disabled="bloqueado"
                                        {{ old('ind_catolico', $ficha->ind_catolico) ? 'checked' : '' }}
                                        class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="text-sm text-gray-800 dark:text-gray-100">É católico</span>
                                </label>
                                <label class="flex items-center gap-2.5 py-1.5 cursor-pointer">
                                    <input type="hidden" name="ind_batizado" value="0">
                                    <input type="checkbox" name="ind_batizado" value="1"
                                        x-bind:disabled="bloqueado"
                                        {{ old('ind_batizado', optional($ficha->fichaVem)->ind_batizado) ? 'checked' : '' }}
                                        class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="text-sm text-gray-800 dark:text-gray-100">Foi batizado</span>
                                </label>
                                <label class="flex items-center gap-2.5 py-1.5 cursor-pointer">
                                    <input type="hidden" name="ind_primeira_comunhao" value="0">
                                    <input type="checkbox" name="ind_primeira_comunhao" value="1"
                                        x-bind:disabled="bloqueado"
                                        {{ old('ind_primeira_comunhao', optional($ficha->fichaVem)->ind_primeira_comunhao) ? 'checked' : '' }}
                                        class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="text-sm text-gray-800 dark:text-gray-100">Fez primeira comunhão</span>
                                </label>
                                <label class="flex items-center gap-2.5 py-1.5 cursor-pointer">
                                    <input type="hidden" name="ind_crismado" value="0">
                                    <input type="checkbox" name="ind_crismado" value="1"
                                        x-bind:disabled="bloqueado"
                                        {{ old('ind_crismado', optional($ficha->fichaVem)->ind_crismado) ? 'checked' : '' }}
                                        class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="text-sm text-gray-800 dark:text-gray-100">Foi crismado</span>
                                </label>
                            </div>
                        </div>

                        {{-- Paróquia + Instrumento --}}
                        <div class="space-y-4">
                            <div>
                                <label for="nom_paroquia"
                                    class="block font-medium text-gray-700 dark:text-gray-300 mb-1 text-sm sm:text-base">
                                    Paróquia que frequenta
                                </label>
                                <input type="text" name="nom_paroquia" id="nom_paroquia"
                                    x-bind:disabled="bloqueado"
                                    value="{{ old('nom_paroquia', optional($ficha->fichaVem)->nom_paroquia) }}"
                                    maxlength="150" placeholder="Nome da paróquia"
                                    class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('nom_paroquia') border-red-500 @enderror" />
                                @error('nom_paroquia')
                                    <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <p class="font-medium text-gray-700 dark:text-gray-300 mb-1 text-sm sm:text-base"
                                    id="instrumento-label">
                                    Toca algum instrumento?
                                </p>
                                <label class="flex items-center gap-2.5 py-1.5 cursor-pointer">
                                    <input type="hidden" name="ind_toca_instrumento" value="0">
                                    <input type="checkbox" name="ind_toca_instrumento" value="1"
                                        x-bind:disabled="bloqueado"
                                        {{ old('ind_toca_instrumento', optional($ficha->fichaVem)->ind_toca_instrumento) ? 'checked' : '' }}
                                        class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                        aria-labelledby="instrumento-label">
                                    <span class="text-sm text-gray-800 dark:text-gray-100">Sim</span>
                                </label>
                            </div>
                        </div>

                    </div>
                </fieldset>

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
                                Há alguma informação sobre sua saúde que julga importante sabermos? (alergias,
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
                                        x-model="texto" {{-- Sincroniza o valor do input com a variável 'texto' --}}
                                        @input="if(texto.trim().length > 0) selecionado = true" {{-- Marca o checkbox ao digitar --}}
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
                                Estou ciente de que <strong>NÃO É PERMITIDO</strong> sair durante o encontro nem levar o
                                celular para o VEM.
                                <span class="text-red-600" aria-hidden="true">*</span><span
                                    class="sr-only">(obrigatório)</span>
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
                            que você quer participar do VEM?
                        </label>
                        <textarea name="txt_observacao" id="txt_observacao" rows="4" maxlength="1000" x-bind:disabled="bloqueado"
                            aria-describedby="txt_observacao_hint" placeholder="Escreva com sinceridade. Essa resposta é confidencial."
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
                            <p class="text-xs sm:text-sm font-medium" x-text="eventoSelecionado.faixa"></p>
                        </div>
                    </div>

                    <div role="listitem" class="flex items-center gap-2 sm:gap-3 bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-zinc-700 p-2 sm:p-3">
                        <x-heroicon-o-calendar class="w-5 h-5 text-red-500 shrink-0" />
                        <div>
                            <p class="text-xs text-gray-500">Inscrições até</p>
                            <p class="text-xs sm:text-sm font-medium" x-text="eventoSelecionado.data_limite"></p>
                        </div>
                    </div>

                    <div role="listitem" class="flex items-center gap-2 sm:gap-3 bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-zinc-700 p-2 sm:p-3">
                        <x-heroicon-o-ticket class="w-5 h-5 text-green-500 shrink-0" />
                        <div>
                            <p class="text-xs text-gray-500">Vagas</p>
                            <p class="text-xs sm:text-sm font-medium"><span x-text="eventoSelecionado.vaga"></span> vagas</p>
                        </div>
                    </div>

                    <div role="listitem" class="flex items-center gap-2 sm:gap-3 bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-zinc-700 p-2 sm:p-3">
                        <x-heroicon-o-currency-dollar class="w-5 h-5 text-yellow-500 shrink-0" />
                        <div>
                            <p class="text-xs text-gray-500">Taxa</p>
                            <p class="text-xs sm:text-sm font-medium">R$ <span x-text="eventoSelecionado.valor"></span></p>
                        </div>
                    </div>
                </div>
                {{-- Datas, local, roupas, garrafinha --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-3">
                    <div
                        class="flex items-start gap-3 bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-zinc-700 p-3">
                        <x-heroicon-o-calendar-days class="w-5 h-5 text-purple-500 shrink-0 mt-0.5" aria-hidden="true" />
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Datas</p>
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-100">Pré-VEM: <span
                                    class="font-semibold">13/06/2026</span></p>
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-100">VEM: <span
                                    class="font-semibold">20 e 21/06/2026</span></p>
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
