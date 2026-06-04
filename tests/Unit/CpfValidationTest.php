<?php

use App\Services\CpfService;
use App\Rules\Cpf;

test('pode formatar CPF com mascara', function () {
    expect(CpfService::format('12345678901'))->toBe('123.456.789-01');
    expect(CpfService::format('123.456.789-01'))->toBe('123.456.789-01');
    expect(CpfService::format(''))->toBeNull();
});

test('pode limpar CPF removendo mascara', function () {
    expect(CpfService::clean('123.456.789-01'))->toBe('12345678901');
    expect(CpfService::clean(''))->toBeNull();
});

test('valida CPFs validos e invalidos estritamente', function () {
    // CPFs válidos conhecidos matematicamente (validação estrita)
    expect(CpfService::validate('52998224725', true))->toBe(true);
    expect(CpfService::validate('529.982.247-25', true))->toBe(true);
    expect(CpfService::validate('12345678909', true))->toBe(true);
    expect(CpfService::validate('123.456.789-09', true))->toBe(true);

    // CPFs inválidos conhecidos (validação estrita)
    expect(CpfService::validate('11111111111', true))->toBe(false);
    expect(CpfService::validate('123.456.789-00', true))->toBe(false);
    expect(CpfService::validate('123', true))->toBe(false);
    expect(CpfService::validate('', true))->toBe(false);
});

test('valida comportamento flexivel em ambiente de testes por padrao', function () {
    // Como estamos no ambiente de testes, a validação padrão de sequências ou CPFs matematicamente
    // inválidos deve retornar true se tiverem 11 dígitos.
    expect(CpfService::validate('11111111111'))->toBe(true);
    expect(CpfService::validate('12345678901'))->toBe(true);
    // Mas se tiver tamanho inválido, continua retornando false
    expect(CpfService::validate('123'))->toBe(false);
});
