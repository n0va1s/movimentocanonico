# ADR-002: Uso de Enums PHP nativos para campos de domínio

- **Status:** Aceito
- **Data:** 2026-04-27 (adoção ampla); início em 2026-05-05 para FichaSGM
- **Commits de referência:**
  - `5959ec8` — *feat: implement event management system with new Enums, Livewire components*
  - `695214f` — *refactor: FichaSGM done. Migrate FichaSGM model and request to use Enums*

---

## Contexto

O sistema possui vários campos cujos valores são restritos a um conjunto fixo: gênero, estado civil, cor da troca, tamanho de camiseta, tipo de evento, faixa etária, escolaridade, religião, entre outros. Inicialmente esses valores eram tratados como strings livres ou constantes espalhadas pelo código, sem garantia de consistência entre a camada de apresentação, validação e persistência.

## Decisão

Adotar os **Enums nativos do PHP 8.1+** (backed enums) para representar todos os campos de domínio com valores fixos. Cada enum fica em `app/Enums/` e é referenciado diretamente nos models Eloquent (via cast), nas requests de validação e nas views Blade.

Exemplos criados: `ComoSoube`, `CorTroca`, `EstadoCivil`, `FaixaEtaria`, `Genero`, `HabilidadePrincipal`, `Perfil`, `TamanhoCamiseta`, `TipoEvento`, `TipoRestricao`, `Escolaridade`, `EscolaridadeSituacao`, `Religiao`.

## Alternativas consideradas

- **Tabelas de domínio no banco (lookup tables):** Já existiam algumas (`TipoMovimento`, `TipoResponsavel`, `TipoSituacao`). Mantidas para domínios que a coordenação precisa gerenciar em produção. Para domínios fixos e imutáveis, Enums são preferíveis por eliminar joins e garantir type safety em tempo de compilação.
- **Constantes de classe:** Menos expressivos, sem suporte nativo a cast no Eloquent e sem os métodos utilitários que os Enums fornecem (`cases()`, `from()`, `tryFrom()`).

## Consequências

**Positivas:**
- Type safety: o compilador/IDE detecta valores inválidos antes da execução.
- Validação simplificada nas requests usando `Rule::enum(MinhaEnum::class)`.
- Casts automáticos no Eloquent eliminam conversões manuais.
- Código mais legível: `Genero::Masculino` em vez de `'M'` ou `1`.

**Negativas:**
- Adicionar ou remover valores exige deploy de código (não é configurável em produção).
- Campos que antes eram strings livres precisaram de migration para normalizar os dados existentes.
