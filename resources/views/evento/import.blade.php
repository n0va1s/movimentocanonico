<x-layouts.app title="Importar Planilhas de Eventos">
    <section class="p-6 w-full max-w-7xl mx-auto">
        <flux:breadcrumbs class="mb-4">
            <flux:breadcrumbs.item icon="home" href="/" />
            <flux:breadcrumbs.item href="{{ route('configuracoes.index') }}">Configurações</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Importar Planilhas</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        {{-- Cabeçalho Principal --}}
        <header class="mb-8 border-b border-gray-200 dark:border-zinc-700 pb-5">
            <flux:heading size="xl" class="text-indigo-900 dark:text-indigo-100 font-bold tracking-tight mb-1 flex items-center gap-3">
                <x-heroicon-o-cloud-arrow-up class="w-8 h-8 text-blue-500" />
                Importar Planilhas de Eventos
            </flux:heading>
            <p class="text-gray-600 dark:text-gray-400 mt-2 text-sm md:text-base">
                Cadastre e atualize participantes e trabalhadores em lote (lotes de 50) vinculados a um evento ativo. O sistema verifica automaticamente duplicidades por CPF ou e-mail, vincula usuários e pessoas, e gera logs detalhados do processamento.
            </p>
        </header>

        {{-- Alerta de Informações Gerais --}}
        <div class="mb-8 p-4 bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-xl text-sm text-blue-800 dark:text-blue-200">
            <div class="flex items-start gap-3">
                <x-heroicon-s-information-circle class="w-5 h-5 mt-0.5 shrink-0" />
                <div>
                    <strong class="font-bold">Regras de Processamento Importantes:</strong>
                    <ul class="list-disc ml-5 mt-1 space-y-1">
                        <li><strong>Verificação de Cadastro:</strong> Se a pessoa já estiver cadastrada (mesmo CPF ou e-mail), seus dados cadastrais básicos serão atualizados. Caso contrário, um novo registro de Pessoa será criado.</li>
                        <li><strong>Vínculo de Usuário:</strong> Para toda Pessoa (nova ou existente) vinculada à importação, o sistema garante que haverá um Usuário (`User`) criado e devidamente associado.</li>
                        <li><strong>Senha Temporária:</strong> Novos usuários gerados terão uma senha provisória correspondente à sua data de nascimento (formato: <code class="bg-blue-100 dark:bg-blue-900 px-1 rounded font-mono">AAAAMMDD</code>).</li>
                        <li><strong>Relatórios de Log:</strong> O resultado completo do processamento de cada linha fica disponível em tempo real na pasta <code class="bg-blue-100 dark:bg-blue-900 px-1 rounded font-mono">storage/logs</code> (arquivos <code class="bg-blue-100 dark:bg-blue-900 px-1 rounded font-mono">import_participantes.log</code> e <code class="bg-blue-100 dark:bg-blue-900 px-1 rounded font-mono">import_trabalhadores.log</code>).</li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Grid de Uploads --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-12">
            
            {{-- Painel 1: Importar Participantes --}}
            <flux:card class="flex flex-col bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 shadow-sm p-6 rounded-xl hover:shadow-md transition-shadow">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h2 class="text-xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
                            <x-heroicon-o-users class="w-5 h-5 text-indigo-500" />
                            1. Importar Participantes
                        </h2>
                        <p class="text-gray-500 dark:text-gray-400 text-xs mt-1">Inscreva participantes em massa em um evento ativo.</p>
                    </div>
                    <flux:button href="{{ route('eventos.importar.modelo-participantes') }}" variant="ghost" icon="arrow-down-tray" size="sm" color="indigo" title="Baixar Planilha Modelo">
                        Modelo CSV
                    </flux:button>
                </div>

                <form method="POST" action="{{ route('eventos.importar.participantes') }}" enctype="multipart/form-data" class="space-y-6 flex-grow flex flex-col justify-between">
                    @csrf
                    
                    <div class="space-y-4">
                        {{-- Seleção de Evento --}}
                        <div>
                            <flux:label for="evento_participantes">Selecione o Evento Ativo <span class="text-red-500">*</span></flux:label>
                            <flux:select id="evento_participantes" name="evento_id" placeholder="Selecione o evento de destino...">
                                @forelse ($eventosAtivos as $ev)
                                    <flux:select.option value="{{ $ev->idt_evento }}">
                                        {{ $ev->des_evento }} ({{ $ev->movimento->des_sigla }} - {{ $ev->getDataInicioFormatada() }})
                                    </flux:select.option>
                                @empty
                                    <flux:select.option value="" disabled>{{ __('messages.empty.evento.no_active') }}</flux:select.option>
                                @endforelse
                            </flux:select>
                            <span class="text-xs text-gray-400 dark:text-zinc-500 mt-1 block">Apenas eventos futuros ou em andamento são listados aqui.</span>
                        </div>

                        {{-- Arquivo --}}
                        <div class="p-4 border-2 border-dashed border-gray-200 dark:border-zinc-700 rounded-lg bg-gray-50 dark:bg-zinc-800/40 text-center hover:bg-gray-100 dark:hover:bg-zinc-800 transition-colors">
                            <label for="arquivo_participantes" class="cursor-pointer block">
                                <x-heroicon-o-document-text class="w-8 h-8 mx-auto text-gray-400 mb-2" />
                                <span class="text-xs font-bold text-gray-700 dark:text-zinc-300 block mb-1">Escolher planilha CSV</span>
                                <span class="text-[10px] text-gray-500 dark:text-zinc-400 block mb-1">Arquivos do tipo .csv separados por ; ou ,</span>
                                <span id="nome_arquivo_participantes" class="text-xs text-indigo-600 dark:text-indigo-400 mt-2 block font-medium hidden"></span>
                            </label>
                            <input type="file" id="arquivo_participantes" name="arquivo_participantes" accept=".csv,text/csv" class="sr-only"
                                onchange="const el = document.getElementById('nome_arquivo_participantes'); if (this.files[0]) { el.textContent = this.files[0].name; el.classList.remove('hidden'); } else { el.classList.add('hidden'); }">
                            @error('arquivo_participantes')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="pt-4 border-t border-gray-100 dark:border-zinc-700/50 mt-6">
                        <flux:button type="submit" variant="primary" class="w-full bg-gradient-to-r from-indigo-500 to-purple-500 hover:from-indigo-600 hover:to-purple-600 border-none shadow-md" icon="arrow-up-on-square" loading>
                            Iniciar Importação de Participantes
                        </flux:button>
                    </div>
                </form>
            </flux:card>

            {{-- Painel 2: Importar Trabalhadores --}}
            <flux:card class="flex flex-col bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 shadow-sm p-6 rounded-xl hover:shadow-md transition-shadow">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h2 class="text-xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
                            <x-heroicon-o-briefcase class="w-5 h-5 text-emerald-500" />
                            2. Importar Trabalhadores
                        </h2>
                        <p class="text-gray-500 dark:text-gray-400 text-xs mt-1">Aloque equipes e trabalhadores em massa em um evento ativo.</p>
                    </div>
                    <flux:button href="{{ route('eventos.importar.modelo-trabalhadores') }}" variant="ghost" icon="arrow-down-tray" size="sm" color="emerald" title="Baixar Planilha Modelo">
                        Modelo CSV
                    </flux:button>
                </div>

                <form method="POST" action="{{ route('eventos.importar.trabalhadores') }}" enctype="multipart/form-data" class="space-y-6 flex-grow flex flex-col justify-between">
                    @csrf

                    <div class="space-y-4">
                        {{-- Seleção de Evento --}}
                        <div>
                            <flux:label for="evento_trabalhadores">Selecione o Evento Ativo <span class="text-red-500">*</span></flux:label>
                            <flux:select id="evento_trabalhadores" name="evento_id" placeholder="Selecione o evento de destino...">
                                @forelse ($eventosAtivos as $ev)
                                    <flux:select.option value="{{ $ev->idt_evento }}">
                                        {{ $ev->des_evento }} ({{ $ev->movimento->des_sigla }} - {{ $ev->getDataInicioFormatada() }})
                                    </flux:select.option>
                                @empty
                                    <flux:select.option value="" disabled>{{ __('messages.empty.evento.no_active') }}</flux:select.option>
                                @endforelse
                            </flux:select>
                            <span class="text-xs text-gray-400 dark:text-zinc-500 mt-1 block">Apenas eventos futuros ou em andamento são listados aqui.</span>
                        </div>

                        {{-- Arquivo --}}
                        <div class="p-4 border-2 border-dashed border-gray-200 dark:border-zinc-700 rounded-lg bg-gray-50 dark:bg-zinc-800/40 text-center hover:bg-gray-100 dark:hover:bg-zinc-800 transition-colors">
                            <label for="arquivo_trabalhadores" class="cursor-pointer block">
                                <x-heroicon-o-document-text class="w-8 h-8 mx-auto text-gray-400 mb-2" />
                                <span class="text-xs font-bold text-gray-700 dark:text-zinc-300 block mb-1">Escolher planilha CSV</span>
                                <span class="text-[10px] text-gray-500 dark:text-zinc-400 block mb-1">Arquivos do tipo .csv separados por ; ou ,</span>
                                <span id="nome_arquivo_trabalhadores" class="text-xs text-emerald-600 dark:text-emerald-400 mt-2 block font-medium hidden"></span>
                            </label>
                            <input type="file" id="arquivo_trabalhadores" name="arquivo_trabalhadores" accept=".csv,text/csv" class="sr-only"
                                onchange="const el = document.getElementById('nome_arquivo_trabalhadores'); if (this.files[0]) { el.textContent = this.files[0].name; el.classList.remove('hidden'); } else { el.classList.add('hidden'); }">
                            @error('arquivo_trabalhadores')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="pt-4 border-t border-gray-100 dark:border-zinc-700/50 mt-6">
                        <flux:button type="submit" variant="primary" class="w-full bg-gradient-to-r from-indigo-500 to-purple-500 hover:from-indigo-600 hover:to-purple-600 border-none shadow-md" icon="arrow-up-on-square" loading>
                            Iniciar Importação de Trabalhadores
                        </flux:button>
                    </div>
                </form>
            </flux:card>

        </div>

        {{-- Explicação dos Campos --}}
        <flux:card class="bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 shadow-sm p-6 rounded-xl">
            <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                <x-heroicon-o-table-cells class="w-5 h-5 text-blue-500" />
                Guia de Colunas do CSV e Formatações
            </h3>
            <p class="text-gray-600 dark:text-gray-400 text-sm mb-6">
                Para que o importador funcione corretamente, certifique-se de que os cabeçalhos do seu arquivo correspondam a um dos termos reconhecidos abaixo. Cabeçalhos com acentos ou variações de maiúsculas/minúsculas são resolvidos de forma inteligente pelo sistema.
            </p>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs md:text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-zinc-700 text-gray-500 dark:text-zinc-400 uppercase tracking-wider text-[11px] font-bold">
                            <th class="py-3 px-4">Coluna na Planilha</th>
                            <th class="py-3 px-4">Obrigatório</th>
                            <th class="py-3 px-4">Valores Aceitos e Formatos</th>
                            <th class="py-3 px-4">Onde é salvo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-zinc-700 text-gray-700 dark:text-zinc-300">
                        <tr>
                            <td class="py-3 px-4 font-mono font-bold">CPF</td>
                            <td class="py-3 px-4 text-gray-400">Não (Recomendável)</td>
                            <td class="py-3 px-4">Apenas números ou formato padrão: <code class="bg-gray-100 dark:bg-zinc-900 px-1 rounded">xxx.xxx.xxx-xx</code>. É usado para verificar duplicidades.</td>
                            <td class="py-3 px-4 font-mono text-gray-500">pessoa.num_cpf_pessoa</td>
                        </tr>
                        <tr class="bg-gray-50/50 dark:bg-zinc-800/30">
                            <td class="py-3 px-4 font-mono font-bold">Nome</td>
                            <td class="py-3 px-4 text-red-500 font-bold">Sim</td>
                            <td class="py-3 px-4">Nome completo da pessoa. Ex: <code class="bg-gray-100 dark:bg-zinc-900 px-1 rounded">Maria da Silva</code>.</td>
                            <td class="py-3 px-4 font-mono text-gray-500">pessoa.nom_pessoa</td>
                        </tr>
                        <tr>
                            <td class="py-3 px-4 font-mono font-bold">Email</td>
                            <td class="py-3 px-4 text-red-500 font-bold">Sim</td>
                            <td class="py-3 px-4">Endereço de e-mail válido. Ex: <code class="bg-gray-100 dark:bg-zinc-900 px-1 rounded">maria@gmail.com</code>. Usado para criar o Usuário e login.</td>
                            <td class="py-3 px-4 font-mono text-gray-500">pessoa.eml_pessoa, users.email</td>
                        </tr>
                        <tr class="bg-gray-50/50 dark:bg-zinc-800/30">
                            <td class="py-3 px-4 font-mono font-bold">Data Nascimento</td>
                            <td class="py-3 px-4 text-red-500 font-bold">Sim</td>
                            <td class="py-3 px-4">Formatos válidos: <code class="bg-gray-100 dark:bg-zinc-900 px-1 rounded">DD/MM/AAAA</code> ou <code class="bg-gray-100 dark:bg-zinc-900 px-1 rounded">AAAA-MM-DD</code>. Usado para gerar a senha padrão de acesso.</td>
                            <td class="py-3 px-4 font-mono text-gray-500">pessoa.dat_nascimento</td>
                        </tr>
                        <tr>
                            <td class="py-3 px-4 font-mono font-bold">Genero</td>
                            <td class="py-3 px-4">Não</td>
                            <td class="py-3 px-4"><code class="bg-gray-100 dark:bg-zinc-900 px-1 rounded">M</code> (Masculino) ou <code class="bg-gray-100 dark:bg-zinc-900 px-1 rounded">F</code> (Feminino).</td>
                            <td class="py-3 px-4 font-mono text-gray-500">pessoa.tip_genero</td>
                        </tr>
                        <tr class="bg-gray-50/50 dark:bg-zinc-800/30">
                            <td class="py-3 px-4 font-mono font-bold">Tamanho Camiseta</td>
                            <td class="py-3 px-4">Não</td>
                            <td class="py-3 px-4">Tamanhos padrão: <code class="bg-gray-100 dark:bg-zinc-900 px-1 rounded">PP</code>, <code class="bg-gray-100 dark:bg-zinc-900 px-1 rounded">P</code>, <code class="bg-gray-100 dark:bg-zinc-900 px-1 rounded">M</code>, <code class="bg-gray-100 dark:bg-zinc-900 px-1 rounded">G</code>, <code class="bg-gray-100 dark:bg-zinc-900 px-1 rounded">GG</code>, <code class="bg-gray-100 dark:bg-zinc-900 px-1 rounded">EG</code>.</td>
                            <td class="py-3 px-4 font-mono text-gray-500">pessoa.tam_camiseta</td>
                        </tr>
                        <tr>
                            <td class="py-3 px-4 font-mono font-bold">Cor Troca</td>
                            <td class="py-3 px-4">Não (Apenas Partic.)</td>
                            <td class="py-3 px-4">Cores de troca aceitas: <code class="bg-gray-100 dark:bg-zinc-900 px-1 rounded">vermelha</code>, <code class="bg-gray-100 dark:bg-zinc-900 px-1 rounded">azul</code>, <code class="bg-gray-100 dark:bg-zinc-900 px-1 rounded">verde</code>, <code class="bg-gray-100 dark:bg-zinc-900 px-1 rounded">amarela</code>, <code class="bg-gray-100 dark:bg-zinc-900 px-1 rounded">laranja</code>.</td>
                            <td class="py-3 px-4 font-mono text-gray-500">participante.tip_cor_troca</td>
                        </tr>
                        <tr class="bg-gray-50/50 dark:bg-zinc-800/30">
                            <td class="py-3 px-4 font-mono font-bold">Equipe</td>
                            <td class="py-3 px-4 text-red-500 font-bold">Sim (Apenas Trab.)</td>
                            <td class="py-3 px-4">Nome exato ou termo parcial do grupo da equipe (deve existir no movimento). Ex: <code class="bg-gray-100 dark:bg-zinc-900 px-1 rounded">Bandinha</code>. Ou o ID numérico.</td>
                            <td class="py-3 px-4 font-mono text-gray-500">trabalhador.idt_equipe</td>
                        </tr>
                        <tr>
                            <td class="py-3 px-4 font-mono font-bold">Taxa Pagou / Presente / Coordenador...</td>
                            <td class="py-3 px-4">Não</td>
                            <td class="py-3 px-4">Campos lógicos e booleanos de controle. Aceita: <code class="bg-gray-100 dark:bg-zinc-900 px-1 rounded">Sim</code>, <code class="bg-gray-100 dark:bg-zinc-900 px-1 rounded">Não</code>, <code class="bg-gray-100 dark:bg-zinc-900 px-1 rounded">1</code>, <code class="bg-gray-100 dark:bg-zinc-900 px-1 rounded">0</code>, <code class="bg-gray-100 dark:bg-zinc-900 px-1 rounded">S</code>, <code class="bg-gray-100 dark:bg-zinc-900 px-1 rounded">N</code>.</td>
                            <td class="py-3 px-4 font-mono text-gray-500">Relacionamentos específicos</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </flux:card>
    </section>
</x-layouts.app>
