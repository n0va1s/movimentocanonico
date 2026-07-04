<?php

return [
    'alerts' => [
        'error' => [
            'ficha_update_error' => 'Erro ao atualizar situação: :error',
            'insufficient_stock' => 'Estoque insuficiente para :product (Disponível: :available).',
            'product_not_found' => 'Produto não encontrado.',
        ],
        'warning' => [
            'no_allergies' => 'Nenhuma restrição ou alergia foi informada. Desmarque a opção Informações de Saúde por favor.',
        ],
        'info' => [
            // Textos explicativos em alertas aqui
        ],
        'success' => [
            'saved' => 'Registro salvo com sucesso!',
            'deleted' => 'Registro excluído com sucesso.',
            'ficha_updated' => 'Situação da ficha atualizada com sucesso!',
            'product_updated' => 'Produto atualizado com sucesso!',
            'product_created' => 'Produto cadastrado com sucesso!',
            'product_deleted' => 'Produto removido com sucesso!',
            'purchase_registered' => 'Compra registrada com sucesso!',
            'avulsa_registered' => 'Venda avulsa registrada com sucesso!',
            'credit_registered' => 'Crédito/Pagamento registrado com sucesso!',
            'transaction_reversed' => 'Transação estornada com sucesso!',
        ],
    ],

    'empty' => [
        'trabalhador' => [
            'title' => 'Nenhum(a) trabalhador(a) encontrado(a)',
            'description' => 'Quando houver trabalhadores(as) cadastrados(as), eles(as) aparecerão aqui.',
        ],
        'evento' => [
            'no_active' => 'Nenhum evento ativo cadastrado',
        ],
    ],

    'hints' => [
        'required' => '(obrigatório)',
        'optional' => '(opcional)',
    ],

    'api' => [
        'candidate_not_found' => 'Candidato ainda não existe, preenchimento manual necessário.',
    ],

    'terms' => [
        'termoSGM' => [
            'updates' => 'A Paróquia Nossa Senhora do Lago reserva-se o direito de atualizar estes termos a qualquer tempo, para melhor atender às exigências legais e às necessidades de organização. Alterações substanciais serão comunicadas ao participante pelos canais de contato cadastrados com antecedência razoável.',
            'data_retention' => 'Após o término da relação do participante com o movimento, os dados serão anonimizados ou eliminados, salvo quando sua conservação for obrigatória por lei.',
        ]
    ]
];
