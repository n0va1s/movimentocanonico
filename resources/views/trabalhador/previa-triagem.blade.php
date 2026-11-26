<x-layouts.public title="Prévia da triagem de voluntários">
    @php
        $candidaturas = [
            ['nome' => 'Mariana Alves', 'apelido' => 'Mari', 'data' => '25/11', 'status' => 'Aguardando escalação',
                'preferencias' => ['Recepção' => 'Gosto de acolher quem chega e posso orientar os grupos.', 'Música' => 'Posso ajudar com violão nos momentos de oração.'],
                'experiencia' => 'Recepção e logística', 'musica' => 'Toca violão e canta', 'observacoes' => 'Tenho disponibilidade para ajudar na preparação no dia anterior.'],
            ['nome' => 'Lucas Ribeiro', 'apelido' => 'Lu', 'data' => '24/11', 'status' => 'Aguardando escalação',
                'preferencias' => ['Logística' => 'Posso organizar materiais e ajudar na montagem das salas.'],
                'experiencia' => 'Primeira candidatura', 'musica' => 'Não informado', 'observacoes' => 'Já organizei atividades em grupos grandes.'],
            ['nome' => 'Beatriz Lima', 'apelido' => 'Bia', 'data' => '23/11', 'status' => 'Escalado',
                'preferencias' => ['Recepção' => 'Já trabalhei na recepção e posso orientar voluntários novos.'],
                'experiencia' => 'Recepção', 'musica' => 'Canta', 'observacoes' => 'Escalada para Recepção.'],
            ['nome' => 'Rafael Souza', 'apelido' => 'Rafa', 'data' => '22/11', 'status' => 'Não selecionado',
                'preferencias' => ['Cozinha' => 'Tenho experiência com preparo de refeições em grupo.'],
                'experiencia' => 'Cozinha', 'musica' => 'Não informado', 'observacoes' => 'Disponível no sábado.'],
        ];
        $equipes = ['Recepção', 'Música', 'Logística', 'Cozinha', 'Comunicação', 'Apoio'];
        $nomes = ['Ana', 'Clara', 'Daniel', 'Elisa', 'Felipe', 'Gabriela', 'Henrique', 'Isabela', 'João', 'Laura', 'Miguel'];
        $sobrenomes = ['Costa', 'Ferreira', 'Gomes', 'Martins', 'Oliveira', 'Santos'];

        foreach ($sobrenomes as $grupo => $sobrenome) {
            foreach ($nomes as $posicao => $nome) {
                $modelo = $candidaturas[($grupo + $posicao) % 2];
                $candidaturas[] = array_replace($modelo, [
                    'nome' => "$nome $sobrenome",
                    'apelido' => $nome,
                    'status' => match ($grupo) {
                        4 => 'Escalado',
                        5 => 'Não selecionado',
                        default => 'Aguardando escalação',
                    },
                ]);
            }
        }

        foreach ($candidaturas as $indice => &$candidatura) {
            $candidatura['prioritario'] = false;
            $candidatura['coordenador'] = $indice === 2;
        }
        unset($candidatura);
        $origens = [
            'vem' => ['nome' => '74º encontro VEM', 'selo' => 'Ex-venista'],
            'segue-me' => ['nome' => '73º encontro Segue-me', 'selo' => 'Ex-seguimista'],
        ];
        foreach (['vem' => ['Helena Rocha', 'Pedro Almeida'], 'segue-me' => ['Carolina Dias', 'André Melo']] as $origem => $participantes) {
            foreach ($participantes as $nome) {
                $candidaturas[] = [
                    'nome' => $nome, 'apelido' => '', 'data' => '', 'status' => 'Aguardando escalação',
                    'preferencias' => [], 'experiencia' => 'Participou do encontro anterior',
                    'musica' => 'Não informado', 'observacoes' => 'Sem observações',
                    'prioritario' => true, 'coordenador' => false, 'origem' => $origem,
                ];
            }
        }
        usort($candidaturas, fn ($a, $b) => $b['prioritario'] <=> $a['prioritario']);
        $origensPorCandidato = array_map(fn ($candidato) => $candidato['origem'] ?? '', $candidaturas);
        $nomesPorCandidato = array_map(fn ($candidato) => trim($candidato['nome'].' '.$candidato['apelido']), $candidaturas);
        $coordenadores = array_column($candidaturas, 'coordenador');
        $preferenciasPorCandidato = array_map(fn ($candidato) => array_keys($candidato['preferencias']), $candidaturas);
        $primeiroPendente = array_key_first(array_filter($candidaturas, fn ($candidato) =>
            $candidato['status'] === 'Aguardando escalação' && empty($candidato['origem'])));
    @endphp

    <section class="mx-auto w-full max-w-7xl space-y-3"
        x-data="{
            filtro: 'Aguardando escalação',
            busca: '',
            perfil: '',
            ordem: 'prioridade',
            equipeInteresse: '',
            equipeEscalada: '',
            mostrandoFiltros: false,
            selecionado: {{ $primeiroPendente }},
            origem: '',
            origemVinculada: '',
            nomesDasOrigens: @js(array_map(fn ($encontro) => $encontro['nome'], $origens)),
            origensPorCandidato: @js($origensPorCandidato),
            nomesPorCandidato: @js($nomesPorCandidato),
            resumos: @js(array_map(fn ($candidato) => [
                'nome' => $candidato['nome'],
                'linha' => implode(' · ', array_filter([$candidato['apelido'], $candidato['data'] ? 'Recebida em '.$candidato['data'] : null])),
                'equipes' => implode(' · ', array_keys($candidato['preferencias'])),
                'selo' => $candidato['prioritario'] ? $origens[$candidato['origem']]['selo'] : null,
            ], $candidaturas)),
            coordenadores: @js($coordenadores),
            preferenciasPorCandidato: @js($preferenciasPorCandidato),
            exibindoVinculo: false,
            situacoes: @js(array_column($candidaturas, 'status')),
            escalas: {},
            motivos: {},
            motivo: '',
            informandoMotivo: false,
            equipe: '',
            aviso: '',
            visivel(indice) {
                const origem = this.origensPorCandidato[indice];
                return !origem || this.origemVinculada === origem;
            },
            normalizar(texto) {
                return texto.trim().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            },
            equipeDoEscalado(indice) {
                return this.escalas[indice] || this.preferenciasPorCandidato[indice][0] || '';
            },
            corresponde(indice, busca, situacao) {
                const origem = this.origensPorCandidato[indice];
                return this.visivel(indice)
                    && this.normalizar(this.nomesPorCandidato[indice]).includes(busca)
                    && (situacao !== 'Aguardando escalação' || !this.equipeInteresse
                        || this.preferenciasPorCandidato[indice].includes(this.equipeInteresse))
                    && (situacao !== 'Escalado' || !this.equipeEscalada || this.equipeDoEscalado(indice) === this.equipeEscalada)
                    && (!this.perfil || (this.perfil === 'coordenador' && this.coordenadores[indice])
                        || (this.perfil === 'publica' && !origem && !this.coordenadores[indice])
                        || this.perfil === origem);
            },
            indices(situacao) {
                const busca = this.normalizar(this.busca);
                const resultados = this.situacoes.flatMap((status, indice) =>
                    status === situacao && this.corresponde(indice, busca, situacao) ? [indice] : []);
                if (this.ordem === 'prioridade') return resultados;
                const direcao = this.ordem === 'nome-desc' ? -1 : 1;
                return resultados.sort((a, b) => direcao * this.nomesPorCandidato[a]
                    .localeCompare(this.nomesPorCandidato[b], 'pt-BR', { sensitivity: 'base' }));
            },
            get resultadosAtuais() {
                return this.indices(this.filtro);
            },
            limparFiltros() {
                this.busca = '';
                this.perfil = '';
                this.equipeInteresse = '';
                this.equipeEscalada = '';
                this.ordem = 'prioridade';
                this.atualizarSelecao();
            },
            atualizarSelecao() {
                const resultados = this.indices(this.filtro);
                if (!resultados.includes(this.selecionado)) this.selecionar(resultados[0] ?? -1);
            },
            vincularOrigem() {
                if (!this.origem || this.origem === this.origemVinculada) return;
                this.origemVinculada = this.origem;
                this.origem = '';
                this.exibindoVinculo = false;
                this.aviso = 'Participantes do encontro vinculados na prévia.';
                this.busca = '';
                this.perfil = '';
                this.filtrar('Aguardando escalação');
            },
            filtrar(situacao) {
                this.filtro = situacao;
                this.selecionar(this.indices(situacao)[0] ?? -1);
            },
            selecionar(indice) {
                this.selecionado = indice;
                this.equipe = '';
                this.motivo = '';
                this.informandoMotivo = false;
            },
            decidir(situacao) {
                const indice = this.selecionado;
                if (this.situacoes[indice] !== 'Aguardando escalação') return;
                if (situacao === 'Escalado') {
                    if (!this.equipe) return;
                    this.escalas[indice] = this.equipe;
                } else {
                    if (!this.motivo.trim()) return;
                    this.motivos[indice] = this.motivo.trim();
                }
                this.situacoes[indice] = situacao;
                this.aviso = situacao === 'Escalado'
                    ? 'Candidatura escalada para ' + this.equipe + '.'
                    : 'Candidatura marcada como não selecionada.';
                this.filtrar(this.filtro);
            },
        }">
        <header class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <flux:heading level="1" size="xl" class="font-bold tracking-tight text-indigo-900 dark:text-indigo-100">
                    Triagem de voluntários
                </flux:heading>
                <flux:subheading>Evento 75 · Prévia com dados fictícios</flux:subheading>
            </div>
            <flux:button type="button" size="sm" icon="link" x-show="!origemVinculada"
                x-on:click="exibindoVinculo = !exibindoVinculo">
                Vincular encontro anterior
            </flux:button>
            <div x-show="origemVinculada" x-cloak class="flex flex-wrap items-center gap-2">
            <flux:badge color="indigo">
                Encontro vinculado
                <span x-text="nomesDasOrigens[origemVinculada]"></span>
            </flux:badge>
            <flux:button type="button" size="sm" icon="arrows-right-left" aria-label="Trocar encontro" title="Trocar encontro"
                x-on:click="origem = origemVinculada; exibindoVinculo = !exibindoVinculo" />
            </div>
        </header>
        <div x-show="exibindoVinculo" x-cloak>
            <flux:card class="space-y-3">
                <flux:select label="Encontro de origem" x-model="origem">
                    <option value="">Selecione o encontro</option>
                    @foreach ($origens as $id => $encontro)
                        <option value="{{ $id }}">{{ $encontro['nome'] }}</option>
                    @endforeach
                </flux:select>
                <flux:button type="button" variant="primary" x-bind:disabled="!origem || origem === origemVinculada" x-on:click="vincularOrigem">
                    <span x-text="origemVinculada ? 'Confirmar troca' : 'Vincular participantes'"></span>
                </flux:button>
                <flux:button type="button" x-on:click="exibindoVinculo = false">Cancelar</flux:button>
            </flux:card>
        </div>

        <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex flex-wrap gap-2" role="group" aria-label="Filtrar por situação">
            @foreach (['Aguardando escalação', 'Escalado', 'Não selecionado'] as $status)
                <flux:button type="button" size="sm"
                    data-situacao="{{ $status }}"
                    x-on:click="filtrar($el.dataset.situacao)"
                    x-bind:aria-pressed="filtro === $el.dataset.situacao"
                    class="aria-pressed:border-indigo-500 aria-pressed:text-indigo-700 dark:aria-pressed:text-indigo-300">
                    {{ $status }} (<span x-text="indices(@js($status)).length"></span>)
                </flux:button>
            @endforeach
        </div>
        <div class="flex items-center gap-2 lg:w-72">
            <flux:input aria-label="Buscar pessoa" placeholder="Nome ou apelido" icon="magnifying-glass" size="sm"
                class="min-w-0 flex-1" x-model="busca" x-on:input="$nextTick(() => atualizarSelecao())" />
            <flux:button type="button" size="sm" icon="funnel" aria-label="Filtros e ordenação" title="Filtros e ordenação"
                aria-controls="filtros-triagem" x-bind:aria-expanded="mostrandoFiltros"
                x-on:click="mostrandoFiltros = !mostrandoFiltros" />
        </div>
        </div>
        <div id="filtros-triagem" x-show="mostrandoFiltros" x-cloak>
            <flux:card class="space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <flux:heading level="2">Filtros e ordenação</flux:heading>
                    <flux:button type="button" size="sm" variant="ghost" x-on:click="limparFiltros">Limpar filtros</flux:button>
                </div>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4" x-on:change="$nextTick(() => atualizarSelecao())">
                    <flux:select label="Ordenar por" x-model="ordem">
                        <option value="prioridade">Prioridade</option>
                        <option value="nome-asc">Nome (A–Z)</option>
                        <option value="nome-desc">Nome (Z–A)</option>
                    </flux:select>
                    <flux:select label="Perfil" x-model="perfil">
                        <option value="">Todos os perfis</option>
                        <option value="publica">Candidatura pública</option>
                        <option value="coordenador">Coordenador</option>
                        <option value="vem">Ex-venista</option>
                        <option value="segue-me">Ex-seguimista</option>
                    </flux:select>
                    <div x-show="filtro === 'Aguardando escalação'">
                        <flux:select label="Equipe de interesse" x-model="equipeInteresse">
                            <option value="">Todas as equipes</option>
                            @foreach ($equipes as $equipe)
                                <option value="{{ $equipe }}">{{ $equipe }}</option>
                            @endforeach
                        </flux:select>
                    </div>
                    <div x-show="filtro === 'Escalado'">
                        <flux:select label="Equipe escalada" x-model="equipeEscalada">
                            <option value="">Todas as equipes</option>
                            @foreach ($equipes as $equipe)
                                <option value="{{ $equipe }}">{{ $equipe }}</option>
                            @endforeach
                        </flux:select>
                    </div>
                </div>
            </flux:card>
        </div>
        <p role="status" x-show="aviso" x-text="aviso" x-cloak class="text-sm font-medium text-green-700 dark:text-green-300"></p>

        <div class="grid items-start gap-5 lg:grid-cols-3">
            <ul class="flex max-h-80 flex-col gap-3 overflow-y-auto p-1 lg:max-h-[36rem]" aria-label="Candidaturas">
                <template x-for="indice in resultadosAtuais" :key="indice">
                    <li>
                        <button type="button" x-on:click="selecionar(indice)"
                            x-bind:aria-pressed="selecionado === indice"
                            class="w-full rounded-xl border border-zinc-200 bg-white p-4 text-left shadow-sm hover:border-indigo-400 aria-pressed:border-indigo-500 dark:border-zinc-700 dark:bg-zinc-800 dark:aria-pressed:border-indigo-400">
                            <span class="flex flex-wrap items-center gap-2">
                                <span class="font-semibold" x-text="resumos[indice].nome"></span>
                                <flux:badge color="amber" size="sm" x-show="resumos[indice].selo">
                                    <span x-text="resumos[indice].selo"></span>
                                </flux:badge>
                                <flux:badge color="indigo" size="sm" x-show="coordenadores[indice]">Coordenador</flux:badge>
                            </span>
                            <span class="block text-sm text-zinc-600 dark:text-zinc-300" x-show="resumos[indice].linha" x-text="resumos[indice].linha"></span>
                            <span class="mt-2 block text-sm" x-show="resumos[indice].equipes" x-text="resumos[indice].equipes"></span>
                        </button>
                    </li>
                </template>
                <li x-show="!resultadosAtuais.length" x-cloak>Nenhuma candidatura corresponde aos filtros.</li>
            </ul>

            <div class="min-w-0 lg:col-span-2">
                @foreach ($candidaturas as $indice => $candidato)
                    <div x-show="selecionado === {{ $indice }}" x-cloak>
                        <flux:card class="space-y-5 bg-white shadow-sm dark:bg-zinc-800">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <flux:heading level="2" size="lg">{{ $candidato['nome'] }}</flux:heading>
                                        @if ($candidato['prioritario'])
                                            <flux:badge color="amber">{{ $origens[$candidato['origem']]['selo'] }}</flux:badge>
                                        @endif
                                        @if ($candidato['coordenador'])
                                            <flux:badge color="indigo">Coordenador</flux:badge>
                                        @endif
                                        <span x-show="situacoes[{{ $indice }}] === 'Escalado'"
                                            class="inline-flex items-center gap-1.5 rounded-full bg-indigo-100 px-3 py-1 text-sm font-semibold text-indigo-900 dark:bg-indigo-950 dark:text-indigo-200">
                                            <flux:icon.user-group class="size-4" aria-hidden="true" />
                                            Escalado para <span x-text="equipeDoEscalado({{ $indice }})"></span>
                                        </span>
                                    </div>
                                    <flux:subheading>
                                        {{ $candidato['prioritario'] ? 'Participante do encontro anterior' : 'Candidatura pública' }}
                                        @if ($candidato['data']) · {{ $candidato['data'] }} @endif
                                    </flux:subheading>
                                </div>
                                <flux:badge color="indigo"><span x-text="situacoes[{{ $indice }}]"></span></flux:badge>
                            </div>

                            <div class="space-y-3">
                                <flux:heading level="3">Equipes desejadas</flux:heading>
                                @if (!$candidato['preferencias'])
                                    <flux:text>Nenhuma preferência informada.</flux:text>
                                @endif
                                @foreach ($candidato['preferencias'] as $equipe => $motivo)
                                    <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-900">
                                        <p class="font-semibold">{{ $equipe }}</p>
                                        <flux:text>{{ $motivo }}</flux:text>
                                    </div>
                                @endforeach
                            </div>

                            <dl class="grid gap-4 border-t border-zinc-200 pt-4 sm:grid-cols-2 dark:border-zinc-700">
                                <div><dt class="font-semibold">Experiência declarada</dt><dd>{{ $candidato['experiencia'] }}</dd></div>
                                <div><dt class="font-semibold">Música</dt><dd>{{ $candidato['musica'] }}</dd></div>
                                <div class="sm:col-span-2"><dt class="font-semibold">Observações</dt><dd>{{ $candidato['observacoes'] }}</dd></div>
                            </dl>

                            <div x-show="situacoes[{{ $indice }}] === 'Aguardando escalação'" class="space-y-3">
                                <flux:select label="Equipe de escalação" x-model="equipe">
                                    <option value="">Selecione uma equipe</option>
                                    @foreach ($equipes as $equipe)
                                        <option value="{{ $equipe }}">{{ $equipe }}</option>
                                    @endforeach
                                </flux:select>
                                <div class="flex flex-wrap gap-2">
                                    <flux:button type="button" variant="primary" x-bind:disabled="!equipe"
                                        x-on:click="decidir('Escalado')">Escalar para equipe</flux:button>
                                    <flux:button type="button" x-show="!informandoMotivo" x-on:click="informandoMotivo = true">
                                        Marcar como não selecionado
                                    </flux:button>
                                </div>
                                <div x-show="informandoMotivo" class="space-y-3">
                                    <flux:textarea label="Motivo da não seleção" rows="2" maxlength="500"
                                        x-model="motivo" x-bind:required="informandoMotivo" />
                                    <div class="flex flex-wrap gap-2">
                                        <flux:button type="button" variant="danger" x-bind:disabled="!motivo.trim()"
                                            x-on:click="decidir('Não selecionado')">Confirmar não seleção</flux:button>
                                        <flux:button type="button" x-on:click="informandoMotivo = false; motivo = ''">Cancelar</flux:button>
                                    </div>
                                </div>
                            </div>
                            <div x-show="situacoes[{{ $indice }}] === 'Não selecionado'"
                                class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-900">
                                <flux:heading level="3">Motivo da não seleção</flux:heading>
                                <flux:text class="whitespace-pre-wrap break-words"><span x-text="motivos[{{ $indice }}] || 'As vagas da equipe já foram preenchidas.'"></span></flux:text>
                            </div>
                        </flux:card>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
</x-layouts.public>
