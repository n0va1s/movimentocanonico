---
name: testador-senior
description: Guia avançado e pragmático para geração de testes automatizados com Pest PHP no ecossistema Laravel 12 do projeto PNSL-NTM.
---

# Skill: Testador Sênior (Pest PHP & Laravel 12)

Esta Skill define as diretrizes, padrões técnicos e boas práticas inegociáveis para a criação de suítes de testes automatizados de alta fidelidade e resiliência no projeto PNSL-NTM. Ela visa guiar futuros agentes a gerarem testes funcionais (Feature) abrangentes que de fato reflitam as regras do código de produção e cubram tratamentos complexos de exceção e transações de banco.

---

## 1. Contexto do Ecossistema de Testes do Projeto
- **Framework de Teste:** Pest PHP v3 (sintaxe limpa, estruturada com `describe()` e `test()`).
- **Persistência de Teste:** Utilização de `uses(RefreshDatabase::class)` para isolamento do banco.
- **Ambiente Portável:** Execução baseada em **SQLite em memória** (`DB_CONNECTION=sqlite DB_DATABASE=:memory:`), o que exige testes portáveis sem dependências externas ausentes (como a biblioteca PHP-GD).

---

## 2. Padrões de Geração de Código de Testes (Cheat-Sheet)

Cada arquivo de testes de Feature deve começar com a importação limpa de modelos, enums e Pest functions, agrupado por contextos usando `describe()` para cobrir todas as responsabilidades do Controlador associado:

```php
<?php

use App\Models\Ficha;
use App\Models\Pessoa;
use App\Enums\TipoSituacao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\QueryException;

uses(RefreshDatabase::class);
```

---

## 3. Padrões Técnicos Avançados (Cruciais para Sucesso)

Ao gerar novos cenários de testes, você **deve** aplicar as cinco técnicas mestras descobertas durante a homologação do ecossistema:

### A. Simulação de Mídias sem Dependência de GD (GD-Free Uploads)
> [!WARNING]
> Nunca use `UploadedFile::fake()->image('foto.jpg')`! A máquina que roda os testes pode não ter a extensão `gd` do PHP instalada, gerando falhas catastróficas de `LogicException: GD extension is not installed.`
> **Solução Correta:** Use `UploadedFile::fake()->create(...)` para gerar arquivos binários genéricos sem requisições gráficas:
```php
// Correto: cria um arquivo genérico simulando a imagem sem dependência do GD
$foto = UploadedFile::fake()->create('candidato.jpg', 100);
```

### B. Captura de Exceções de Negócio no Controller (`withoutExceptionHandling`)
> [!IMPORTANT]
> Em testes HTTP (`$this->get()` ou `$this->post()`), o Laravel captura exceções lançadas nos controladores (como `RuntimeException` de validação interna de negócio) e as converte automaticamente em responses HTTP 500, impedindo o Pest de assertar a exceção original.
> **Solução Correta:** Chame `$this->withoutExceptionHandling();` no escopo do teste antes da chamada HTTP:
```php
test('aprovar ficha lanca excecao de integracao', function () {
    $ficha = Ficha::factory()->create(['num_cpf_candidato' => null]);

    $this->withoutExceptionHandling();

    expect(fn () => $this->get(route('recurso.approve', $ficha->id)))
        ->toThrow(\RuntimeException::class);
});
```

### C. Simulação de Falhas Físicas de Banco via Listeners Eloquent
> [!TIP]
> Para testar blocos de captura `catch (\Throwable $e)` em métodos como `store` ou `update` (que executam rollback de transações e retornam mensagens amigáveis de erro ao usuário), evite mocks estáticos complexos com Mockery, os quais costumam dar conflito caso a classe já tenha sido carregada.
> **Solução Correta:** Use listeners temporários de eventos Eloquent (`creating` ou `updating`) para disparar a exceção desejada, e limpe-os no final do teste com `flushEventListeners()`:
```php
test('tratamento de erro geral Throwable ao salvar', function () {
    // Registra listener temporário para forçar falha no salvamento
    Ficha::creating(function ($ficha) {
        throw new \Exception('Erro interno simulado de banco');
    });

    $this->post(route('recurso.store'), $payload)
        ->assertStatus(302)
        ->assertSessionHas('error', 'Ocorreu um erro ao salvar a ficha. Tente novamente.');

    // Inegociável: Limpar o listener para não contaminar outros testes
    Ficha::flushEventListeners();
});
```

### D. Mocking de SQLSTATE Strings para QueryExceptions
> [!CAUTION]
> Ao validar capturas de `QueryException` (como em exclusões violando restrições de chaves estrangeiras onde o código do controlador verifica estritamente `$e->getCode() === '23000'`), a exceção padrão do PHP `Exception` recebe o código como um `int` (`23000`), falhando na comparação do tipo estrito do Laravel PHP (`===`).
> **Solução Correta:** Use uma **classe anônima** herdando de `\Exception` para forçar o retorno do código de erro como a string `'23000'`:
```php
test('tratamento de QueryException de integridade (codigo 23000)', function () {
    // Forçar o código do erro a ser retornado como string '23000'
    $innerException = new class('integrity constraint violation') extends \Exception {
        protected $code = '23000';
    };

    Ficha::deleting(function ($ficha) use ($innerException) {
        throw new QueryException(
            'mysql',
            'delete from ficha where idt_ficha = ?',
            [$ficha->idt_ficha],
            $innerException
        );
    });

    $this->delete(route('recurso.destroy', $ficha->id))
        ->assertRedirect(route('recurso.index'))
        ->assertSessionHas('error', 'Não é possível excluir esta ficha. É preciso apagar os dados associados.');

    Ficha::flushEventListeners();
});
```

### E. Assertions de Side-Effects e Auditoria Automática
Todo cadastro ou alteração deve validar não apenas o estado final HTTP, mas os efeitos reais persistidos:
1. **Auditoria:** Assertar que `usu_inclusao` e `usu_alteracao` refletem o ID do usuário logado (`$this->user->id`).
2. **Side-effects de Aprovação:** Verificar se a transição para `APROVADO` de fato gerou os registros em tabelas correlacionadas (`Pessoa`, `Participante`, `PessoaSaude`) e copiou fisicamente mídias (`PessoaFoto`), limpando o `Participante` ao desaprovar (toggle).

---

## 4. Checklist para Novas Suítes de Testes
Sempre que for solicitado a escrever novos testes, garanta a cobertura dos seguintes blocos:
- [ ] **Segurança (Autenticação):** Garantir que usuários anônimos são barrados com redirect para login em todas as rotas do controlador.
- [ ] **Index & Listagem:** Testar busca geral por nome, apelido, filtros específicos por relacionamento (como evento) e paginação.
- [ ] **Views de Formulário:** Validar dados injetados na view para formulários de criação, edição, exibição padrão e modo impressão.
- [ ] **Happy Paths de Persistência:** Cobrir gravação e edição de dados primários, sub-relações de tabelas de detalhes, uploads múltiplos e flags booleanas.
- [ ] **Tratamento de Exceções de Banco:** Cobrir CPFs duplicados, falhas de queries no soft delete e falhas inesperadas de banco.
- [ ] **Testes de Integração e Transições de Negócio:** Cobrir fluxo completo de aprovação com side-effects no banco de dados e remoção limpa sob desaprovação.