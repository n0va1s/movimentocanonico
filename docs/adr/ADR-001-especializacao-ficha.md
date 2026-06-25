# ADR-001: Especialização da tabela Ficha em FichaVem, FichaEcc e FichaSGM

- **Status:** Aceito
- **Data:** 2025-06-21
- **Commit de referência:** `aa02690` — *Refatoracao da tabela Ficha. Especializacao em FichaVem e FichaEcc*

---

## Contexto

O sistema gerencia inscrições de participantes em diferentes tipos de encontros do movimento: VEM (Venha Encontrar Maria), ECC (Encontro de Casais com Cristo) e Segue-Me (SGM). Cada tipo de encontro possui campos específicos no formulário de inscrição que não se aplicam aos demais. Inicialmente, todos os dados eram armazenados em uma única tabela `ficha` genérica.

## Decisão

A tabela `ficha` foi mantida como entidade base (com os campos comuns a todos os tipos), e foram criadas tabelas especializadas `ficha_vem`, `ficha_ecc` e `ficha_sgm`, cada uma com seus campos específicos. No nível de código, foram criados models separados (`FichaVem`, `FichaEcc`, `FichaSGM`) que estendem o comportamento base, com controllers e requests de validação próprios para cada tipo.

As tabelas especializadas utilizam chave composta referenciando a tabela `ficha` e são deletadas em cascata quando a ficha base é excluída.

## Alternativas consideradas

- **Tabela única com colunas nullable:** Manter uma única tabela com todos os campos possíveis, deixando nulos os que não se aplicam. Descartado por gerar uma tabela esparsa e dificultar validações específicas por tipo.
- **Herança de tabela por classe (CTI):** Uma tabela por tipo sem tabela base. Descartado por duplicar os campos comuns e dificultar consultas que precisam de todos os tipos.

## Consequências

**Positivas:**
- Validações de formulário são isoladas por tipo, tornando as regras mais claras e testáveis.
- O schema do banco reflete fielmente o domínio do negócio.
- Facilita a adição de novos tipos de ficha no futuro sem impactar os existentes.

**Negativas:**
- Consultas que precisam de todos os tipos de ficha exigem JOINs ou queries separadas.
- Mais controllers, requests e views para manter.
