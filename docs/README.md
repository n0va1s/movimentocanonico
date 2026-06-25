# Documentação — PNSL-NTM

Sistema de gestão de encontros da **Paróquia Nossa Senhora do Lago**, Brasília-DF.  
"NTM" — *Não Tenhais Medo*, frase do Papa São João Paulo II.

---

## 📋 PRD — Product Requirements Document

Visão completa do produto: objetivos, funcionalidades, Aura, crachás, prestação de contas, stack e backlog.

> 👉 **[Acessar o PRD](PRD.md)**

---

## Histórias de Usuário

Descrevem as funcionalidades sob a perspectiva do usuário, com critérios de aceitação, campos e mensagens esperadas.

| ID | Título | Perfil | Status |
|----|--------|--------|--------|
| [HIS-001](his/HIS_001_cadastro-evento.md) | Cadastro de Evento | Coordenação | ✅ Implementado |
| [HIS-002](his/HIS_002_cadastro-ficha-vem.md) | Cadastro de Ficha do VEM | Coordenação / Usuário | ✅ Implementado |
| [HIS-003](his/HIS_003_cadastro-ficha-ecc.md) | Cadastro de Ficha do ECC | Coordenação / Usuário | ✅ Implementado |
| [HIS-004](his/HIS_004_cadastro-participante.md) | Cadastro de Participante | Administrador | ✅ Implementado |
| [HIS-005](his/HIS_005_cadastro-tipo-movimento.md) | Cadastro de Tipo de Movimento | Administrador | ✅ Implementado |
| [HIS-006](his/HIS_006_cadastro-tipo-responsavel.md) | Cadastro de Tipo de Responsável | Administrador | ✅ Implementado |
| [HIS-007](his/HIS_007_cadastro-tipo-situacao.md) | Cadastro de Tipo de Situação | Administrador | ✅ Implementado |
| [HIS-008](his/HIS_008_inscricao-trabalhador.md) | Inscrição para Evento como Trabalhador | Usuário | ✅ Implementado |

---

## Architecture Decision Records (ADR)

Registram as decisões arquiteturais significativas: contexto, o que foi decidido, alternativas descartadas e consequências.

| ID | Título | Data |
|----|--------|------|
| [ADR-001](adr/ADR-001-especializacao-ficha.md) | Especialização da tabela Ficha em FichaVem, FichaEcc e FichaSGM | 2025-06-21 |
| [ADR-002](adr/ADR-002-enums-php-campos-dominio.md) | Uso de Enums PHP nativos para campos de domínio | 2026-04-27 |
| [ADR-003](adr/ADR-003-soft-deletes.md) | Adoção de SoftDeletes para exclusão lógica de registros | 2025-06-29 |
| [ADR-004](adr/ADR-004-livewire-gerenciamento-eventos.md) | Uso de Livewire para o gerenciamento de eventos | 2026-04-27 |
| [ADR-005](adr/ADR-005-notificacoes-telegram.md) | Notificações de sistema via Telegram | 2026-04-26 |
| [ADR-006](adr/ADR-006-deploy-ftp-locaweb-github-actions.md) | Deploy via FTP para Locaweb usando GitHub Actions | 2026-02-20 |
| [ADR-007](adr/ADR-007-dockerizacao-ambiente-desenvolvimento.md) | Dockerização do ambiente de desenvolvimento e produção | 2025-12-09 |
| [ADR-008](adr/ADR-008-gates-autorizacao-perfis.md) | Gates de autorização para controle de acesso por perfil | 2026-05-12 |
| [ADR-009](adr/ADR-009-relacionamento-pessoa-user.md) | Relacionamento bidirecional entre Pessoa e User | 2025-10-18 |
| [ADR-010](adr/ADR-010-laravel-pint-padronizacao-codigo.md) | Laravel Pint como padrão de formatação de código | 2025-10-18 |

---

## Referências Técnicas

Documentos de referência para desenvolvimento e manutenção do sistema.

| Documento | Descrição |
|-----------|-----------|
| [Arquitetura](arquitetura.md) | Diagrama e descrição da arquitetura Docker: containers, redes, volumes, segurança e healthchecks |
| [Padrões de Banco de Dados](padroes_banco_dados.md) | Convenções de nomenclatura de tabelas e colunas (prefixos `nom_`, `ind_`, `dat_`, etc.) |
| [Esquema Resumido do Banco](schema_resumido.md) | Mapa enxuto de tabelas, chaves e relacionamentos para consulta ultra-rápida |
| [Perfis de Acesso](perfis-de-acesso.md) | Mapeamento completo de rotas e permissões por perfil (`user`, `coord`, `espec`, `admin`) |
