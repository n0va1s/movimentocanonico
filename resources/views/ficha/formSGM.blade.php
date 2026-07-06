<x-layouts.public :title="'Ficha do Segue-me'">
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
                selectedEventoId: '{{ old('idt_evento', $ficha->idt_evento ?? ($eventos->count() === 1 ? $eventos->first()->idt_evento : '')) }}',
                eventosData: {{ $eventosJson }},
                get info() {
                    return this.eventosData[String(this.selectedEventoId)] || { faixa: '---', data_limite: '---', vaga: 0, valor: '0,00' };
                }
            }">

        {{-- ===== BREADCRUMBS ===== --}}
        @php
            $previousUrl = url()->previous();
            $isMinhasFichas = str_contains($previousUrl, 'minhas-fichas');
            $isGerenciamento = str_contains($previousUrl, 'gerenciamento');
            
            // Fallback baseado no perfil se a origem for desconhecida ou houver refresh (F5)
            if (!$isMinhasFichas && !$isGerenciamento && Auth::user()) {
                if (Auth::user()->hasRole('admin', 'espec', 'coord')) {
                    $isGerenciamento = true;
                } else {
                    $isMinhasFichas = true;
                }
            }
        @endphp

        @if (Auth::user())
            <flux:breadcrumbs class="mb-6">
                <flux:breadcrumbs.item href="{{ route('home') }}">Início</flux:breadcrumbs.item>
                
                @if ($isGerenciamento)
                    @if ($ficha->idt_evento)
                        <flux:breadcrumbs.item href="{{ route('eventos.gerenciamento', $ficha->idt_evento) }}">Gerenciamento</flux:breadcrumbs.item>
                    @else
                        <flux:breadcrumbs.item href="{{ route('eventos.index') }}">Eventos</flux:breadcrumbs.item>
                    @endif
                @else
                    <flux:breadcrumbs.item href="{{ route('minhas-fichas.index') }}">Minhas Fichas</flux:breadcrumbs.item>
                @endif
                
                <flux:breadcrumbs.item>Ficha do Segue-me</flux:breadcrumbs.item>
            </flux:breadcrumbs>
        @endif

        {{-- ===== CABEÇALHO ===== --}}
        <div class="mb-6 space-y-4">
            <div class="mb-6">
                <flux:heading size="xl" class="text-indigo-900 dark:text-indigo-100 font-bold tracking-tight mb-1">Ficha do Segue-me</flux:heading>
                <p class="text-gray-700 dark:text-gray-500 mt-1">Paróquia Nossa Senhora do Lago</p>
            </div>

            {{-- Cards de informações rápidas --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-3" role="list" aria-label="Informações do evento">
                <div role="listitem"
                    class="flex items-center gap-2 sm:gap-3 bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-zinc-700 p-2 sm:p-3">
                    <x-heroicon-o-users class="w-5 h-5 text-blue-500 shrink-0" aria-hidden="true" />
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Público</p>
                        <p class="text-xs sm:text-sm font-medium text-gray-800 dark:text-gray-100" x-text="info.faixa">16 a 23 anos</p>
                    </div>
                </div>
                <div role="listitem"
                    class="flex items-center gap-2 sm:gap-3 bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-zinc-700 p-2 sm:p-3">
                    <x-heroicon-o-calendar class="w-5 h-5 text-red-500 shrink-0" aria-hidden="true" />
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Inscrições até</p>
                        <p class="text-xs sm:text-sm font-medium text-gray-800 dark:text-gray-100" x-text="info.data_limite">12/07/2026</p>
                    </div>
                </div>
                <div role="listitem"
                    class="flex items-center gap-2 sm:gap-3 bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-zinc-700 p-2 sm:p-3">
                    <x-heroicon-o-ticket class="w-5 h-5 text-green-500 shrink-0" aria-hidden="true" />
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Vagas</p>
                        <p class="text-xs sm:text-sm font-medium text-gray-800 dark:text-gray-100"><span x-text="info.vaga">60</span> vagas</p>
                    </div>
                </div>
                <div role="listitem"
                    class="flex items-center gap-2 sm:gap-3 bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-zinc-700 p-2 sm:p-3">
                    <x-heroicon-o-currency-dollar class="w-5 h-5 text-yellow-500 shrink-0" aria-hidden="true" />
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Taxa</p>
                        <p class="text-xs sm:text-sm font-medium text-gray-800 dark:text-gray-100">R$ <span x-text="info.valor">120,00</span></p>
                    </div>
                </div>
            </div>

            {{-- Datas, local, roupas, foco --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-3">
                <div
                    class="flex items-start gap-3 bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-zinc-700 p-3">
                    <x-heroicon-o-calendar-days class="w-5 h-5 text-purple-500 shrink-0 mt-0.5" aria-hidden="true" />
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Datas</p>
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-100">
                            <span class="font-semibold">28 a 30/08/2026</span>
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
                    <x-heroicon-o-device-phone-mobile class="w-5 h-5 text-cyan-500 shrink-0 mt-0.5"
                        aria-hidden="true" />
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Foco</p>
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-100">Deixe o celular de lado e viva o
                            momento</p>
                    </div>
                </div>
            </div>

            {{-- Como funciona + Aviso --}}
            <div class="space-y-4">
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
                                    atento ao seu WhatsApp e/ou ligações</p>
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
                                    <li>• Moradores do Lago Norte e regiões próximas</li>
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
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Contato por ligação e/ou
                                    WhatsApp</p>
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
        </div>



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
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-bold {{ $style['light'] }} shadow-sm">
                                <x-heroicon-s-check class="w-4 h-4" />
                                {{ $situacao->label() }}
                                @if($situacao->mail()[0] === 'Sim')
                                    <x-heroicon-o-envelope class="w-4 h-4" title="Envia e-mail ao ser alterado para esta situação" />
                                @endif
                            </span>
                        @else
                            <form method="POST" action="{{ route('sgm.situacao', $ficha->idt_ficha) }}" class="inline">
                                @csrf
                                <input type="hidden" name="tip_situacao" value="{{ $situacao->value }}">
                                <button type="submit" 
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-semibold border transition-all duration-200 cursor-pointer hover:text-white {{ $style['light'] }} {{ $style['hover'] }}">
                                    @if($situacao->value === 'N')
                                        <x-heroicon-o-document-text class="w-4 h-4" />
                                    @elseif($situacao->value === 'S')
                                        <x-heroicon-o-check-circle class="w-4 h-4" />
                                    @elseif($situacao->value === 'E')
                                        <x-heroicon-o-paper-airplane class="w-4 h-4" />
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
                                    @if($situacao->mail()[0] === 'Sim')
                                        <x-heroicon-o-envelope class="w-4 h-4" title="Envia e-mail ao ser alterado para esta situação" />
                                    @endif
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
                                                <option value="{{ $v->idt_pessoa }}" @selected($ficha->idt_pessoa_visitacao == $v->idt_pessoa || ($v->idt_parceiro && $ficha->idt_pessoa_visitacao == $v->idt_parceiro))>
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

        {{-- ===== FORMULÁRIO ===== --}}
        @if ($eventos->count() > 0)

            <form method="POST" enctype="multipart/form-data" @submit="setTimeout(() => enviando = true, 50)"
                action="{{ $ficha->exists ? route('sgm.update', $ficha) : route('sgm.store') }}" class="space-y-8">
                @csrf
                @if ($ficha->exists) @method('PUT') @endif

                {{-- ===== DADOS BÁSICOS ===== --}}
                <div class="bg-white dark:bg-zinc-800 rounded-md shadow p-4 sm:p-6">
                    <h2 class="text-xl font-semibold mb-4 text-gray-900 dark:text-gray-100">Dados Básicos</h2>
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
                                CPF <span class="text-red-600" aria-hidden="true">*</span><span
                                    class="sr-only">(obrigatório)</span>
                            </label>
                            <input type="text" name="num_cpf_candidato" id="num_cpf_candidato"
                                x-bind:disabled="bloqueado" required maxlength="14" autocomplete="off"
                                value="{{ old('num_cpf_candidato', $ficha->num_cpf_candidato) }}"
                                placeholder="000.000.000-00" aria-required="true"
                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('num_cpf_candidato') border-red-500 @enderror" />
                            @error('num_cpf_candidato')
                                <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                        {{-- Movimento --}}
                        <div>
                            <label for="idt_movimento"
                                class="block font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Movimento
                            </label>
                            <select id="idt_movimento" name="idt_movimento" disabled
                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('idt_movimento') border-red-500 @enderror">
                                <option value="">Selecione um movimento</option>
                                @foreach ($movimentos as $movimento)
                                    <option value="{{ $movimento->idt_movimento }}"
                                        {{ old('idt_movimento', $movimentopadrao) == $movimento->idt_movimento ? 'selected' : '' }}>
                                        {{ $movimento->nom_movimento }} ({{ $movimento->des_sigla }})
                                    </option>
                                @endforeach
                            </select>
                            @error('idt_movimento')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Escolha o movimento para filtrar as opções de evento.
                            </p>
                        </div>

                        {{-- Evento --}}
                        <div>
                            <label for="idt_evento" class="block font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Evento <span class="text-red-600">*</span>
                            </label>
                            <select name="idt_evento" id="idt_evento" required x-bind:disabled="bloqueado" x-model="selectedEventoId"
                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('idt_evento') border-red-500 @enderror">
                                <option class="dark:bg-zinc-700" value="">Selecione um evento</option>
                                @foreach ($eventos as $evento)
                                    <option class="dark:bg-zinc-700" value="{{ $evento->idt_evento }}"
                                        {{ old('idt_evento', $ficha->idt_evento ?? ($eventos->count() === 1 ? $eventos->first()->idt_evento : null)) == $evento->idt_evento ? 'selected' : '' }}>
                                        {{ $evento->des_evento }} - {{ $evento->dat_inicio->format('d/m/Y') }}
                                    </option>
                                @endforeach
                            </select>
                            @error('idt_evento')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Sexo --}}
                        <div>
                            <label for="tip_genero" class="block font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Sexo <span class="text-red-600">*</span>
                            </label>
                            <select name="tip_genero" id="tip_genero" required x-bind:disabled="bloqueado"
                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('tip_genero') border-red-500 @enderror">
                                <option class="dark:bg-zinc-700" value="">Selecione o sexo</option>
                                @foreach (\App\Enums\Genero::cases() as $genero)
                                    <option class="dark:bg-zinc-700" value="{{ $genero->value }}"
                                        {{ old('tip_genero', $ficha->tip_genero?->value ?? $ficha->tip_genero) == $genero->value ? 'selected' : '' }}>
                                        {{ $genero->label() }}
                                    </option>
                                @endforeach
                            </select>
                            @error('tip_genero')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Nome Completo --}}
                        <div>
                            <label for="nom_candidato"
                                class="block font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Nome completo <span class="text-red-600">*</span>
                            </label>
                            <input type="text" name="nom_candidato" id="nom_candidato"
                                x-bind:disabled="bloqueado" value="{{ old('nom_candidato', $ficha->nom_candidato) }}"
                                required maxlength="255" placeholder="Digite o nome completo"
                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('nom_candidato') border-red-500 @enderror" />
                            @error('nom_candidato')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Apelido --}}
                        <div>
                            <label for="nom_apelido" class="block font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Apelido
                            </label>
                            <input type="text" name="nom_apelido" id="nom_apelido" x-bind:disabled="bloqueado"
                                value="{{ old('nom_apelido', $ficha->nom_apelido) }}" maxlength="100"
                                placeholder="Como gosta de ser chamado"
                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('nom_apelido') border-red-500 @enderror" />
                            @error('nom_apelido')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Telefone --}}
                        <div>
                            <label for="tel_candidato"
                                class="block font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Telefone
                            </label>
                            <input type="text" name="tel_candidato" id="tel_candidato"
                                x-bind:disabled="bloqueado" value="{{ old('tel_candidato', $ficha->tel_candidato) }}"
                                maxlength="20" placeholder="(00) 00000-0000"
                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('tel_candidato') border-red-500 @enderror" />
                            @error('tel_candidato')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Email --}}
                        <div>
                            <label for="eml_candidato"
                                class="block font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Email <span class="text-red-600">*</span>
                            </label>
                            <input type="email" name="eml_candidato" id="eml_candidato"
                                x-bind:disabled="bloqueado" value="{{ old('eml_candidato', $ficha->eml_candidato) }}"
                                required maxlength="255" placeholder="exemplo@email.com"
                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('eml_candidato') border-red-500 @enderror" />
                            @error('eml_candidato')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Data de Nascimento --}}
                        <div>
                            <label for="dat_nascimento"
                                class="block font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Data de Nascimento <span class="text-red-600">*</span>
                            </label>
                            <input type="date" name="dat_nascimento" id="dat_nascimento"
                                x-bind:disabled="bloqueado"
                                value="{{ old('dat_nascimento', $ficha->getDataNascimentoFormatada()) }}" required
                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('dat_nascimento') border-red-500 @enderror" />
                            @error('dat_nascimento')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Naturalidade --}}
                        <div>
                            <label for="des_naturalidade"
                                class="block font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Naturalidade <span class="text-red-600">*</span>
                            </label>
                            <input type="text" name="des_naturalidade" id="des_naturalidade"
                                x-bind:disabled="bloqueado"
                                value="{{ old('des_naturalidade', optional($ficha->fichaSGM)->des_naturalidade) }}"
                                required maxlength="255" placeholder="Ex: Brasília/DF"
                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('des_naturalidade') border-red-500 @enderror" />
                            @error('des_naturalidade')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Tamanho da Camiseta --}}
                        <div>
                            <label for="tam_camiseta" class="block font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Tamanho da Camiseta <span class="text-red-600">*</span>
                            </label>
                            <select name="tam_camiseta" id="tam_camiseta" required x-bind:disabled="bloqueado"
                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('tam_camiseta') border-red-500 @enderror">
                                <option class="dark:bg-zinc-700" value="">Selecione o tamanho</option>
                                @foreach (\App\Enums\TamanhoCamiseta::cases() as $tamanho)
                                    <option class="dark:bg-zinc-700" value="{{ $tamanho->value }}"
                                        {{ old('tam_camiseta', $ficha->tam_camiseta?->value ?? $ficha->tam_camiseta) == $tamanho->value ? 'selected' : '' }}>
                                        {{ $tamanho->value }}
                                    </option>
                                @endforeach
                            </select>
                            @error('tam_camiseta')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Endereço --}}
                        <div class="col-span-1 md:col-span-2">
                            <label for="des_endereco" class="block font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Endereço <span class="text-red-600" aria-hidden="true">*</span><span class="sr-only">(obrigatório)</span>
                            </label>
                            <input type="text" name="des_endereco" id="des_endereco" x-bind:disabled="bloqueado"
                                required aria-required="true"
                                value="{{ old('des_endereco', $ficha->des_endereco) }}" maxlength="500"
                                placeholder="Rua, número, bairro, cidade"
                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('des_endereco') border-red-500 @enderror" />
                            @error('des_endereco')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                    </div>
                </div>

                {{-- ===== FILIAÇÃO ===== --}}
                <fieldset class="bg-white dark:bg-zinc-800 rounded-md shadow p-4 sm:p-6">
                    <legend class="text-lg sm:text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">Filiação
                    </legend>
                    <div class="space-y-6">

                        {{-- Mãe --}}
                        <div>
                            <p class="font-medium text-gray-700 dark:text-gray-300 mb-2 text-sm sm:text-base">Mãe</p>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
                                <div>
                                    <label for="nom_mae"
                                        class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Nome</label>
                                    <input type="text" name="nom_mae" id="nom_mae" x-bind:disabled="bloqueado"
                                        value="{{ old('nom_mae', optional($ficha->fichaSGM)->nom_mae) }}"
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
                                        value="{{ old('tel_mae', optional($ficha->fichaSGM)->tel_mae) }}"
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
                                        value="{{ old('eml_mae', optional($ficha->fichaSGM)->eml_mae) }}"
                                        maxlength="100" autocomplete="off" placeholder="email@exemplo.com"
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
                                        value="{{ old('nom_pai', optional($ficha->fichaSGM)->nom_pai) }}"
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
                                        value="{{ old('tel_pai', optional($ficha->fichaSGM)->tel_pai) }}"
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
                                        value="{{ old('eml_pai', optional($ficha->fichaSGM)->eml_pai) }}"
                                        maxlength="100" autocomplete="off" placeholder="email@exemplo.com"
                                        class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('eml_pai') border-red-500 @enderror" />
                                    @error('eml_pai')
                                        <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        {{-- Falar com --}}
                        <div
                            class="border-t border-gray-200 dark:border-zinc-600 pt-5 grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-6">
                            <div>
                                <label for="idt_falar_com"
                                    class="block font-medium text-gray-700 dark:text-gray-300 mb-1 text-sm sm:text-base">
                                    Falar com <span class="text-red-600" aria-hidden="true">*</span><span
                                        class="sr-only">(obrigatório)</span>
                                </label>
                                <select name="idt_falar_com" id="idt_falar_com" required x-bind:disabled="bloqueado"
                                    aria-required="true"
                                    class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('idt_falar_com') border-red-500 @enderror">
                                    <option class="dark:bg-zinc-700" value="" disabled
                                        {{ old('idt_falar_com', optional($ficha->fichaSGM)->idt_falar_com) ? '' : 'selected' }}>
                                        Selecione</option>
                                    @foreach ($responsaveis as $responsavel)
                                        <option class="dark:bg-zinc-700" value="{{ $responsavel->idt_responsavel }}"
                                            {{ old('idt_falar_com', optional($ficha->fichaSGM)->idt_falar_com) == $responsavel->idt_responsavel ? 'selected' : '' }}>
                                            {{ $responsavel->des_responsavel }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('idt_falar_com')
                                    <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="nom_falar_com"
                                    class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Nome do Contato</label>
                                <input type="text" name="nom_falar_com" id="nom_falar_com" x-bind:disabled="bloqueado"
                                    value="{{ old('nom_falar_com', optional($ficha->fichaSGM)->nom_falar_com) }}"
                                    maxlength="150" autocomplete="off" placeholder="Nome completo"
                                    class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('nom_falar_com') border-red-500 @enderror" />
                                @error('nom_falar_com')
                                    <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="tel_falar_com"
                                    class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Telefone</label>
                                <input type="tel" name="tel_falar_com" id="tel_falar_com" x-bind:disabled="bloqueado"
                                    value="{{ old('tel_falar_com', optional($ficha->fichaSGM)->tel_falar_com) }}"
                                    maxlength="20" autocomplete="off" placeholder="(61) 90000-0000"
                                    class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('tel_falar_com') border-red-500 @enderror" />
                                @error('tel_falar_com')
                                    <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                    </div>
                </fieldset>


                {{-- ===== ESCOLARIDADE ===== --}}
                <div class="bg-white dark:bg-zinc-800 rounded-md shadow p-4 sm:p-6">
                    <h2 class="text-xl font-semibold mb-4 text-gray-900 dark:text-gray-100">Escolaridade</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">

                        {{-- Escolaridade --}}
                        <div>
                            <label for="tip_escolaridade"
                                class="block font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Escolaridade <span class="text-red-600">*</span>
                            </label>
                            <select name="tip_escolaridade" id="tip_escolaridade" required
                                x-bind:disabled="bloqueado"
                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('tip_escolaridade') border-red-500 @enderror">
                                <option class="dark:bg-zinc-700" value="">Selecione uma opção</option>
                                @foreach (\App\Enums\Escolaridade::cases() as $escolaridade)
                                    <option class="dark:bg-zinc-700" value="{{ $escolaridade->value }}"
                                        {{ old('tip_escolaridade', $ficha->fichaSGM->tip_escolaridade?->value ?? $ficha->fichaSGM?->tip_escolaridade) == $escolaridade->value ? 'selected' : '' }}>
                                        {{ $escolaridade->label() }}
                                    </option>
                                @endforeach
                            </select>
                            @error('tip_escolaridade')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Situação --}}
                        <div>
                            <label for="tip_escolaridade_situacao"
                                class="block font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Situação <span class="text-red-600">*</span>
                            </label>
                            <select name="tip_escolaridade_situacao" id="tip_escolaridade_situacao" required
                                x-bind:disabled="bloqueado"
                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('tip_escolaridade_situacao') border-red-500 @enderror">
                                <option class="dark:bg-zinc-700" value="">Selecione uma opção</option>
                                @foreach (\App\Enums\EscolaridadeSituacao::cases() as $escolaridade)
                                    <option class="dark:bg-zinc-700" value="{{ $escolaridade->value }}"
                                        {{ old('tip_escolaridade_situacao', $ficha->fichaSGM->tip_escolaridade_situacao?->value ?? $ficha->fichaSGM?->tip_escolaridade_situacao) == $escolaridade->value ? 'selected' : '' }}>
                                        {{ $escolaridade->label() }}
                                    </option>
                                @endforeach
                            </select>
                            @error('tip_escolaridade_situacao')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Curso --}}
                        <div>
                            <label for="des_curso" class="block font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Curso
                            </label>
                            <input type="text" name="des_curso" id="des_curso" x-bind:disabled="bloqueado"
                                value="{{ old('des_curso', optional($ficha->fichaSgm)->des_curso) }}" maxlength="255"
                                placeholder="Nome do curso. Ex: Direito"
                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('des_curso') border-red-500 @enderror" />
                            @error('des_curso')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Instituição --}}
                        <div>
                            <label for="nom_instituicao"
                                class="block font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Instituição
                            </label>
                            <input type="text" name="nom_instituicao" id="nom_instituicao"
                                x-bind:disabled="bloqueado"
                                value="{{ old('nom_instituicao', optional($ficha->fichaSgm)->nom_instituicao) }}"
                                maxlength="255" placeholder="Nome da instituição ou escola"
                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('fichaSgm.nom_instituicao') border-red-500 @enderror" />
                            @error('fichaSgm.nom_instituicao')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                    </div>
                </div>

                {{-- ===== RELIGIÃO ===== --}}
                <div class="bg-white dark:bg-zinc-800 rounded-md shadow p-4 sm:p-6">
                    <h2 class="text-xl font-semibold mb-4 text-gray-900 dark:text-gray-100">Fé e Sacramentos</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">

                        {{-- Religião --}}
                        <div>
                            <label for="tip_religiao" class="block font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Religião <span class="text-red-600">*</span>
                            </label>
                            <select name="tip_religiao" id="tip_religiao" required x-bind:disabled="bloqueado"
                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('tip_religiao') border-red-500 @enderror">
                                <option class="dark:bg-zinc-700" value="">Selecione uma opção</option>
                                @foreach (\App\Enums\Religiao::cases() as $religiao)
                                    <option class="dark:bg-zinc-700" value="{{ $religiao->value }}"
                                        {{ old('tip_religiao', $ficha->fichaSGM->tip_religiao?->value ?? $ficha->fichaSGM?->tip_religiao) == $religiao->value ? 'selected' : '' }}>
                                        {{ $religiao->label() }}
                                    </option>
                                @endforeach
                            </select>
                            @error('tip_religiao')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Paróquia que frequenta --}}
                        <div>
                            <label for="nom_paroquia" class="block font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Paróquia que frequenta
                            </label>
                            <input type="text" name="nom_paroquia" id="nom_paroquia" x-bind:disabled="bloqueado"
                                value="{{ old('nom_paroquia', optional($ficha->fichaSgm)->nom_paroquia) }}"
                                maxlength="255" placeholder="Nome da sua paróquia. Ex: Nossa Senhora do Lago"
                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('nom_paroquia') border-red-500 @enderror" />
                            @error('nom_paroquia')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Sacramentos --}}
                        <div>
                            <p class="font-medium text-gray-700 dark:text-gray-300 mb-2 text-sm sm:text-base" id="sacramentos-label">
                                Sacramentos:
                            </p>
                            <div class="flex flex-col gap-2" role="group" aria-labelledby="sacramentos-label">
                                <label class="flex items-center gap-2.5 py-1.5 cursor-pointer">
                                    <input type="hidden" name="ind_batismo" value="0">
                                    <input type="checkbox" name="ind_batismo" value="1"
                                        x-bind:disabled="bloqueado"
                                        {{ old('ind_batismo', optional($ficha->fichaSgm)->ind_batismo) ? 'checked' : '' }}
                                        class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 @error('ind_batismo') border-red-500 @enderror">
                                    <span class="text-sm text-gray-800 dark:text-gray-100">Batismo</span>
                                </label>
                                @error('ind_batismo')
                                    <p class="text-sm text-red-600" role="alert">{{ $message }}</p>
                                @enderror
                                <label class="flex items-center gap-2.5 py-1.5 cursor-pointer">
                                    <input type="hidden" name="ind_eucaristia" value="0">
                                    <input type="checkbox" name="ind_eucaristia" value="1"
                                        x-bind:disabled="bloqueado"
                                        {{ old('ind_eucaristia', optional($ficha->fichaSgm)->ind_eucaristia) ? 'checked' : '' }}
                                        class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 @error('ind_eucaristia') border-red-500 @enderror">
                                    <span class="text-sm text-gray-800 dark:text-gray-100">Eucaristia</span>
                                </label>
                                @error('ind_eucaristia')
                                    <p class="text-sm text-red-600" role="alert">{{ $message }}</p>
                                @enderror
                                <label class="flex items-center gap-2.5 py-1.5 cursor-pointer">
                                    <input type="hidden" name="ind_crisma" value="0">
                                    <input type="checkbox" name="ind_crisma" value="1"
                                        x-bind:disabled="bloqueado"
                                        {{ old('ind_crisma', optional($ficha->fichaSgm)->ind_crisma) ? 'checked' : '' }}
                                        class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 @error('ind_crisma') border-red-500 @enderror">
                                    <span class="text-sm text-gray-800 dark:text-gray-100">Crisma</span>
                                </label>
                                @error('ind_crisma')
                                    <p class="text-sm text-red-600" role="alert">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Participa de movimento --}}
                        <div>
                            <label for="des_participa_movimento"
                                class="block font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Participa (ou) de algum movimento da Igreja? Qual(is)?
                            </label>
                            <input type="text" name="des_participa_movimento" id="des_participa_movimento"
                                x-bind:disabled="bloqueado"
                                value="{{ old('des_participa_movimento', optional($ficha->fichaSgm)->des_participa_movimento) }}"
                                maxlength="255" placeholder="Movimento que participa(ou)"
                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('des_participa_movimento') border-red-500 @enderror" />
                            @error('des_participa_movimento')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                    </div>
                </div>

                {{-- ===== QUEM CONVIDOU ===== --}}
                <div class="bg-white dark:bg-zinc-800 rounded-md shadow p-4 sm:p-6">
                    <h2 class="text-xl font-semibold mb-4 text-gray-900 dark:text-gray-100">Quem convidou você para
                        participar do encontro?</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">

                        {{-- Como soube do evento --}}
                        <div>
                            <label for="tip_como_soube"
                                class="block font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Como soube do evento
                            </label>
                            <select name="tip_como_soube" id="tip_como_soube" x-bind:disabled="bloqueado"
                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('tip_como_soube') border-red-500 @enderror">
                                <option class="dark:bg-zinc-700" value="">Selecione uma opção</option>
                                @foreach (\App\Enums\ComoSoube::cases() as $comoSoube)
                                    <option class="dark:bg-zinc-700" value="{{ $comoSoube->value }}"
                                        {{ old('tip_como_soube', $ficha->tip_como_soube?->value ?? $ficha->tip_como_soube) == $comoSoube->value ? 'selected' : '' }}>
                                        {{ $comoSoube->label() }}
                                    </option>
                                @endforeach
                            </select>
                            @error('tip_como_soube')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Nome de quem indicou --}}
                        <div>
                            <label for="nom_convidou" class="block font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Nome de quem indicou
                            </label>
                            <input type="text" name="nom_convidou" id="nom_convidou" x-bind:disabled="bloqueado"
                                value="{{ old('nom_convidou', optional($ficha->fichaSgm)->nom_convidou) }}"
                                maxlength="255" placeholder="Nome completo"
                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('nom_convidou') border-red-500 @enderror" />
                            @error('nom_convidou')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Número de quem indicou --}}
                        <div>
                            <label for="tel_convidou" class="block font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Telefone de quem indicou
                            </label>
                            <input type="text" name="tel_convidou" id="tel_convidou" x-bind:disabled="bloqueado"
                                value="{{ old('tel_convidou', optional($ficha->fichaSgm)->tel_convidou) }}"
                                maxlength="255" placeholder="(61) 90000-0000"
                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('tel_convidou') border-red-500 @enderror" />
                            @error('tel_convidou')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Endereço de quem indicou --}}
                        <div>
                            <label for="end_convidou" class="block font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Endereço de quem indicou
                            </label>
                            <input type="text" name="end_convidou" id="end_convidou" x-bind:disabled="bloqueado"
                                value="{{ old('end_convidou', optional($ficha->fichaSgm)->end_convidou) }}"
                                maxlength="255" placeholder="Rua, número, complemento, bairro, cidade, estado"
                                class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('end_convidou') border-red-500 @enderror" />
                            @error('end_convidou')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                    </div>
                </div>

                {{-- ===== SAÚDE E RESTRIÇÕES ===== --}}
                <div x-data="{
                        mostrarRestricoes: {{ old('ind_restricao', $ficha->ind_restricao ?? false) ? 'true' : 'false' }},
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
                    @error('ind_restricao')
                        <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                    @enderror

                    <div x-show="mostrarRestricoes" x-transition
                        x-ref="restricoesContainer"
                        class="mt-3 bg-gray-50 dark:bg-zinc-700 rounded-md p-4" role="region"
                        aria-label="Restrições e Alergias">
                        <h3 class="text-base sm:text-lg font-medium mb-3 text-gray-900 dark:text-gray-100">
                            Restrições e Alergias
                        </h3>

                        <div x-show="mostrarRestricoes && !temRestricaoSelecionada" x-transition
                            class="mb-4 flex items-center gap-2 rounded-md bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 px-4 py-3"
                            role="alert">
                            <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-amber-500 shrink-0" aria-hidden="true" />
                            <p class="text-sm text-amber-700 dark:text-amber-400">
                                {{ __('messages.alerts.warning.no_allergies') }}
                            </p>
                        </div>

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

                                <div class="space-y-2" x-data="{
                                    selecionado: {{ $checked ? 'true' : 'false' }},
                                    texto: '{{ addslashes($complemento) }}'
                                }">

                                    <div class="flex items-center gap-2">
                                        <input type="checkbox" name="restricoes[{{ $restricao->idt_restricao }}]"
                                            id="restricao_{{ $restricao->idt_restricao }}" value="1"
                                            x-model="selecionado"
                                            class="w-4 h-4 text-blue-600 rounded border-gray-300 dark:border-zinc-600 focus:ring-blue-500 focus:ring-2" />

                                        <label for="restricao_{{ $restricao->idt_restricao }}"
                                            class="text-gray-800 dark:text-gray-100 flex items-center gap-2 cursor-pointer">
                                            <span
                                                class="text-xs font-semibold px-2 py-0.5 rounded-full bg-gray-200 text-gray-700 dark:bg-zinc-600 dark:text-gray-300">
                                                {{ $restricao->getTipo() }}
                                            </span>
                                            <span class="text-sm">{{ $restricao->des_restricao }}</span>
                                        </label>
                                    </div>

                                    <input type="text" name="complementos[{{ $restricao->idt_restricao }}]"
                                        x-model="texto" {{-- Sincroniza o valor do input com a variável 'texto' --}}
                                        @input="if(texto.trim().length > 0) selecionado = true" {{-- Marca o checkbox ao digitar --}}
                                        placeholder="Complemento ou detalhes adicionais" maxlength="255"
                                        aria-label="Complemento para {{ $restricao->des_restricao }}"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md text-sm text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-zinc-800" />
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- ===== CONSENTIMENTO ===== --}}
                <div class="bg-white dark:bg-zinc-800 rounded-md shadow p-4 sm:p-6">
                    <h2 class="text-xl font-semibold mb-4 text-gray-900 dark:text-gray-100">Consentimento</h2>
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
                                Concorda com os
                                <a href="{{ route('termo.sgm') }}" target="_blank"
                                    class="text-blue-600 dark:text-blue-400 underline hover:no-underline font-medium">
                                    Termos e Políticas de Privacidade
                                </a>?
                                <span class="text-red-600" aria-hidden="true">*</span><span class="sr-only">(obrigatório)</span>
                            </span>
                        </label>
                        @error('ind_consentimento')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mt-6">
                        <label for="txt_observacao" class="block font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Observações que julgar convenientes
                        </label>
                        <textarea name="txt_observacao" id="txt_observacao" rows="4" maxlength="1000" x-bind:disabled="bloqueado"
                            placeholder="Inclua observações como remédios contínuos ou outros pontos de atenção"
                            class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('txt_observacao') border-red-500 @enderror">{{ old('txt_observacao', $ficha->txt_observacao) }}</textarea>
                        @error('txt_observacao')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Máximo de 1000 caracteres</p>
                    </div>
                </div>

                {{-- ===== AÇÕES ===== --}}
                <div class="mt-8 flex flex-col-reverse sm:flex-row gap-3 sm:justify-end">
                    <button type="submit" x-bind:disabled="bloqueado || enviando"
                        class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-2.5 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:outline-none focus-visible:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                        <x-heroicon-o-check class="w-5 h-5 mr-2" aria-hidden="true" />
                        <span x-text="enviando ? 'Salvando...' : 'Salvar'"></span>
                    </button>
                </div>

            </form>
        @else
            {{-- ===== SEM EVENTOS DISPONÍVEIS ===== --}}
            <div
                class="flex flex-col items-center justify-center text-center p-10 mt-4 bg-white dark:bg-zinc-800 rounded-xl shadow border border-dashed border-gray-300 dark:border-zinc-600">
                <x-heroicon-o-user-group class="w-12 h-12 text-gray-400 dark:text-gray-500 mb-4" />
                <p class="text-lg font-medium text-gray-600 dark:text-gray-300">Nenhum evento disponível no momento</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Em breve abriremos novas inscrições</p>
            </div>

        @endif
        </div>

    </section>
</x-layouts.public>
