<?php

use App\Enums\FaixaEtaria;
use App\Enums\TipoEvento;
use App\Models\Evento;
use App\Models\EventoFoto;
use App\Models\Participante;
use App\Models\Pessoa;
use App\Models\TipoEquipe;
use App\Models\TipoMovimento;
use App\Models\Trabalhador;
use App\Models\User;
use App\Services\EventoService;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

// ─────────────────────────────────────────────────────────────────────────────
// Setup compartilhado
// ─────────────────────────────────────────────────────────────────────────────

beforeEach(function () {
    $this->eventoService = new EventoService;

    createMovimentos();

    $this->movimento = TipoMovimento::first();
    $this->user = User::factory()->create(['role' => 'admin']);
    $this->pessoa = $this->user->pessoa;

    $this->actingAs($this->user);

    Storage::fake('public');
});

// ─────────────────────────────────────────────────────────────────────────────
// Helper local — payload mínimo válido
// ─────────────────────────────────────────────────────────────────────────────

function eventoPayloadValido(int $idtMovimento, array $overrides = []): array
{
    return array_merge([
        'idt_movimento' => $idtMovimento,
        'des_evento' => 'Encontro de Teste',
        'num_evento' => '001',
        'dat_inicio' => '2025-06-20',
        'dat_termino' => '2025-06-22',
        'dat_limite_inscricao' => '2025-06-10',
        'qtd_vaga' => 40,
        'tip_evento' => TipoEvento::ENCONTRO->value,
        'tip_faixa_etaria' => FaixaEtaria::LIVRE->value,
        'val_trabalhador' => '50.00',
        'val_venista' => '80.00',
        'val_camiseta' => '30.00',
        'txt_informacao' => 'Informações gerais do evento.',
    ], $overrides);
}

// ─────────────────────────────────────────────────────────────────────────────
// Evento Model
// ─────────────────────────────────────────────────────────────────────────────

describe('Evento Model', function () {

    test('fillable contém todos os campos esperados', function () {
        $expected = [
            'idt_movimento', 'des_evento', 'num_evento',
            'dat_inicio', 'dat_termino', 'dat_limite_inscricao', 'qtd_vaga',
            'val_camiseta', 'val_trabalhador', 'val_venista', 'val_entrada',
            'val_receita', 'val_despesa',
            'tip_evento', 'tip_faixa_etaria',
            'txt_informacao', 'txt_relatorio',
            'med_foto', 'med_logo',
        ];

        expect((new Evento)->getFillable())->toBe($expected);
    });

    test('casts de datas retornam instâncias Carbon', function () {
        $evento = Evento::factory()->create([
            'dat_inicio' => '2025-06-20',
            'dat_termino' => '2025-06-22',
            'dat_limite_inscricao' => '2025-06-10',
        ]);

        expect($evento->dat_inicio)->toBeInstanceOf(Carbon::class)
            ->and($evento->dat_termino)->toBeInstanceOf(Carbon::class)
            ->and($evento->dat_limite_inscricao)->toBeInstanceOf(Carbon::class);
    });

    test('cast de tip_evento retorna enum TipoEvento', function () {
        $evento = Evento::factory()->create(['tip_evento' => TipoEvento::ENCONTRO->value]);

        expect($evento->tip_evento)->toBeInstanceOf(TipoEvento::class)
            ->and($evento->tip_evento)->toBe(TipoEvento::ENCONTRO);
    });

    test('cast de tip_faixa_etaria retorna enum FaixaEtaria', function () {
        $evento = Evento::factory()->create(['tip_faixa_etaria' => FaixaEtaria::LIVRE->value]);

        expect($evento->tip_faixa_etaria)->toBeInstanceOf(FaixaEtaria::class)
            ->and($evento->tip_faixa_etaria)->toBe(FaixaEtaria::LIVRE);
    });

    test('soft delete não remove o registro do banco', function () {
        $evento = Evento::factory()->create();
        $id = $evento->idt_evento;

        $evento->delete();

        expect(Evento::find($id))->toBeNull()
            ->and(Evento::withTrashed()->find($id))->not->toBeNull();
    });

    test('getDataInicioFormatada retorna data no formato d/m/Y', function () {
        $evento = Evento::factory()->create(['dat_inicio' => '2025-06-20']);

        expect($evento->getDataInicioFormatada())->toBe('20/06/2025');
    });

    test('getDataTerminoFormatada retorna null quando dat_termino é null', function () {
        $evento = Evento::factory()->create(['dat_termino' => null]);

        expect($evento->getDataTerminoFormatada())->toBeNull();
    });

    test('scope search filtra por des_evento (case-insensitive)', function () {
        Evento::factory()->create(['des_evento' => 'Encontro de Jovens']);
        Evento::factory()->create(['des_evento' => 'Retiro Espiritual']);

        $resultado = Evento::search('JOVENS')->get();

        expect($resultado)->toHaveCount(1)
            ->and($resultado->first()->des_evento)->toBe('Encontro de Jovens');
    });

    test('scope search filtra por num_evento', function () {
        Evento::factory()->create(['num_evento' => 'EJ001']);
        Evento::factory()->create(['num_evento' => 'RE002']);

        $resultado = Evento::search('EJ')->get();

        expect($resultado)->toHaveCount(1)
            ->and($resultado->first()->num_evento)->toBe('EJ001');
    });

    test('scope movimento filtra por idt_movimento', function () {
        $mov2 = TipoMovimento::factory()->create();
        Evento::factory()->create(['idt_movimento' => $this->movimento->idt_movimento]);
        Evento::factory()->create(['idt_movimento' => $mov2->idt_movimento]);

        // Usa query() para evitar conflito com o relacionamento movimento()
        $resultado = Evento::query()->movimento($this->movimento->idt_movimento)->get();

        expect($resultado)->toHaveCount(1)
            ->and($resultado->first()->idt_movimento)->toBe($this->movimento->idt_movimento);
    });

    test('getByTipo retorna eventos filtrados por movimento e tipo', function () {
        Evento::factory()->create([
            'idt_movimento' => $this->movimento->idt_movimento,
            'tip_evento' => TipoEvento::ENCONTRO->value,
        ]);
        Evento::factory()->create([
            'idt_movimento' => $this->movimento->idt_movimento,
            'tip_evento' => TipoEvento::POS_ENCONTRO->value,
        ]);

        $resultado = Evento::getByTipo($this->movimento->idt_movimento, TipoEvento::ENCONTRO->value);

        expect($resultado)->toHaveCount(1)
            ->and($resultado->first()->tip_evento)->toBe(TipoEvento::ENCONTRO);
    });

    test('getByTipo respeita o limite quando informado', function () {
        Evento::factory()->count(5)->create([
            'idt_movimento' => $this->movimento->idt_movimento,
            'tip_evento' => TipoEvento::ENCONTRO->value,
        ]);

        $resultado = Evento::getByTipo($this->movimento->idt_movimento, TipoEvento::ENCONTRO->value, 3);

        expect($resultado)->toHaveCount(3);
    });

    test('relacionamento com movimento funciona', function () {
        $evento = Evento::factory()->create(['idt_movimento' => $this->movimento->idt_movimento]);

        expect($evento->movimento)->toBeInstanceOf(TipoMovimento::class)
            ->and($evento->movimento->idt_movimento)->toBe($this->movimento->idt_movimento);
    });

    test('relacionamento com foto funciona', function () {
        $evento = Evento::factory()->create();
        $evento->foto()->create(['med_foto' => 'eventos/fotos/teste.jpg']);

        expect($evento->foto)->toBeInstanceOf(EventoFoto::class)
            ->and($evento->foto->med_foto)->toBe('eventos/fotos/teste.jpg');
    });

    test('relacionamento com participantes funciona', function () {
        $evento = Evento::factory()->create();
        Participante::factory()->for($evento)->create();

        expect($evento->participantes)->toHaveCount(1);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// EventoFoto Model
// ─────────────────────────────────────────────────────────────────────────────

describe('EventoFoto Model', function () {

    test('fillable contém med_foto e med_logo', function () {
        expect((new EventoFoto)->getFillable())
            ->toContain('med_foto')
            ->toContain('med_logo');
    });

    test('relacionamento com evento funciona', function () {
        $evento = Evento::factory()->create();
        $foto = EventoFoto::create([
            'idt_evento' => $evento->idt_evento,
            'med_foto' => 'eventos/fotos/teste.jpg',
        ]);

        expect($foto->evento)->toBeInstanceOf(Evento::class)
            ->and($foto->evento->idt_evento)->toBe($evento->idt_evento);
    });

    test('med_logo pode ser null', function () {
        $evento = Evento::factory()->create();
        $foto = EventoFoto::create([
            'idt_evento' => $evento->idt_evento,
            'med_foto' => 'eventos/fotos/teste.jpg',
            'med_logo' => null,
        ]);

        expect($foto->med_logo)->toBeNull();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// EventoController — Index
// ─────────────────────────────────────────────────────────────────────────────

describe('EventoController — Index', function () {

    test('exibe listagem para usuário autenticado', function () {
        Evento::factory()->count(3)->create(['idt_movimento' => $this->movimento->idt_movimento]);

        $response = $this->get(route('eventos.index'));

        $response->assertOk()
            ->assertViewIs('evento.list')
            ->assertViewHas('eventos')
            ->assertViewHas('movimentos');
    });

    test('exibe listagem para usuário não autenticado', function () {
        // O index é público — usuário não autenticado pode acessar
        // mas o controller usa Auth::user() que retorna null
        $this->post(route('logout'));
        Evento::factory()->count(2)->create();

        $response = $this->get(route('eventos.index'));

        // Redireciona para login pois a rota requer autenticação
        $response->assertRedirect(route('login'));
    });

    test('filtra eventos por busca', function () {
        $encontrado = Evento::factory()->create(['des_evento' => 'Encontro Especial']);
        $naoEncontrado = Evento::factory()->create(['des_evento' => 'Outro Evento']);

        $response = $this->get(route('eventos.index', ['search' => 'especial']));

        $response->assertOk();
        $eventos = $response->viewData('eventos');

        expect($eventos->items())->toHaveCount(1)
            ->and($eventos->items()[0]->idt_evento)->toBe($encontrado->idt_evento);
    });

    test('filtra eventos por movimento', function () {
        $mov2 = TipoMovimento::factory()->create();
        Evento::factory()->create(['idt_movimento' => $this->movimento->idt_movimento]);
        Evento::factory()->create(['idt_movimento' => $mov2->idt_movimento]);

        $response = $this->get(route('eventos.index', ['idt_movimento' => $this->movimento->idt_movimento]));

        $response->assertOk();
        $eventos = $response->viewData('eventos');

        expect($eventos->items())->toHaveCount(1)
            ->and($eventos->items()[0]->idt_movimento)->toBe($this->movimento->idt_movimento);
    });

    test('filtra eventos por tipo de evento', function () {
        $encontro = Evento::factory()->create(['tip_evento' => TipoEvento::ENCONTRO->value]);
        $desafio = Evento::factory()->create(['tip_evento' => TipoEvento::DESAFIO->value]);

        $response = $this->get(route('eventos.index', ['tip_evento' => TipoEvento::ENCONTRO->value]));

        $response->assertOk();
        $eventos = $response->viewData('eventos');

        expect($eventos->items())->toHaveCount(1)
            ->and($eventos->items()[0]->idt_evento)->toBe($encontro->idt_evento);
    });

    test('paginação mantém parâmetros de busca', function () {
        Evento::factory()->count(15)->create(['des_evento' => 'Encontro Teste']);

        $response = $this->get(route('eventos.index', ['search' => 'teste', 'page' => 2]));

        $response->assertOk();
        expect($response->viewData('eventos')->hasPages())->toBeTrue();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// EventoController — Create
// ─────────────────────────────────────────────────────────────────────────────

describe('EventoController — Create', function () {

    test('exibe formulário de criação', function () {
        $response = $this->get(route('eventos.create'));

        $response->assertOk()
            ->assertViewIs('evento.form')
            ->assertViewHas('movimentos')
            ->assertViewHas('evento');
    });

    test('formulário de criação carrega todos os movimentos', function () {
        $total = TipoMovimento::count();

        $response = $this->get(route('eventos.create'));

        expect($response->viewData('movimentos'))->toHaveCount($total);
    });

    test('redireciona para login quando não autenticado', function () {
        Auth::logout();

        $response = $this->get(route('eventos.create'));

        $response->assertRedirect(route('login'));
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// EventoController — Store
// ─────────────────────────────────────────────────────────────────────────────

describe('EventoController — Store', function () {

    test('cria evento com dados válidos e redireciona', function () {
        $payload = eventoPayloadValido($this->movimento->idt_movimento);

        $response = $this->post(route('eventos.store'), $payload);

        $response->assertRedirect(route('eventos.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('evento', [
            'des_evento' => 'Encontro de Teste',
            'num_evento' => '001',
            'tip_evento' => TipoEvento::ENCONTRO->value,
            'tip_faixa_etaria' => FaixaEtaria::LIVRE->value,
            'qtd_vaga' => 40,
        ]);
    });

    test('persiste campos novos: tip_faixa_etaria, qtd_vaga e dat_limite_inscricao', function () {
        $payload = eventoPayloadValido($this->movimento->idt_movimento, [
            'tip_faixa_etaria' => FaixaEtaria::JOVEM->value,
            'qtd_vaga' => 55,
            'dat_limite_inscricao' => '2025-06-15',
        ]);

        $this->post(route('eventos.store'), $payload);

        $evento = Evento::where('des_evento', 'Encontro de Teste')->first();

        expect($evento->tip_faixa_etaria)->toBe(FaixaEtaria::JOVEM)
            ->and($evento->qtd_vaga)->toBe(55)
            ->and($evento->dat_limite_inscricao->format('Y-m-d'))->toBe('2025-06-15');
    });

    test('persiste val_receita e val_despesa', function () {
        $payload = eventoPayloadValido($this->movimento->idt_movimento, [
            'val_receita' => '1500.00',
            'val_despesa' => '800.00',
        ]);

        $this->post(route('eventos.store'), $payload);

        $this->assertDatabaseHas('evento', [
            'val_receita' => 1500.00,
            'val_despesa' => 800.00,
        ]);
    });

    test('faz upload de med_foto e cria registro em evento_foto', function () {
        $payload = eventoPayloadValido($this->movimento->idt_movimento, [
            'med_foto' => UploadedFile::fake()->image('foto.jpg'),
        ]);

        $response = $this->withoutExceptionHandling()
            ->from(route('eventos.index'))
            ->post(route('eventos.store'), $payload);

        // Verifica o que está na sessão para debug
        $sessionErrors = session('errors');
        $sessionError = session('error');

        $response->assertRedirect(route('eventos.index'))
            ->assertSessionHas('success');

        $evento = Evento::where('des_evento', 'Encontro de Teste')->with('foto')->first();

        expect($evento->foto)->not->toBeNull()
            ->and($evento->foto->med_foto)->not->toBeNull();

        Storage::disk('public')->assertExists($evento->foto->med_foto);
    });

    test('faz upload de med_logo e persiste em evento_foto', function () {
        $payload = eventoPayloadValido($this->movimento->idt_movimento, [
            'med_foto' => UploadedFile::fake()->image('foto.png'),
            'med_logo' => UploadedFile::fake()->image('logo.png'),
        ]);

        $response = $this->withoutExceptionHandling()
            ->from(route('eventos.index'))
            ->post(route('eventos.store'), $payload);

        $response->assertRedirect(route('eventos.index'))
            ->assertSessionHas('success');

        $evento = Evento::where('des_evento', 'Encontro de Teste')->with('foto')->first();

        expect($evento->foto)->not->toBeNull()
            ->and($evento->foto->med_logo)->not->toBeNull();

        Storage::disk('public')->assertExists($evento->foto->med_logo);
    });

    test('falha quando des_evento está vazio', function () {
        $payload = eventoPayloadValido($this->movimento->idt_movimento, ['des_evento' => '']);

        $response = $this->post(route('eventos.store'), $payload);

        $response->assertSessionHasErrors('des_evento');
        $this->assertDatabaseCount('evento', 0);
    });

    test('falha quando idt_movimento não existe', function () {
        $payload = eventoPayloadValido(99999);

        $response = $this->post(route('eventos.store'), $payload);

        $response->assertSessionHasErrors('idt_movimento');
    });

    test('falha quando dat_inicio está ausente', function () {
        $payload = eventoPayloadValido($this->movimento->idt_movimento, ['dat_inicio' => '']);

        $response = $this->post(route('eventos.store'), $payload);

        $response->assertSessionHasErrors('dat_inicio');
    });

    test('falha quando dat_termino é anterior a dat_inicio', function () {
        $payload = eventoPayloadValido($this->movimento->idt_movimento, [
            'dat_inicio' => '2025-06-20',
            'dat_termino' => '2025-06-18',
        ]);

        $response = $this->post(route('eventos.store'), $payload);

        $response->assertSessionHasErrors('dat_termino');
    });

    test('falha quando dat_limite_inscricao é posterior a dat_inicio', function () {
        $payload = eventoPayloadValido($this->movimento->idt_movimento, [
            'dat_inicio' => '2025-06-20',
            'dat_limite_inscricao' => '2025-06-25',
        ]);

        $response = $this->post(route('eventos.store'), $payload);

        $response->assertSessionHasErrors('dat_limite_inscricao');
    });

    test('falha quando tip_evento está ausente', function () {
        $payload = eventoPayloadValido($this->movimento->idt_movimento, ['tip_evento' => '']);

        $response = $this->post(route('eventos.store'), $payload);

        $response->assertSessionHasErrors('tip_evento');
    });

    test('falha quando tip_faixa_etaria está ausente', function () {
        $payload = eventoPayloadValido($this->movimento->idt_movimento, ['tip_faixa_etaria' => '']);

        $response = $this->post(route('eventos.store'), $payload);

        $response->assertSessionHasErrors('tip_faixa_etaria');
    });

    test('falha quando qtd_vaga é negativo', function () {
        $payload = eventoPayloadValido($this->movimento->idt_movimento, ['qtd_vaga' => -1]);

        $response = $this->post(route('eventos.store'), $payload);

        $response->assertSessionHasErrors('qtd_vaga');
    });

    test('falha quando val_trabalhador é negativo', function () {
        $payload = eventoPayloadValido($this->movimento->idt_movimento, ['val_trabalhador' => '-10']);

        $response = $this->post(route('eventos.store'), $payload);

        $response->assertSessionHasErrors('val_trabalhador');
    });

    test('falha quando med_foto excede 2MB', function () {
        $payload = eventoPayloadValido($this->movimento->idt_movimento, [
            'med_foto' => UploadedFile::fake()->image('grande.jpg')->size(3000),
        ]);

        $response = $this->post(route('eventos.store'), $payload);

        $response->assertSessionHasErrors('med_foto');
    });

    test('falha quando med_logo não é imagem', function () {
        $payload = eventoPayloadValido($this->movimento->idt_movimento, [
            'med_logo' => UploadedFile::fake()->create('logo.pdf', 100, 'application/pdf'),
        ]);

        $response = $this->post(route('eventos.store'), $payload);

        $response->assertSessionHasErrors('med_logo');
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// EventoController — Edit
// ─────────────────────────────────────────────────────────────────────────────

describe('EventoController — Edit', function () {

    test('exibe formulário de edição com dados do evento', function () {
        $evento = Evento::factory()->create();

        $response = $this->get(route('eventos.edit', $evento));

        $response->assertOk()
            ->assertViewIs('evento.form')
            ->assertViewHas('movimentos');

        expect($response->viewData('evento')->idt_evento)->toBe($evento->idt_evento);
    });

    test('carrega relações foto e logo para edição', function () {
        $evento = Evento::factory()->create();
        $evento->foto()->create([
            'med_foto' => 'eventos/fotos/foto.jpg',
            'med_logo' => 'eventos/logos/logo.png',
        ]);

        $response = $this->get(route('eventos.edit', $evento));

        $eventoView = $response->viewData('evento');

        expect($eventoView->relationLoaded('foto'))->toBeTrue()
            ->and($eventoView->foto->med_foto)->toBe('eventos/fotos/foto.jpg')
            ->and($eventoView->foto->med_logo)->toBe('eventos/logos/logo.png');
    });

    test('retorna 404 para evento inexistente', function () {
        $response = $this->get(route('eventos.edit', 99999));

        $response->assertNotFound();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// EventoController — Update
// ─────────────────────────────────────────────────────────────────────────────

describe('EventoController — Update', function () {

    test('atualiza evento com dados válidos', function () {
        $evento = Evento::factory()->create([
            'des_evento' => 'Evento Original',
            'dat_inicio' => '2025-06-20',
            'dat_termino' => '2025-06-22',
        ]);

        $payload = eventoPayloadValido($this->movimento->idt_movimento, [
            'des_evento' => 'Evento Atualizado',
            'tip_faixa_etaria' => FaixaEtaria::CASADO->value,
            'qtd_vaga' => 50,
        ]);

        $response = $this->put(route('eventos.update', $evento), $payload);

        $response->assertRedirect(route('eventos.index'))
            ->assertSessionHas('success');

        expect($evento->fresh()->des_evento)->toBe('Evento Atualizado')
            ->and($evento->fresh()->tip_faixa_etaria)->toBe(FaixaEtaria::CASADO)
            ->and($evento->fresh()->qtd_vaga)->toBe(50);
    });

    test('atualiza med_foto substituindo a anterior', function () {
        $evento = Evento::factory()->create();
        $evento->foto()->create(['med_foto' => 'eventos/fotos/antiga.jpg']);

        $payload = eventoPayloadValido($this->movimento->idt_movimento, [
            'dat_inicio' => $evento->dat_inicio->format('Y-m-d'),
            'dat_termino' => $evento->dat_termino?->format('Y-m-d'),
            'med_foto' => UploadedFile::fake()->image('nova.jpg'),
        ]);

        $response = $this->put(route('eventos.update', $evento), $payload);

        $response->assertRedirect(route('eventos.index'))
            ->assertSessionHas('success');

        $fotoAtualizada = $evento->fresh()->foto;

        expect($fotoAtualizada->med_foto)->not->toBe('eventos/fotos/antiga.jpg');
        Storage::disk('public')->assertExists($fotoAtualizada->med_foto);
    });

    test('atualiza med_logo sem afetar med_foto existente', function () {
        $evento = Evento::factory()->create();
        $evento->foto()->create([
            'med_foto' => 'eventos/fotos/foto.jpg',
            'med_logo' => null,
        ]);

        $payload = eventoPayloadValido($this->movimento->idt_movimento, [
            'dat_inicio' => $evento->dat_inicio->format('Y-m-d'),
            'dat_termino' => $evento->dat_termino?->format('Y-m-d'),
            'med_logo' => UploadedFile::fake()->image('logo.png'),
        ]);

        $response = $this->put(route('eventos.update', $evento), $payload);

        $response->assertRedirect(route('eventos.index'))
            ->assertSessionHas('success');

        $foto = $evento->fresh()->foto;

        expect($foto->med_foto)->toBe('eventos/fotos/foto.jpg')
            ->and($foto->med_logo)->not->toBeNull();
        Storage::disk('public')->assertExists($foto->med_logo);
    });

    test('falha quando des_evento está vazio na atualização', function () {
        $evento = Evento::factory()->create();

        $payload = eventoPayloadValido($this->movimento->idt_movimento, [
            'des_evento' => '',
            'dat_inicio' => $evento->dat_inicio->format('Y-m-d'),
            'dat_termino' => $evento->dat_termino?->format('Y-m-d'),
        ]);

        $response = $this->put(route('eventos.update', $evento), $payload);

        $response->assertSessionHasErrors('des_evento');
        expect($evento->fresh()->des_evento)->not->toBe('');
    });

    test('falha quando dat_termino é anterior a dat_inicio na atualização', function () {
        $evento = Evento::factory()->create();

        $payload = eventoPayloadValido($this->movimento->idt_movimento, [
            'dat_inicio' => '2025-06-20',
            'dat_termino' => '2025-06-18',
        ]);

        $response = $this->put(route('eventos.update', $evento), $payload);

        $response->assertSessionHasErrors('dat_termino');
    });

    test('retorna 404 ao tentar atualizar evento inexistente', function () {
        $payload = eventoPayloadValido($this->movimento->idt_movimento);

        $response = $this->put(route('eventos.update', 99999), $payload);

        $response->assertNotFound();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// EventoController — Destroy
// ─────────────────────────────────────────────────────────────────────────────

describe('EventoController — Destroy', function () {

    test('soft-deleta evento e redireciona com sucesso', function () {
        $evento = Evento::factory()->create();
        $id = $evento->idt_evento;

        $response = $this->delete(route('eventos.destroy', $evento));

        $response->assertRedirect(route('eventos.index'))
            ->assertSessionHas('success');

        expect(Evento::find($id))->toBeNull()
            ->and(Evento::withTrashed()->find($id))->not->toBeNull();
    });

    test('retorna 404 ao tentar deletar evento inexistente', function () {
        $response = $this->delete(route('eventos.destroy', 99999));

        $response->assertNotFound();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// EventoController — Confirm (participação)
// ─────────────────────────────────────────────────────────────────────────────

describe('EventoController — Confirm', function () {

    test('confirma participação e cria registro em participante', function () {
        $evento = Evento::factory()->create();

        $response = $this->post(route('participantes.confirm', [
            'evento' => $evento,
            'pessoa' => $this->pessoa,
        ]));

        $response->assertRedirect(route('eventos.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('participante', [
            'idt_evento' => $evento->idt_evento,
            'idt_pessoa' => $this->pessoa->idt_pessoa,
        ]);
    });

    test('confirmar participação duas vezes não duplica o registro', function () {
        $evento = Evento::factory()->create();

        $this->post(route('participantes.confirm', ['evento' => $evento, 'pessoa' => $this->pessoa]));
        $this->post(route('participantes.confirm', ['evento' => $evento, 'pessoa' => $this->pessoa]));

        $this->assertDatabaseCount('participante', 1);
    });

    test('usuario comum pode confirmar sua propria participacao', function () {
        $evento = Evento::factory()->create();
        $comumUser = User::factory()->create(['role' => 'user']);
        $comumPessoa = $comumUser->pessoa;

        $response = $this->actingAs($comumUser)->post(route('participantes.confirm', [
            'evento' => $evento,
            'pessoa' => $comumPessoa,
        ]));

        $response->assertRedirect(route('eventos.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('participante', [
            'idt_evento' => $evento->idt_evento,
            'idt_pessoa' => $comumPessoa->idt_pessoa,
        ]);
    });

    test('usuario comum nao pode confirmar participacao para outra pessoa', function () {
        $evento = Evento::factory()->create();
        $comumUser = User::factory()->create(['role' => 'user']);
        $outraPessoa = Pessoa::factory()->create();

        $response = $this->actingAs($comumUser)->post(route('participantes.confirm', [
            'evento' => $evento,
            'pessoa' => $outraPessoa,
        ]));

        $response->assertStatus(403);

        $this->assertDatabaseMissing('participante', [
            'idt_evento' => $evento->idt_evento,
            'idt_pessoa' => $outraPessoa->idt_pessoa,
        ]);
    });

    test('admin pode confirmar participacao para qualquer pessoa', function () {
        $evento = Evento::factory()->create();
        $outraPessoa = Pessoa::factory()->create();

        $response = $this->actingAs($this->user)->post(route('participantes.confirm', [
            'evento' => $evento,
            'pessoa' => $outraPessoa,
        ]));

        $response->assertRedirect(route('eventos.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('participante', [
            'idt_evento' => $evento->idt_evento,
            'idt_pessoa' => $outraPessoa->idt_pessoa,
        ]);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// EventoController — Timeline
// ─────────────────────────────────────────────────────────────────────────────

describe('EventoController — Timeline', function () {

    test('exibe timeline para usuário autenticado com pessoa vinculada', function () {
        $response = $this->get(route('timeline.index'));

        $response->assertOk()
            ->assertViewIs('evento.linhadotempo')
            ->assertViewHas('timeline')
            ->assertViewHas('pontuacaoTotal')
            ->assertViewHas('posicaoNoRanking')
            ->assertViewHas('pessoa');
    });

    test('redireciona para login quando não autenticado', function () {
        Auth::logout();

        $response = $this->get(route('timeline.index'));

        $response->assertRedirect(route('login'));
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// EventoService — Timeline
// ─────────────────────────────────────────────────────────────────────────────

describe('EventoService — Timeline', function () {

    test('retorna array vazio para pessoa sem eventos', function () {
        $timeline = $this->eventoService->getEventosTimeline($this->pessoa);

        expect($timeline)->toBeArray()->toBeEmpty();
    });

    test('retorna entrada de Trabalhador com dados corretos', function () {
        $evento = Evento::factory()->create([
            'idt_movimento' => $this->movimento->idt_movimento,
            'dat_inicio' => '2024-03-10',
        ]);

        $equipe = TipoEquipe::firstOrCreate([
            'des_grupo' => 'Coordenação Geral',
            'idt_movimento' => $this->movimento->idt_movimento,
        ]);

        Trabalhador::factory()->create([
            'idt_pessoa' => $this->pessoa->idt_pessoa,
            'idt_evento' => $evento->idt_evento,
            'idt_equipe' => $equipe->idt_equipe,
            'ind_coordenador' => true,
        ]);

        $timeline = $this->eventoService->getEventosTimeline($this->pessoa);

        $entry = $timeline[0]['events'][0];

        expect($entry['type'])->toBe('Trabalhador')
            ->and($entry['details']['coordenador'])->toBeTrue()
            ->and($entry['event']->idt_evento)->toBe($evento->idt_evento);
    });

    test('retorna entrada de Participante com dados corretos', function () {
        $evento = Evento::factory()->create(['dat_inicio' => '2024-05-15']);

        Participante::factory()->create([
            'idt_pessoa' => $this->pessoa->idt_pessoa,
            'idt_evento' => $evento->idt_evento,
        ]);

        $timeline = $this->eventoService->getEventosTimeline($this->pessoa);

        $entry = $timeline[0]['events'][0];

        expect($entry['type'])->toBe('Participante')
            ->and($entry['event']->idt_evento)->toBe($evento->idt_evento);
    });

    test('agrupa eventos por ano em ordem decrescente', function () {
        $evento2024 = Evento::factory()->create(['dat_inicio' => '2024-01-10']);
        $evento2023 = Evento::factory()->create(['dat_inicio' => '2023-06-20']);

        Participante::factory()->create(['idt_pessoa' => $this->pessoa->idt_pessoa, 'idt_evento' => $evento2024->idt_evento]);
        Participante::factory()->create(['idt_pessoa' => $this->pessoa->idt_pessoa, 'idt_evento' => $evento2023->idt_evento]);

        $timeline = $this->eventoService->getEventosTimeline($this->pessoa);

        expect($timeline)->toHaveCount(2)
            ->and($timeline[0]['year'])->toBe(2024)
            ->and($timeline[1]['year'])->toBe(2023);
    });

    test('ordena eventos dentro do mesmo ano por data decrescente', function () {
        $eventoJan = Evento::factory()->create(['dat_inicio' => '2024-01-10']);
        $eventoDez = Evento::factory()->create(['dat_inicio' => '2024-12-20']);

        Participante::factory()->create(['idt_pessoa' => $this->pessoa->idt_pessoa, 'idt_evento' => $eventoJan->idt_evento]);
        Participante::factory()->create(['idt_pessoa' => $this->pessoa->idt_pessoa, 'idt_evento' => $eventoDez->idt_evento]);

        $timeline = $this->eventoService->getEventosTimeline($this->pessoa);
        $events = $timeline[0]['events'];

        expect($events[0]['event']->idt_evento)->toBe($eventoDez->idt_evento)
            ->and($events[1]['event']->idt_evento)->toBe($eventoJan->idt_evento);
    });

    test('combina participações e trabalhos na mesma timeline', function () {
        $eventoP = Evento::factory()->create(['dat_inicio' => '2024-03-01']);
        $eventoT = Evento::factory()->create(['dat_inicio' => '2024-07-01']);

        $equipe = TipoEquipe::first();

        Participante::factory()->create(['idt_pessoa' => $this->pessoa->idt_pessoa, 'idt_evento' => $eventoP->idt_evento]);
        Trabalhador::factory()->create([
            'idt_pessoa' => $this->pessoa->idt_pessoa,
            'idt_evento' => $eventoT->idt_evento,
            'idt_equipe' => $equipe->idt_equipe,
        ]);

        $timeline = $this->eventoService->getEventosTimeline($this->pessoa);
        $events = $timeline[0]['events'];

        expect($events)->toHaveCount(2);
        $types = collect($events)->pluck('type')->all();
        expect($types)->toContain('Participante')->toContain('Trabalhador');
    });

    test('retorna eventos inativos/deletados com pontos corretos na timeline', function () {
        $eventoAtivo = Evento::factory()->create([
            'des_evento' => 'Evento Ativo',
            'dat_inicio' => '2024-03-10',
            'tip_evento' => 'E',
        ]);
        
        $eventoInativo = Evento::factory()->create([
            'des_evento' => 'Evento Inativo',
            'dat_inicio' => '2024-05-15',
            'tip_evento' => 'D',
        ]);
        $eventoInativo->delete(); // Soft-deleta o evento para torná-lo inativo
        
        $equipe = TipoEquipe::firstOrCreate([
            'des_grupo' => 'Coordenação Geral',
            'idt_movimento' => $this->movimento->idt_movimento,
        ]);
        Trabalhador::factory()->create([
            'idt_pessoa' => $this->pessoa->idt_pessoa,
            'idt_evento' => $eventoAtivo->idt_evento,
            'idt_equipe' => $equipe->idt_equipe,
            'ind_coordenador' => true,
        ]);

        Participante::factory()->create([
            'idt_pessoa' => $this->pessoa->idt_pessoa,
            'idt_evento' => $eventoInativo->idt_evento,
        ]);

        $timeline = $this->eventoService->getEventosTimeline($this->pessoa);
        
        expect($timeline)->toHaveCount(1)
            ->and($timeline[0]['year'])->toBe(2024);
            
        $events = $timeline[0]['events'];
        expect($events)->toHaveCount(2);
        
        // O mais recente primeiro (eventoInativo de 2024-05-15)
        expect($events[0]['event']->idt_evento)->toBe($eventoInativo->idt_evento)
            ->and($events[0]['type'])->toBe('Participante')
            ->and($events[0]['pontos'])->toBe(3) // Tipo D (Desafio) = 3 pontos
            ->and($events[0]['event']->trashed())->toBeTrue();
            
        // EventoAtivo de 2024-03-10
        expect($events[1]['event']->idt_evento)->toBe($eventoAtivo->idt_evento)
            ->and($events[1]['type'])->toBe('Trabalhador')
            ->and($events[1]['pontos'])->toBe(4) // Trabalhador Coordenador = 4 pontos
            ->and($events[1]['event']->trashed())->toBeFalse();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// EventoService — Ranking
// ─────────────────────────────────────────────────────────────────────────────

describe('EventoService — Ranking', function () {

    test('pessoa sem pontos fica na última posição', function () {
        $totalPessoas = Pessoa::count();

        $ranking = $this->eventoService->calcularRanking($this->pessoa);

        expect($ranking)->toBe($totalPessoas);
    });

    test('pessoa com mais pontos fica em posição superior', function () {
        $pessoaForte = createPessoa();
        // qtd_pontos_total não está no fillable — usar saveQuietly para bypass
        $pessoaForte->qtd_pontos_total = 500;
        $pessoaForte->saveQuietly();

        $this->pessoa->qtd_pontos_total = 100;
        $this->pessoa->saveQuietly();

        $rankingForte = $this->eventoService->calcularRanking($pessoaForte->fresh());
        $rankingFraco = $this->eventoService->calcularRanking($this->pessoa->fresh());

        expect($rankingForte)->toBeLessThan($rankingFraco);
    });

    test('empate resulta na mesma posição', function () {
        $outraPessoa = createPessoa();

        $this->pessoa->qtd_pontos_total = 200;
        $this->pessoa->saveQuietly();

        $outraPessoa->qtd_pontos_total = 200;
        $outraPessoa->saveQuietly();

        $r1 = $this->eventoService->calcularRanking($this->pessoa->fresh());
        $r2 = $this->eventoService->calcularRanking($outraPessoa->fresh());

        expect($r1)->toBe($r2);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// EventoService — confirmarParticipacao
// ─────────────────────────────────────────────────────────────────────────────

describe('EventoService — confirmarParticipacao', function () {

    test('cria participante na primeira chamada', function () {
        $evento = Evento::factory()->create();

        $participante = $this->eventoService->confirmarParticipacao($evento, $this->pessoa);

        expect($participante)->toBeInstanceOf(Participante::class);
        $this->assertDatabaseHas('participante', [
            'idt_evento' => $evento->idt_evento,
            'idt_pessoa' => $this->pessoa->idt_pessoa,
        ]);
    });

    test('não duplica participante em chamadas repetidas', function () {
        $evento = Evento::factory()->create();

        $this->eventoService->confirmarParticipacao($evento, $this->pessoa);
        $this->eventoService->confirmarParticipacao($evento, $this->pessoa);

        $this->assertDatabaseCount('participante', 1);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// EventoController — Espec e Movimentos Adicionais
// ─────────────────────────────────────────────────────────────────────────────

describe('EventoController — Espec e Movimentos Adicionais', function () {

    test('especialista vê eventos de todos os movimentos na listagem geral', function () {
        $movVem = TipoMovimento::where('des_sigla', 'VEM')->first();
        $movEcc = TipoMovimento::where('des_sigla', 'ECC')->first();
        $movSgm = TipoMovimento::where('des_sigla', 'Segue-Me')->first();

        $eventoVem = Evento::factory()->create(['idt_movimento' => $movVem->idt_movimento, 'des_evento' => 'Evento VEM Teste']);
        $eventoEcc = Evento::factory()->create(['idt_movimento' => $movEcc->idt_movimento, 'des_evento' => 'Evento ECC Teste']);
        $eventoSgm = Evento::factory()->create(['idt_movimento' => $movSgm->idt_movimento, 'des_evento' => 'Evento SGM Teste']);

        $espec = User::factory()->create([
            'role' => 'espec',
            'idt_movimento' => $movVem->idt_movimento,
        ]);

        $response = $this->actingAs($espec)->get(route('eventos.index'));

        $response->assertOk();
        $eventos = $response->viewData('eventos');

        expect($eventos->pluck('des_evento'))->toContain('Evento VEM Teste')
            ->toContain('Evento ECC Teste')
            ->toContain('Evento SGM Teste');
    });

    test('especialista vê o botão de gerenciar somente nos eventos do seu próprio movimento mesmo que não esteja trabalhando nele', function () {
        $movVem = TipoMovimento::where('des_sigla', 'VEM')->first();
        $movEcc = TipoMovimento::where('des_sigla', 'ECC')->first();

        $espec = User::factory()->create([
            'role' => 'espec',
            'idt_movimento' => $movVem->idt_movimento,
        ]);
        
        $eventoVem = Evento::factory()->create(['idt_movimento' => $movVem->idt_movimento, 'des_evento' => 'Meu Movimento']);
        $eventoEcc = Evento::factory()->create(['idt_movimento' => $movEcc->idt_movimento, 'des_evento' => 'Outro Movimento']);

        $response = $this->actingAs($espec)->get(route('eventos.index'));
        $response->assertOk();

        $response->assertSee(route('eventos.gerenciamento', $eventoVem));
        $response->assertDontSee(route('eventos.gerenciamento', $eventoEcc));
    });

    test('especialista vê na sua timeline eventos de todos os movimentos que participou/trabalhou', function () {
        $movVem = TipoMovimento::where('des_sigla', 'VEM')->first();
        $movEcc = TipoMovimento::where('des_sigla', 'ECC')->first();

        $espec = User::factory()->create([
            'role' => 'espec',
            'idt_movimento' => $movVem->idt_movimento,
        ]);
        
        $pessoaEspec = $espec->pessoa;

        $eventoVem = Evento::factory()->create(['idt_movimento' => $movVem->idt_movimento, 'des_evento' => 'Trabalho VEM', 'dat_inicio' => '2024-03-10']);
        $eventoEcc = Evento::factory()->create(['idt_movimento' => $movEcc->idt_movimento, 'des_evento' => 'Participacao ECC', 'dat_inicio' => '2024-05-15']);

        $equipe = TipoEquipe::first();
        Trabalhador::factory()->create([
            'idt_pessoa' => $pessoaEspec->idt_pessoa,
            'idt_evento' => $eventoVem->idt_evento,
            'idt_equipe' => $equipe->idt_equipe,
        ]);

        Participante::factory()->create([
            'idt_pessoa' => $pessoaEspec->idt_pessoa,
            'idt_evento' => $eventoEcc->idt_evento,
        ]);

        $response = $this->actingAs($espec)->get(route('timeline.index'));
        $response->assertOk();

        $response->assertSee('Trabalho VEM');
        $response->assertSee('Participacao ECC');
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Eventos Encerrados e Expiração
// ─────────────────────────────────────────────────────────────────────────────

describe('Eventos Encerrados e Expiração', function () {

    test('comando DeletarEventosFinalizados realiza soft-delete corretamente definindo deleted_at como data de término do evento', function () {
        $eventoAtivo = Evento::factory()->create([
            'dat_termino' => now()->addDays(2),
        ]);
        $eventoExpirado = Evento::factory()->create([
            'dat_termino' => now()->subDays(2),
        ]);

        $this->artisan('mov:deletar-eventos-finalizados')
            ->expectsOutput('1 eventos foram encerrados.')
            ->assertExitCode(0);

        expect(Evento::find($eventoAtivo->idt_evento))->not->toBeNull()
            ->and(Evento::find($eventoExpirado->idt_evento))->toBeNull()
            ->and(Evento::withTrashed()->find($eventoExpirado->idt_evento)->deleted_at->format('Y-m-d'))
            ->toBe($eventoExpirado->dat_termino->format('Y-m-d'));
    });

    test('admin e espec podem ver eventos encerrados passando status=encerrados', function () {
        $eventoAtivo = Evento::factory()->create([
            'des_evento' => 'Evento Ativo Lindo',
        ]);
        $eventoEncerrado = Evento::factory()->create([
            'des_evento' => 'Evento Encerrado Antigo',
        ]);
        // Soft-deleta o evento encerrado
        $eventoEncerrado->deleted_at = now()->subDays(1);
        $eventoEncerrado->save();

        // 1. Admin acessando sem status
        $response = $this->actingAs($this->user)->get(route('eventos.index'));
        $response->assertOk();
        $response->assertSee('Evento Ativo Lindo');
        $response->assertDontSee('Evento Encerrado Antigo');

        // 2. Admin acessando com status=encerrados
        $response = $this->actingAs($this->user)->get(route('eventos.index', ['status' => 'encerrados']));
        $response->assertOk();
        $response->assertDontSee('Evento Ativo Lindo');
        $response->assertSee('Evento Encerrado Antigo');

        // 3. Espec acessando com status=encerrados
        $espec = User::factory()->create(['role' => 'espec', 'idt_movimento' => $this->movimento->idt_movimento]);
        $response = $this->actingAs($espec)->get(route('eventos.index', ['status' => 'encerrados']));
        $response->assertOk();
        $response->assertDontSee('Evento Ativo Lindo');
        $response->assertSee('Evento Encerrado Antigo');
    });

    test('usuario comum e coordenador nao podem ver eventos encerrados', function () {
        $eventoEncerrado = Evento::factory()->create([
            'des_evento' => 'Evento Encerrado Secreto',
        ]);
        $eventoEncerrado->deleted_at = now()->subDays(1);
        $eventoEncerrado->save();

        // 1. Usuário comum tenta acessar com status=encerrados
        $comum = User::factory()->create(['role' => 'user']);
        $response = $this->actingAs($comum)->get(route('eventos.index', ['status' => 'encerrados']));
        $response->assertOk();
        $response->assertDontSee('Evento Encerrado Secreto');

        // 2. Coordenador tenta acessar com status=encerrados
        $coord = User::factory()->create(['role' => 'coord']);
        $response = $this->actingAs($coord)->get(route('eventos.index', ['status' => 'encerrados']));
        $response->assertOk();
        $response->assertDontSee('Evento Encerrado Secreto');
    });
});
