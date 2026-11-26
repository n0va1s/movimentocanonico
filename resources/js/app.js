const registrarTriagem = () => {
    window.Alpine.data('listaTrabalhadores', () => ({
        dados: {},
        observador: null,

        init() {
            this.atualizarDados();
            this.observador = new MutationObserver(() => this.atualizarDados());
            this.observador.observe(this.$el, { attributes: true, attributeFilter: ['data-trabalhadores'] });
        },

        destroy() {
            this.observador?.disconnect();
        },

        atualizarDados() {
            this.dados = JSON.parse(this.$el.dataset.trabalhadores);
        },

        mudarVisualizacao(modo) {
            this.$wire.$set('visualizacao', modo, false);
        },

        alternarEquipe(id) {
            this.$wire.$set('equipeAbertaId', this.$wire.equipeAbertaId === id ? '' : id, false);
            this.$wire.$set('paginators.paginaEquipeTrabalhadores', 1, false);
        },

        equipe(id) {
            const pagina = this.dados.paginaEquipe;
            return pagina?.id === id && this.$wire.paginators?.paginaEquipeTrabalhadores > 1
                ? pagina : this.dados.equipes[id];
        },

        paginas(lista) {
            return Math.max(1, Math.ceil(lista.total / this.dados.porPagina));
        },

        remover(id) {
            if (window.confirm('Remover esta pessoa da escalação?')) {
                this.$wire.removerTrabalhador(id);
            }
        },
    }));

    window.Alpine.data('triagemVoluntarios', () => ({
        dados: {},
        observador: null,
        situacao: 'pendente',
        selecao: null,
        filtrosAbertos: false,
        recusando: false,

        init() {
            this.situacao = this.$wire.situacao;
            this.atualizarDados();
            this.observador = new MutationObserver(() => this.atualizarDados());
            this.observador.observe(this.$el, { attributes: true, attributeFilter: ['data-candidaturas'] });
        },

        destroy() {
            this.observador?.disconnect();
        },

        atualizarDados() {
            this.dados = JSON.parse(this.$el.dataset.candidaturas);
            if (!this.candidatos.some(({ id }) => id === this.selecao)) {
                this.selecao = this.candidatos[0]?.id ?? null;
                this.recusando = false;
            }
        },

        get candidatos() {
            return this.dados[this.situacao] ?? [];
        },

        get candidato() {
            return this.candidatos.find(({ id }) => id === this.selecao);
        },

        escolherSituacao(situacao) {
            if (this.situacao === situacao) return;
            this.situacao = situacao;
            this.$wire.$set('situacao', situacao, false);
            this.selecionar(this.candidatos[0]?.id ?? null);
        },

        selecionar(id, revelar = false) {
            if (this.selecao !== id) {
                this.selecao = id;
                this.recusando = false;
                for (const [campo, valor] of Object.entries({ equipe: '', primeiraVez: false, motivo: '' })) {
                    this.$wire.$set(campo, valor, false);
                }
            }
            if (revelar && matchMedia('(width < 64rem)').matches) {
                this.$nextTick(() => this.$refs.tituloCandidatura.focus());
            }
        },
    }));

    window.Alpine.data('modalTriagem', (titulo) => ({
        observador: null,
        quadro: null,

        init() {
            const dialog = this.$el.querySelector('dialog');
            dialog.setAttribute('aria-labelledby', titulo);

            this.observador = new MutationObserver(() => {
                if (!dialog.open || dialog.contains(document.activeElement)) return;

                cancelAnimationFrame(this.quadro);
                this.quadro = requestAnimationFrame(() => {
                    if (dialog.open && !dialog.contains(document.activeElement)) {
                        dialog.querySelector('[data-modal-initial-focus]')?.focus();
                    }
                });
            });
            this.observador.observe(dialog, {
                attributes: true,
                attributeFilter: ['open'],
                childList: true,
                subtree: true,
            });
        },

        destroy() {
            this.observador?.disconnect();
            cancelAnimationFrame(this.quadro);
        },
    }));
};

if (window.Alpine) {
    registrarTriagem();
} else {
    document.addEventListener('alpine:init', registrarTriagem, { once: true });
}

document.addEventListener('DOMContentLoaded', () => {
    const applyCpfMask = (target) => {
        let value = target.value.replace(/\D/g, '');
        if (value.length > 11) {
            value = value.slice(0, 11);
        }
        if (value.length > 9) {
            target.value = value.replace(/^(\d{3})(\d{3})(\d{3})(\d{2})$/, '$1.$2.$3-$4');
        } else if (value.length > 6) {
            target.value = value.replace(/^(\d{3})(\d{3})(\d{0,3})$/, '$1.$2.$3');
        } else if (value.length > 3) {
            target.value = value.replace(/^(\d{3})(\d{0,3})$/, '$1.$2');
        } else {
            target.value = value;
        }
    };

    const isCpfInput = (target) => {
        return target && (
            target.id?.startsWith('num_cpf_') || 
            target.name?.includes('num_cpf_') || 
            target.classList?.contains('mask-cpf')
        );
    };

    // Escuta eventos de input para formatação em tempo real (inclui dinâmicos)
    document.addEventListener('input', function (e) {
        if (isCpfInput(e.target)) {
            applyCpfMask(e.target);
        }
    });

    // Formata o valor inicial já presente ao carregar a página
    document.querySelectorAll('input').forEach(input => {
        if (isCpfInput(input) && input.value) {
            applyCpfMask(input);
        }
    });
});
