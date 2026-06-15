<?php

use App\Models\Evento;
use App\Models\Mensagem;
use App\Models\MensagemEnvio;
use App\Models\Participante;
use App\Models\Pessoa;
use App\Models\Ficha;
use App\Models\FichaVem;
use App\Models\FichaSGM;
use App\Models\TipoMovimento;
use App\Models\User;
use Livewire\Volt\Volt;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    createMovimentos();
    $this->movimento = TipoMovimento::first();

    // Criar usuários
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->coord = User::factory()->create(['role' => 'coord']);
    $this->espec = User::factory()->create([
        'role' => 'espec',
        'idt_movimento' => $this->movimento->idt_movimento,
    ]);
    $this->user = User::factory()->create(['role' => 'user']);

    // Criar evento
    $this->evento = Evento::factory()->create([
        'idt_movimento' => $this->movimento->idt_movimento,
        'des_evento' => 'Evento Teste Mensagem',
    ]);
});

// ─────────────────────────────────────────────────────────────────────────────
// 1. Autorização de Acesso
// ─────────────────────────────────────────────────────────────────────────────

describe('Autorização e Permissões', function () {
    test('guest é redirecionado ao tentar acessar mensagens', function () {
        $this->get(route('mensagens.index'))->assertRedirect(route('login'));
        $this->get(route('mensagens.create'))->assertRedirect(route('login'));
    });

    test('usuario comum (role user) recebe 403 ao acessar mensagens', function () {
        $this->actingAs($this->user);
        $this->get(route('mensagens.index'))->assertStatus(403);
        $this->get(route('mensagens.create'))->assertStatus(403);
    });

    test('gestores (admin, coord, espec) podem acessar index de mensagens', function () {
        foreach ([$this->admin, $this->coord, $this->espec] as $usuario) {
            $this->actingAs($usuario);
            $this->get(route('mensagens.index'))->assertStatus(200);
            $this->get(route('mensagens.create'))->assertStatus(200);
        }
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. Resolvedor de Spintax e Placeholders
// ─────────────────────────────────────────────────────────────────────────────

describe('Formatação de Mensagens (Spintax & Placeholders)', function () {
    test('Mensagem::formatar substitui placeholders corretamente', function () {
        $template = "Olá {nome} ({apelido}), o evento {evento} está chegando para o participante {participante}!";
        $data = [
            'nome' => 'Maria de Lourdes',
            'apelido' => 'Lurdinha',
            'evento' => 'XXX VEM Encontro',
            'participante' => 'Joãozinho',
        ];

        $resultado = Mensagem::formatar($template, $data);

        expect($resultado)->toBe("Olá Maria de Lourdes (Lurdinha), o evento XXX VEM Encontro está chegando para o participante Joãozinho!");
    });

    test('Mensagem::formatar resolve spintax escolhendo uma opção válida', function () {
        $template = "{Bom dia|Olá|Oi}, {nome}!";
        $data = ['nome' => 'Marcos'];

        $resultado = Mensagem::formatar($template, $data);

        // O resultado deve ser um dos três formatos possíveis
        expect($resultado)->toBeIn(["Bom dia, Marcos!", "Olá, Marcos!", "Oi, Marcos!"]);
    });

    test('Mensagem::formatar resolve spintax mesmo sem placeholders', function () {
        $template = "Nós {agradecemos|ficamos felizes com} a sua ajuda.";
        $resultado = Mensagem::formatar($template, []);

        expect($resultado)->toBeIn([
            "Nós agradecemos a sua ajuda.",
            "Nós ficamos felizes com a sua ajuda."
        ]);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. Componente de Criação de Campanha (Criação)
// ─────────────────────────────────────────────────────────────────────────────

describe('Componente Volt mensagens.create', function () {
    beforeEach(function () {
        $this->actingAs($this->admin);

        // Criar participantes para teste de contatos estimados
        $this->pessoa1 = Pessoa::factory()->create([
            'nom_pessoa' => 'Alice Medeiros',
            'tel_pessoa' => '61999998888',
        ]);
        $this->part1 = Participante::factory()->create([
            'idt_evento' => $this->evento->idt_evento,
            'idt_pessoa' => $this->pessoa1->idt_pessoa,
        ]);

        $this->pessoa2 = Pessoa::factory()->create([
            'nom_pessoa' => 'Bruno Alves',
            'tel_pessoa' => '61888887777',
        ]);
        $this->part2 = Participante::factory()->create([
            'idt_evento' => $this->evento->idt_evento,
            'idt_pessoa' => $this->pessoa2->idt_pessoa,
        ]);
    });

    test('renderiza formulário com valores padrão e lista eventos', function () {
        Volt::test('mensagens.create')
            ->assertSet('eventoId', $this->evento->idt_evento)
            ->assertSet('tip_destinatario', 'P')
            ->assertSee('Configurar Nova Mensagem')
            ->assertSee($this->evento->des_evento);
    });

    test('estimativa conta destinatários válidos para participante', function () {
        Volt::test('mensagens.create')
            ->set('eventoId', $this->evento->idt_evento)
            ->set('tip_destinatario', 'P')
            ->assertSet('destinatariosEstimados', [
                [
                    'nom_destinatario' => 'Alice Medeiros',
                    'tel_destinatario' => '61999998888',
                    'nom_responsavel' => null,
                ],
                [
                    'nom_destinatario' => 'Bruno Alves',
                    'tel_destinatario' => '61888887777',
                    'nom_responsavel' => null,
                ]
            ]);
    });

    test('criarCampanha persiste registros e redireciona', function () {
        Volt::test('mensagens.create')
            ->set('eventoId', $this->evento->idt_evento)
            ->set('nom_campanha', 'Lembrete Geral')
            ->set('txt_mensagem', 'Olá {nome}, seu evento é {evento}.')
            ->set('tip_destinatario', 'P')
            ->call('criarCampanha')
            ->assertHasNoErrors()
            ->assertRedirect(); // Redireciona para mensagens.show

        $this->assertDatabaseHas('mensagem', [
            'nom_campanha' => 'Lembrete Geral',
            'tip_destinatario' => 'P',
            'qtd_impactados' => 2,
        ]);

        $this->assertDatabaseHas('mensagem_envio', [
            'nom_destinatario' => 'Alice Medeiros',
            'tel_destinatario' => '61999998888',
            'ind_enviado' => false,
        ]);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// 4. Componente de Visualização e Execução (Show)
// ─────────────────────────────────────────────────────────────────────────────

describe('Componente Volt mensagens.show', function () {
    beforeEach(function () {
        $this->actingAs($this->admin);

        // Criar campanha de mensagem
        $this->mensagem = Mensagem::create([
            'idt_evento' => $this->evento->idt_evento,
            'usu_inclusao' => $this->admin->id,
            'nom_campanha' => 'Campanha Ativa',
            'txt_mensagem' => 'Aviso: {nome}',
            'tip_destinatario' => 'P',
            'qtd_impactados' => 2,
        ]);

        // Criar destinatários individuais
        $this->envio1 = MensagemEnvio::create([
            'idt_mensagem' => $this->mensagem->idt_mensagem,
            'nom_destinatario' => 'Daniel',
            'tel_destinatario' => '61991112222',
            'ind_enviado' => false,
        ]);

        $this->envio2 = MensagemEnvio::create([
            'idt_mensagem' => $this->mensagem->idt_mensagem,
            'nom_destinatario' => 'Eduarda',
            'tel_destinatario' => '61992223333',
            'ind_enviado' => false,
        ]);
    });

    test('renderiza campanha e lista contatos corretamente', function () {
        Volt::test('mensagens.show', ['mensagem' => $this->mensagem])
            ->assertSee('Campanha Ativa')
            ->assertSee('Daniel')
            ->assertSee('Eduarda')
            ->assertSee('0')
            ->assertSee('/ 2 enviados');
    });

    test('marcarComoEnviado atualiza status do destinatário', function () {
        Volt::test('mensagens.show', ['mensagem' => $this->mensagem])
            ->call('marcarComoEnviado', $this->envio1->idt_mensagem_envio)
            ->assertHasNoErrors()
            ->assertDispatched('notify');

        expect($this->envio1->fresh()->ind_enviado)->toBeTrue()
            ->and($this->envio1->fresh()->dat_envio)->not->toBeNull();
    });

    test('resetarEnvio reverte status para pendente', function () {
        $this->envio1->update(['ind_enviado' => true, 'dat_envio' => now()]);

        Volt::test('mensagens.show', ['mensagem' => $this->mensagem])
            ->call('resetarEnvio', $this->envio1->idt_mensagem_envio)
            ->assertHasNoErrors()
            ->assertDispatched('notify');

        expect($this->envio1->fresh()->ind_enviado)->toBeFalse()
            ->and($this->envio1->fresh()->dat_envio)->toBeNull();
    });

    test('dispararProximoPendente atualiza proximo e despacha evento de abertura', function () {
        Volt::test('mensagens.show', ['mensagem' => $this->mensagem])
            ->call('dispararProximoPendente')
            ->assertHasNoErrors()
            ->assertDispatched('abrir-whatsapp')
            ->assertDispatched('notify');

        // O primeiro destinatário (Daniel) deve ser marcado como enviado
        expect($this->envio1->fresh()->ind_enviado)->toBeTrue();
        // O segundo (Eduarda) permanece pendente
        expect($this->envio2->fresh()->ind_enviado)->toBeFalse();
    });
});
