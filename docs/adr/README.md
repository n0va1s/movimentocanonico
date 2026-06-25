# Architecture Decision Records (ADRs)

Este diretório contém os registros de decisões arquiteturais do projeto **PNSL-NTM**. Cada ADR documenta uma decisão técnica significativa: o contexto que a motivou, o que foi decidido, as alternativas consideradas e as consequências esperadas.

Os ADRs foram gerados a partir da análise do histórico de commits do repositório.

## Índice

| ADR | Título | Status | Data |
|-----|--------|--------|------|
| [ADR-001](ADR-001-especializacao-ficha.md) | Especialização da tabela Ficha em FichaVem, FichaEcc e FichaSGM | Aceito | 2025-06-21 |
| [ADR-002](ADR-002-enums-php-campos-dominio.md) | Uso de Enums PHP nativos para campos de domínio | Aceito | 2026-04-27 |
| [ADR-003](ADR-003-soft-deletes.md) | Adoção de SoftDeletes para exclusão lógica de registros | Aceito | 2025-06-29 |
| [ADR-004](ADR-004-livewire-gerenciamento-eventos.md) | Uso de Livewire para o gerenciamento de eventos | Aceito | 2026-04-27 |
| [ADR-005](ADR-005-notificacoes-telegram.md) | Notificações de sistema via Telegram | Aceito | 2026-04-26 |
| [ADR-006](ADR-006-deploy-ftp-locaweb-github-actions.md) | Deploy via FTP para Locaweb usando GitHub Actions | Aceito | 2026-02-20 |
| [ADR-007](ADR-007-dockerizacao-ambiente-desenvolvimento.md) | Dockerização do ambiente de desenvolvimento e produção | Aceito | 2025-12-09 |
| [ADR-008](ADR-008-gates-autorizacao-perfis.md) | Gates de autorização para controle de acesso por perfil | Aceito | 2026-05-12 |
| [ADR-009](ADR-009-relacionamento-pessoa-user.md) | Relacionamento bidirecional entre Pessoa e User | Aceito | 2025-10-18 |
| [ADR-010](ADR-010-laravel-pint-padronizacao-codigo.md) | Laravel Pint como padrão de formatação de código | Aceito | 2025-10-18 |

## Formato

Cada ADR segue a estrutura:

- **Status:** Proposto / Aceito / Depreciado / Substituído
- **Data:** Data da decisão
- **Commit de referência:** Hash(es) do(s) commit(s) que implementaram a decisão
- **Contexto:** O problema ou situação que motivou a decisão
- **Decisão:** O que foi decidido e como foi implementado
- **Alternativas consideradas:** Outras opções avaliadas e por que foram descartadas
- **Consequências:** Impactos positivos e negativos da decisão
