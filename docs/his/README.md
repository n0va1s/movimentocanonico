# Histórias de Usuário — PNSL-NTM

Este diretório contém as histórias de usuário do sistema de gestão de encontros da **Paróquia Nossa Senhora do Lago (PNSL-NTM)**. Cada arquivo descreve uma funcionalidade sob a perspectiva do usuário, com critérios de aceitação, campos do formulário e mensagens esperadas.

## Índice

| ID | Título | Perfil | Status |
|----|--------|--------|--------|
| [HIS-001](HIS_001_cadastro-evento.md) | Cadastro de Evento | Coordenação | ✅ Implementado |
| [HIS-002](HIS_002_cadastro-ficha-vem.md) | Cadastro de Ficha do VEM | Coordenação / Usuário | ✅ Implementado |
| [HIS-003](HIS_003_cadastro-ficha-ecc.md) | Cadastro de Ficha do ECC | Coordenação / Usuário | ✅ Implementado |
| [HIS-004](HIS_004_cadastro-participante.md) | Cadastro de Participante | Administrador | ✅ Implementado |
| [HIS-005](HIS_005_cadastro-tipo-movimento.md) | Cadastro de Tipo de Movimento | Administrador | ✅ Implementado |
| [HIS-006](HIS_006_cadastro-tipo-responsavel.md) | Cadastro de Tipo de Responsável | Administrador | ✅ Implementado |
| [HIS-007](HIS_007_cadastro-tipo-situacao.md) | Cadastro de Tipo de Situação | Administrador | ✅ Implementado |
| [HIS-008](HIS_008_inscricao-trabalhador.md) | Inscrição para Evento como Trabalhador | Usuário | ✅ Implementado |

## Formato padrão

Cada história segue a estrutura:

```
# HIS-XXX: Título

**Como** [perfil],
**quero** [ação],
**para que** [benefício].

## Critérios de Aceitação
## Campos do Formulário
## Mensagens Esperadas
```

## Perfis de usuário

| Perfil | Identificador | Descrição |
|--------|--------------|-----------|
| Administrador | `admin` | Acesso total ao sistema |
| Coordenador | `coord` | Acesso operacional a eventos e equipes |
| Especialista | `espec` | Acesso ao gerenciamento de eventos específicos |
| Usuário | `user` | Acesso básico pós-login |

> Para detalhes completos sobre permissões por perfil, consulte [perfis-de-acesso.md](../perfis-de-acesso.md).
