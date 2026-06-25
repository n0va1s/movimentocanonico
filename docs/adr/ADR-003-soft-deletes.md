# ADR-003: Adoção de SoftDeletes para exclusão lógica de registros

- **Status:** Aceito
- **Data:** 2025-06-29
- **Commits de referência:**
  - `00eef23` — *feat: adição de `SoftDeletes` em `PessoaFoto`, `PessoaSaude` e `Trabalhador`*
  - `0a21aab` — *Melhoria de performance no cadastro de fichas. Implementacao da aprovacao, desaprovacao e exclusao logica da ficha*

---

## Contexto

O sistema gerencia dados sensíveis de pessoas (fotos, informações de saúde) e registros operacionais (trabalhadores, fichas). A exclusão física desses registros poderia causar perda de histórico, quebrar integridade referencial e dificultar auditorias. Além disso, fichas aprovadas que geram participantes precisam de um ciclo de vida controlado (aprovação → desaprovação → exclusão lógica).

## Decisão

Utilizar o trait `SoftDeletes` do Laravel nos models onde a exclusão permanente é indesejável: `PessoaFoto`, `PessoaSaude`, `Trabalhador` e `Ficha` (e suas especializações). As migrations correspondentes adicionam a coluna `deleted_at`. Registros "excluídos" permanecem no banco e são filtrados automaticamente pelo Eloquent nas queries padrão.

## Alternativas consideradas

- **Exclusão física com backup:** Registros seriam deletados do banco e arquivados em outro local. Descartado pela complexidade operacional e pela dificuldade de restauração.
- **Campo booleano `ativo`:** Simples, mas não registra quando a exclusão ocorreu e requer filtros manuais em todas as queries.

## Consequências

**Positivas:**
- Histórico preservado: é possível auditar quando e quais registros foram removidos.
- Restauração simples via `restore()`.
- Integração nativa com Eloquent: queries padrão já excluem os registros deletados.
- Fichas desaprovadas podem ser reativadas sem perda de dados.

**Negativas:**
- O banco cresce com registros "mortos" que precisam de limpeza periódica.
- Queries que precisam incluir registros deletados exigem `withTrashed()` explícito.
- Constraints de unicidade no banco precisam considerar `deleted_at` para funcionar corretamente.
