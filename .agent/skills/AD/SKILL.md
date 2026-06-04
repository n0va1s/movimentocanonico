---
name: padroes-banco-dados
description: Guia sênior de modelagem de dados e padrões de banco de dados, estabelecendo diretrizes rígidas para tabelas, colunas, migrations e ORM no ecossistema Laravel do PNSL-NTM.
---

# Skill: Modelagem e Padrões de Banco de Dados (PNSL-NTM)

Esta Skill define as diretrizes obrigatórias e inegociáveis para a criação de tabelas, colunas, relacionamentos, migrations Laravel e mapeamento de modelos Eloquent no projeto PNSL-NTM. Qualquer alteração ou nova tabela adicionada ao banco de dados deve seguir estritamente as regras documentadas aqui.

---

## 1. Alinhamento das Tabelas com o Negócio (Linguagem Ubíqua)

Toda a modelagem deve refletir a terminologia real do negócio. Evite termos técnicos genéricos ou traduções literais descontextualizadas. 

### Principais Entidades Identificadas:
*   **`evento`**: Refere-se a um encontro específico sendo organizado (ex: "XXX VEM").
*   **`participante`**: A pessoa que está fazendo/participando de um encontro pela primeira vez (venista/encontrista).
*   **`trabalhador`**: A pessoa que está servindo/trabalhando em alguma equipe de suporte do encontro.
*   **`voluntario`**: Cadastro da intenção de uma pessoa em servir em equipes de futuros eventos.

---

## 2. Nomenclatura de Tabelas Relacionais e Auxiliares

Tabelas de relacionamento (N:N), de detalhamento, ou dependentes de uma entidade principal **devem sempre começar com o nome da tabela pai**. Isso agrupa logicamente as tabelas no SGBD por ordem alfabética.

```
[Entidade Principal] + _ + [Qualificador / Detalhe]
```

### Exemplos Reais:
*   `evento` ➔ `evento_foto` (fotos/logos do evento)
*   `pessoa` ➔ `pessoa_saude` (dados médicos da pessoa), `pessoa_foto` (foto de perfil)
*   `ficha` ➔ `ficha_ecc` (detalhes do ECC), `ficha_saude` (restrições alimentares temporárias da ficha)

---

## 3. Padrão Rígido de Prefixos de Colunas (Semântica de Campo)

Todas as colunas do banco de dados devem utilizar um prefixo de **três letras seguido de underline (`_`)** indicando seu tipo semântico e função. 

| Prefixo | Significado | Tipo SGBD Sugerido | Exemplos de Uso Real | Observações / Regras |
| :--- | :--- | :--- | :--- | :--- |
| **`idt_`** | Chaves PK / FK | `bigint unsigned` | `idt_pessoa`, `idt_evento` | Chaves primárias e chaves estrangeiras. |
| **`nom_`** | Nomes e Títulos | `varchar(100/255)` | `nom_pessoa`, `nom_movimento` | Nomes próprios, identificadores textuais amigáveis. |
| **`des_`** | Descrições | `varchar(255)` | `des_endereco`, `des_mora_quem` | Descrições curtas a médias de atributos. |
| **`txt_`** | Textos Longos | `text` / `longtext` | `txt_observacao`, `txt_complemento` | Observações amplas, anotações abertas de saúde. |
| **`ind_`** | Flags Booleanas | `boolean` (0 ou 1) | `ind_restricao`, `ind_consentimento` | Nome deve ser uma afirmação legível de True/False. |
| **`dat_`** | Datas | `date` / `datetime` / `timestamp` | `dat_nascimento`, `dat_casamento` | Datas puras ou marcas temporais de eventos. |
| **`tip_`** | Tipos / Enums | `char(1)` a `varchar(5)` | `tip_escolaridade`, `tip_estado_civil` | Siglas curtas ("enums virtuais"): `F` (Fundamental), `M` (Médio). |
| **`val_`** | Valores Contábeis | `decimal(8,2)` / `decimal(10,2)` | `val_camiseta`, `val_trabalhador` | Campos contábeis ou de custos financeiros em R$. |
| **`qtd_`** | Quantidades | `integer` | `qtd_filhos`, `qtd_vagas` | Valores numéricos escalares para contagem matemática. |
| **`num_`** | Números Textuais | `varchar` | `num_cpf_candidato`, `num_evento` | Números que não sofrem operações matemáticas (ex: CPF, CPF cônjuge). |
| **`eml_`** | E-mails | `varchar(255)` | `eml_pessoa`, `eml_candidato` | Validação estrita de correio eletrônico. |
| **`tel_`** | Telefones | `varchar(20)` | `tel_pessoa`, `tel_conjuge` | Números telefônicos contendo DDD e sem máscara física. |
| **`tam_`** | Tamanho Físico | `varchar(5)` | `tam_camiseta`, `tam_camiseta_conjuge`| Tamanhos comerciais de produtos/roupas (P, M, G, GG, XG). |
| **`med_`** | Mídias e Storage | `varchar(255)` | `med_foto`, `med_logo` | Caminhos de arquivos (*paths*) no Storage (nunca armazene o BLOB). |
| **`usu_`** | Auditoria | `bigint unsigned` | `usu_inclusao`, `usu_alteracao` | ID do usuário logado que realizou o cadastro ou alteração. |

---

## 4. Práticas Recomendadas para Migrations Laravel

Ao gerar migrations, as definições devem refletir exatamente a tipagem física correspondente ao prefixo lógico das colunas.

### Exemplo de Migration Padrão:
```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pessoa_saude', function (Blueprint $table) {
            // Chave Primária Customizada usando idt_
            $table->id('idt_pessoa_saude');

            // Chave Estrangeira com restrição explícita
            $table->foreignId('idt_pessoa')
                  ->constrained('pessoa', 'idt_pessoa')
                  ->onDelete('cascade');

            // Flags Booleanas (ind_)
            $table->boolean('ind_restricao_alimentar')->default(false);
            $table->boolean('ind_toma_remedio')->default(false);

            // Textos longos (txt_) para detalhes
            $table->text('txt_detalhe_restricao')->nullable();
            $table->text('txt_detalhe_remedio')->nullable();

            // Auditoria (usu_) mapeando usuários do sistema
            $table->foreignId('usu_inclusao')->constrained('users', 'id');
            $table->foreignId('usu_alteracao')->nullable()->constrained('users', 'id');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pessoa_saude');
    }
};
```

---

## 5. Mapeamento no Eloquent ORM

Dado que os nomes de chaves primárias e chaves estrangeiras não seguem o padrão clássico do Laravel (`id` e `[tabela]_id`), você **deve** declarar explicitamente essas propriedades em seus Models Eloquent para garantir que os relacionamentos funcionem corretamente.

### Configurando o Model:
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PessoaSaude extends Model
{
    protected $table = 'pessoa_saude';

    // Declarar chave primária customizada
    protected $primaryKey = 'idt_pessoa_saude';

    // Campos permitidos para escrita em massa
    protected $fillable = [
        'idt_pessoa',
        'ind_restricao_alimentar',
        'ind_toma_remedio',
        'txt_detalhe_restricao',
        'txt_detalhe_remedio',
        'usu_inclusao',
        'usu_alteracao',
    ];

    // Casts obrigatórios para o padrão boolean (ind_)
    protected $casts = [
        'ind_restricao_alimentar' => 'boolean',
        'ind_toma_remedio' => 'boolean',
    ];

    // Relacionamento declarando FK e PK customizadas
    public function pessoa(): BelongsTo
    {
        return $this->belongsTo(Pessoa::class, 'idt_pessoa', 'idt_pessoa');
    }
}
```

---

## 6. Checklist de Validação de Banco de Dados

Antes de finalizar qualquer alteração em arquivos de banco de dados ou criar novas migrations, valide se:

- [ ] **Alinhamento com o Negócio:** Os nomes de tabelas utilizam a linguagem ubíqua (`evento`, `participante`, `trabalhador`, `voluntario`)?
- [ ] **Hierarquia de Nomes:** Tabelas secundárias/relacionais começam com o nome da tabela principal (ex: `pessoa_saude`)?
- [ ] **Prefixos Semânticos:** Todos os nomes de colunas sem exceção utilizam um dos prefixos de três letras cadastrados (`idt_`, `nom_`, `des_`, `txt_`, `ind_`, `dat_`, `tip_`, `val_`, `qtd_`, `num_`, `eml_`, `tel_`, `tam_`, `med_`, `usu_`)?
- [ ] **Chaves Primárias e Estrangeiras:** Estão todas com o prefixo `idt_` e as declarações das foreign keys em migrations explicitam a coluna referenciada?
- [ ] **Modelos Eloquent:** A propriedade `protected $primaryKey` foi configurada e os relacionamentos mapeiam explicitamente os campos `idt_*`?
- [ ] **Validação de Booleanos:** Os campos do tipo `ind_` possuem cast explícito para `boolean` no Model Eloquent?
