<?php

use App\Enums\TipoMovimento;
use App\Enums\TipoResponsavel;
use App\Enums\TipoRestricao;

test('TipoMovimento enum has correct values and methods', function () {
    expect(TipoMovimento::ECC->value)->toBe(1);
    expect(TipoMovimento::VEM->value)->toBe(2);
    expect(TipoMovimento::SGM->value)->toBe(3);

    expect(TipoMovimento::ECC->label())->toBe('Encontro de Casais com Cristo');
    expect(TipoMovimento::VEM->label())->toBe('Encontro de Adolescentes com Cristo');
    expect(TipoMovimento::SGM->label())->toBe('Encontro de Jovens com Cristo');

    expect(TipoMovimento::ECC->sigla())->toBe('ECC');
    expect(TipoMovimento::VEM->sigla())->toBe('VEM');
    expect(TipoMovimento::SGM->sigla())->toBe('Segue-Me');
});

test('TipoResponsavel enum has correct values and labels', function () {
    expect(TipoResponsavel::PAI->value)->toBe(1);
    expect(TipoResponsavel::MAE->value)->toBe(2);
    expect(TipoResponsavel::AVO_MASC->value)->toBe(3);
    expect(TipoResponsavel::AVO_FEM->value)->toBe(4);
    expect(TipoResponsavel::TIO->value)->toBe(5);
    expect(TipoResponsavel::TIA->value)->toBe(6);
    expect(TipoResponsavel::PADRINHO->value)->toBe(7);
    expect(TipoResponsavel::MADRINHA->value)->toBe(8);
    expect(TipoResponsavel::OUTRO->value)->toBe(9);

    expect(TipoResponsavel::PAI->label())->toBe('Pai');
    expect(TipoResponsavel::MAE->label())->toBe('Mãe');
    expect(TipoResponsavel::AVO_MASC->label())->toBe('Avô');
    expect(TipoResponsavel::AVO_FEM->label())->toBe('Avó');
    expect(TipoResponsavel::TIO->label())->toBe('Tio');
    expect(TipoResponsavel::TIA->label())->toBe('Tia');
    expect(TipoResponsavel::PADRINHO->label())->toBe('Padrinho');
    expect(TipoResponsavel::MADRINHA->label())->toBe('Madrinha');
    expect(TipoResponsavel::OUTRO->label())->toBe('Outro(a)');
});

test('TipoRestricao enum has correct cases', function () {
    expect(TipoRestricao::INT->value)->toBe('INT');
    expect(TipoRestricao::ALE->value)->toBe('ALE');
    expect(TipoRestricao::CUT->value)->toBe('CUT');
    expect(TipoRestricao::RES->value)->toBe('RES');
    expect(TipoRestricao::PNE->value)->toBe('PNE');
    expect(TipoRestricao::VEG->value)->toBe('VEG');
    expect(TipoRestricao::MED->value)->toBe('MED');

    expect(TipoRestricao::INT->label())->toBe('Intolerância');
    expect(TipoRestricao::ALE->label())->toBe('Alergia');
    expect(TipoRestricao::VEG->label())->toBe('Vegetariano');
});
