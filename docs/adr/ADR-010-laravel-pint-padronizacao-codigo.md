# ADR-010: Laravel Pint como padrão de formatação de código

- **Status:** Aceito
- **Data:** 2025-10-18 (adoção formal); uso inicial em 2025-09-xx
- **Commits de referência:**
  - `e04d919` — *Adicionado o Pint*
  - `d2c769d` — *Execucao do Laravel Pint*
  - `5cd71a3` — *Usei o Laravel/Pint para padronizar o codigo*

---

## Contexto

Com múltiplos colaboradores contribuindo para o projeto, o código acumulou inconsistências de formatação: indentação mista, espaçamento variável, ordenação de imports diferente entre arquivos. Isso gerava diffs ruidosos nos pull requests e dificultava a leitura do código. Era necessário um padrão de formatação aplicado de forma automática e consistente.

## Decisão

Adotar o **Laravel Pint** como ferramenta de formatação de código PHP. O Pint é o formatador oficial do ecossistema Laravel, baseado no PHP-CS-Fixer, com configuração padrão alinhada ao estilo do framework. A execução é feita manualmente antes de commits significativos e pode ser integrada ao pipeline de CI.

## Alternativas consideradas

- **PHP-CS-Fixer diretamente:** O Pint é um wrapper sobre ele com configuração padrão para Laravel. Usar o PHP-CS-Fixer diretamente exigiria manter um arquivo de configuração customizado.
- **PHP_CodeSniffer (phpcs):** Focado em detecção de violações, não em correção automática. Menos conveniente para o fluxo de trabalho do time.
- **Sem formatador (convenção manual):** Descartado pela inconsistência já observada no histórico de commits.

## Consequências

**Positivas:**
- Estilo de código consistente em todo o projeto sem discussões subjetivas.
- Diffs de pull requests focados em mudanças lógicas, não de formatação.
- Configuração zero: o Pint funciona com as convenções do Laravel por padrão.
- Ferramenta oficial do ecossistema, bem mantida e documentada.

**Negativas:**
- Commits de formatação em massa (como `d2c769d`) poluem o histórico do git e dificultam `git blame`.
- Requer disciplina do time para executar antes de commitar; sem hook de pre-commit configurado, a aplicação é inconsistente.
- Pode reformatar código de terceiros ou gerado automaticamente de forma indesejada.
