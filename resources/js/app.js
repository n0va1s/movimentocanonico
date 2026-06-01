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
