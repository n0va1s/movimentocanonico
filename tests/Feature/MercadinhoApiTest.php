<?php

use App\Models\Pessoa;
use App\Models\Evento;
use App\Models\TipoEquipe;
use App\Models\Trabalhador;
use App\Models\Participante;
use App\Models\TipoMovimento;
use Illuminate\Testing\Fluent\AssertableJson;

beforeEach(function () {
    createMovimentos(); // Cria os tipos de movimento base e equipes padrão
});

test('retorna 404 com mensagem amigável quando pessoa não existe', function () {
    $response = $this->getJson('/api/mercadinho/pessoas/999999');

    $response->assertStatus(404)
        ->assertJson([
            'sucesso' => false,
            'mensagem' => 'Pessoa não encontrada no banco de dados.'
        ]);
});

test('retorna dados da pessoa com vínculos vazios se ela não tiver nenhum', function () {
    $pessoa = Pessoa::factory()->create([
        'nom_pessoa' => 'João da Silva',
        'nom_apelido' => 'Joãozinho',
        'num_cpf_pessoa' => '12345678901',
        'tel_pessoa' => '61999999999',
        'eml_pessoa' => 'joao@example.com',
        'tip_genero' => 'M',
        'des_endereco' => 'SHTN Trecho 1, Lote 2',
        'idt_parceiro' => null,
    ]);

    $response = $this->getJson("/api/mercadinho/pessoas/{$pessoa->idt_pessoa}");

    $response->assertStatus(200)
        ->assertJson(fn (AssertableJson $json) =>
            $json->has('sucesso')
                ->where('sucesso', true)
                ->has('cliente', fn (AssertableJson $sub) =>
                    $sub->where('id_pessoa', $pessoa->idt_pessoa)
                        ->where('nome', 'João da Silva')
                        ->where('apelido', 'Joãozinho')
                        ->where('cpf', $pessoa->num_cpf_pessoa) // O model pode ter casts/mutators para formatar CPF/Telefone
                        ->where('telefone', $pessoa->tel_pessoa)
                        ->where('email', 'joao@example.com')
                        ->where('data_nascimento', $pessoa->getDataNascimentoFormatada())
                        ->where('sexo', 'M')
                        ->where('endereco', 'SHTN Trecho 1, Lote 2')
                )
                ->where('vinculos_eventos', [])
        );
});

test('retorna dados e mapeia vínculos de participante e trabalhador corretamente', function () {
    $pessoa = Pessoa::factory()->create([
        'idt_parceiro' => null
    ]);

    $movimento = TipoMovimento::first();
    $evento1 = Evento::factory()->create(['idt_movimento' => $movimento->idt_movimento]);
    $evento2 = Evento::factory()->create(['idt_movimento' => $movimento->idt_movimento]);

    $equipe = TipoEquipe::firstOrCreate(
        ['des_grupo' => 'Alimentação'],
        ['idt_movimento' => $movimento->idt_movimento]
    );

    // Cria um vínculo de participante
    $participante = Participante::factory()->create([
        'idt_pessoa' => $pessoa->idt_pessoa,
        'idt_evento' => $evento1->idt_evento,
        'tip_cor_troca' => 'Verde'
    ]);

    // Cria um vínculo de trabalhador
    $trabalhador = Trabalhador::factory()->create([
        'idt_pessoa' => $pessoa->idt_pessoa,
        'idt_evento' => $evento2->idt_evento,
        'idt_equipe' => $equipe->idt_equipe
    ]);

    $response = $this->getJson("/api/mercadinho/pessoas/{$pessoa->idt_pessoa}");

    $response->assertStatus(200)
        ->assertJson(fn (AssertableJson $json) =>
            $json->where('sucesso', true)
                ->has('cliente')
                ->has('vinculos_eventos', 2)
                ->has('vinculos_eventos.0', fn (AssertableJson $sub) =>
                    $sub->where('id_evento', $evento1->idt_evento)
                        ->where('nome_evento', $evento1->des_evento)
                        ->where('perfil', 'participante')
                        ->where('equipe', null)
                        ->where('cor_troca', 'Verde')
                )
                ->has('vinculos_eventos.1', fn (AssertableJson $sub) =>
                    $sub->where('id_evento', $evento2->idt_evento)
                        ->where('nome_evento', $evento2->des_evento)
                        ->where('perfil', 'trabalhador')
                        ->where('equipe', 'Alimentação')
                        ->where('cor_troca', null)
                )
        );
});
