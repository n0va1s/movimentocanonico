<x-layouts.public title="Indicação de coordenadores">
    <section class="mx-auto w-full max-w-7xl space-y-6 py-4 sm:py-8"
        x-data="{
            equipes: [
                { nome: 'Recepção', cadastroId: 1, pessoa: 'Mariana Alves', apelido: 'Mari', telefone: '(61) 90000-0001', confirmado: true },
                { nome: 'Música', pessoa: 'Rafael Souza', apelido: 'Rafa', telefone: '(61) 90000-0000' },
                { nome: 'Logística', pessoa: '' },
                { nome: 'Cozinha', pessoa: '' },
            ],
            cadastrados: [
                { cadastroId: 1, pessoa: 'Mariana Alves', apelido: 'Mari', telefone: '(61) 90000-0001' },
                { cadastroId: 2, pessoa: 'Lucas Ribeiro', apelido: 'Lu', telefone: '(61) 90000-0002' },
                { cadastroId: 3, pessoa: 'Beatriz Lima', apelido: 'Bia', telefone: '(61) 90000-0003' },
            ],
            indice: null,
            modo: '',
            busca: '',
            erro: '',
            pessoa: '',
            apelido: '',
            telefone: '',
            aviso: '',
            normalizar(texto) {
                return texto.trim().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            },
            get resultados() {
                const busca = this.normalizar(this.busca);
                if (!busca) return this.cadastrados;
                return this.cadastrados.filter(cadastro =>
                    [cadastro.pessoa, cadastro.apelido].some(texto => this.normalizar(texto).includes(busca)));
            },
            buscar(indice) {
                this.indice = indice;
                this.modo = 'busca';
                this.busca = '';
                this.erro = '';
                this.aviso = '';
                this.$nextTick(() => this.$refs.buscaCoordenador.focus());
            },
            equipeVinculada(cadastro) {
                const telefone = cadastro.telefone?.replace(/\D/g, '');
                return this.equipes.find((equipe, posicao) =>
                    posicao !== this.indice && (equipe.cadastroId === cadastro.cadastroId
                        || (telefone && equipe.telefone?.replace(/\D/g, '') === telefone)));
            },
            vincular(cadastro) {
                if (this.indice === null || this.equipeVinculada(cadastro)) return;
                Object.assign(this.equipes[this.indice], cadastro, { confirmado: true });
                this.indice = null;
                this.aviso = 'Cadastro vinculado à equipe na prévia.';
            },
            whatsapp(equipe) {
                const telefone = equipe.telefone?.replace(/\D/g, '') || '';
                if (![10, 11].includes(telefone.length)) return null;
                const mensagem = 'Olá, ' + (equipe.apelido || equipe.pessoa)
                    + '! Poderia se cadastrar no nosso sistema, por favor?';
                return 'https://wa.me/55' + telefone + '?text=' + encodeURIComponent(mensagem);
            },
            editar(indice, inicial = null) {
                const equipe = this.equipes[indice];
                this.indice = indice;
                this.modo = 'formulario';
                this.erro = '';
                this.pessoa = inicial ?? equipe.pessoa;
                this.apelido = inicial === null ? (equipe.apelido || '') : '';
                this.telefone = inicial === null ? (equipe.telefone || '') : '';
                this.aviso = '';
            },
            salvar() {
                const telefone = this.telefone.replace(/\D/g, '');
                if (!this.pessoa.trim() || ![10, 11].includes(telefone.length)) return;
                const equipe = this.equipes[this.indice];
                const pessoa = this.pessoa.trim();
                const outraEquipe = this.equipes.find((item, posicao) =>
                    posicao !== this.indice && item.telefone?.replace(/\D/g, '') === telefone);
                if (outraEquipe) {
                    this.erro = 'Este telefone já está vinculado à equipe de ' + outraEquipe.nome + '.';
                    return;
                }
                const mesmoCadastro = equipe.confirmado && equipe.pessoa === pessoa;
                Object.assign(equipe, {
                    pessoa, apelido: this.apelido.trim(), telefone: this.telefone.trim(),
                    confirmado: mesmoCadastro, cadastroId: mesmoCadastro ? equipe.cadastroId : null,
                });
                this.aviso = 'Indicação salva na prévia.';
                this.indice = null;
            },
            desvincular() {
                const equipe = this.equipes[this.indice];
                Object.assign(equipe, { pessoa: '', apelido: '', telefone: '', confirmado: false, cadastroId: null });
                this.indice = null;
                this.aviso = 'Coordenador desvinculado na prévia.';
            },
        }">
        <header class="space-y-2">
            <flux:heading level="1" size="xl" class="font-bold tracking-tight text-indigo-900 dark:text-indigo-100">
                Indicação de coordenadores
            </flux:heading>
            <flux:subheading>Indique e gerencie o coordenador de cada equipe.</flux:subheading>
        </header>

        <flux:callout icon="information-circle">
            <flux:callout.text>Prévia com dados fictícios. As indicações são simuladas e reiniciam ao recarregar.</flux:callout.text>
        </flux:callout>

        <p class="text-sm font-semibold" x-text="equipes.filter(equipe => equipe.pessoa).length + ' de ' + equipes.length + ' equipes com coordenador definido'"></p>
        <p role="status" x-show="aviso" x-text="aviso" x-cloak class="text-sm text-green-700 dark:text-green-300"></p>

        <div class="grid items-start gap-5 sm:grid-cols-2 xl:grid-cols-4">
            <template x-for="(equipe, posicao) in equipes" :key="equipe.nome">
                <div>
                    <flux:card class="space-y-4 bg-white shadow-sm dark:bg-zinc-800">
                        <flux:heading level="2" size="lg"><span x-text="equipe.nome"></span></flux:heading>
                        <div>
                            <flux:badge color="green" x-show="equipe.confirmado">Coordenador confirmado</flux:badge>
                            <flux:badge color="amber" x-show="equipe.pessoa && !equipe.confirmado">Cadastro pendente</flux:badge>
                            <flux:badge x-show="!equipe.pessoa">Sem coordenador</flux:badge>
                        </div>
                        <div x-show="equipe.pessoa">
                            <p class="font-semibold" x-text="equipe.pessoa + (equipe.apelido ? ' (' + equipe.apelido + ')' : '')"></p>
                            <flux:text><span x-text="equipe.telefone"></span></flux:text>
                        </div>
                        <flux:button type="button" x-on:click="equipe.pessoa ? editar(posicao) : buscar(posicao)"
                            class="w-full" icon="pencil-square">
                            <span x-text="equipe.pessoa ? 'Editar coordenador' : 'Indicar coordenador'"></span>
                        </flux:button>
                        <flux:button as="a" href="#" x-bind:href="whatsapp(equipe)" x-show="whatsapp(equipe)"
                            target="_blank" rel="noopener noreferrer" class="w-full">
                            Pedir cadastro pelo WhatsApp
                        </flux:button>
                    </flux:card>
                </div>
            </template>
        </div>

        <div x-show="indice !== null && modo === 'busca'" x-cloak>
            <flux:card class="space-y-4 bg-white shadow-sm dark:bg-zinc-800">
                <flux:heading level="2" size="lg">Indicar coordenador · <span x-text="equipes[indice]?.nome"></span></flux:heading>
                <flux:input label="Buscar no sistema" placeholder="Digite o nome ou apelido"
                    x-model="busca" x-ref="buscaCoordenador" icon="magnifying-glass" />
                <ul class="space-y-2" aria-label="Pessoas cadastradas">
                    <template x-for="cadastro in resultados" :key="cadastro.cadastroId">
                        <li class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                            <div>
                                <span x-text="cadastro.pessoa + ' (' + cadastro.apelido + ')'"></span>
                                <flux:text x-show="equipeVinculada(cadastro)">
                                    Já vinculado à equipe de <span x-text="equipeVinculada(cadastro)?.nome"></span>
                                </flux:text>
                            </div>
                            <flux:button type="button" size="sm" x-bind:disabled="!!equipeVinculada(cadastro)"
                                x-on:click="vincular(cadastro)">Vincular</flux:button>
                        </li>
                    </template>
                </ul>
                <div x-show="normalizar(busca) && !resultados.length" class="space-y-2">
                    <flux:text>Nenhuma pessoa encontrada com esse nome ou apelido.</flux:text>
                    <flux:button type="button" variant="primary" x-on:click="editar(indice, busca.trim())">
                        Vincular coordenador sem cadastro
                    </flux:button>
                </div>
                <flux:button type="button" x-on:click="indice = null">Cancelar</flux:button>
            </flux:card>
        </div>

        <div x-show="indice !== null && modo === 'formulario'" x-cloak>
            <flux:card class="space-y-4 bg-white shadow-sm dark:bg-zinc-800">
                <flux:heading level="2" size="lg">
                    <span x-text="equipes[indice]?.pessoa ? 'Editar coordenador' : 'Indicar coordenador'"></span>
                    · <span x-text="equipes[indice]?.nome"></span>
                </flux:heading>
                <form x-on:submit.prevent="salvar" class="space-y-4">
                    <div class="grid gap-4 sm:grid-cols-3">
                        <flux:input label="Nome completo" autocomplete="name" x-model="pessoa" required />
                        <flux:input label="Apelido" autocomplete="nickname" x-model="apelido" />
                        <flux:input label="Telefone" type="tel" autocomplete="tel" placeholder="(61) 99999-9999"
                            x-model="telefone" required />
                    </div>
                    <p role="alert" x-show="erro" x-text="erro" class="text-sm text-red-700 dark:text-red-300"></p>
                    <div class="flex flex-wrap justify-end gap-2">
                        <flux:button type="button" x-show="equipes[indice]?.pessoa" x-on:click="desvincular"
                            variant="danger" class="sm:me-auto">Desvincular coordenador</flux:button>
                        <flux:button type="button" x-on:click="indice = null">Cancelar</flux:button>
                        <flux:button type="button" x-show="equipes[indice]?.pessoa" x-on:click="buscar(indice)">
                            Buscar outro cadastro
                        </flux:button>
                        <flux:button type="submit" variant="primary"
                            x-bind:disabled="!pessoa.trim() || ![10, 11].includes(telefone.replace(/\D/g, '').length)">
                            Salvar indicação
                        </flux:button>
                    </div>
                </form>
            </flux:card>
        </div>

    </section>
</x-layouts.public>
