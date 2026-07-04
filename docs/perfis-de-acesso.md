# Perfis de Acesso

Este documento descreve o que cada perfil de usuário pode acessar no sistema. O controle é feito via middleware `role` aplicado nos grupos de rotas em `routes/web.php`.

---

## Perfis disponíveis

| Perfil | Identificador | Descrição |
|--------|--------------|-----------|
| Administrador | `admin` | Acesso total ao sistema (todas as funcionalidades de todos os movimentos). Acesso geral. |
| Dirigente | `dirig` | Acesso ao gerenciamento de eventos ativos (restrito ao seu movimento). Deve ter um movimento associado. Pode configurar informações e gerenciar contatos de seu movimento. |
| Coordenador (Estático) | `coord` | Perfil estático com privilégios intermediários (módulo de mensagens, listagem de trabalhadores e abas operacionais do gerenciamento do evento em que estiver trabalhando). |
| Coordenador de Equipe (Dinâmico) | `coord_equipe` | Qualquer usuário que for designado como coordenador de alguma equipe no evento ativo (`ind_coordenador = true`). Possui acesso à página de sua equipe (`/minha-equipe`). |
| Mercadinho (Dinâmico) | `sales` | Integrantes do mercadinho de um evento. O acesso é dinâmico com base na equipe em que estão alocados. Podem operar o mercadinho. |
| Visitação (Dinâmico) | `visit` | Integrantes responsáveis por um conjunto de fichas atribuídas. O acesso é dinâmico com base na equipe em que estão alocados. Podem usar a funcionalidade "Minhas Fichas". |
| Usuário | `user` | Acesso básico pós-login (timeline, inscrição em eventos, etc.). |

---

## Rotas públicas (sem autenticação)

Acessíveis por qualquer visitante, sem necessidade de login.

| Rota | Descrição |
|------|-----------|
| `GET /` | Página inicial |
| `POST /` | Envio de formulário de contato |

---

## Todos os perfis autenticados (`user`, `coord`, `dirig`, `admin`)

Qualquer usuário logado tem acesso às rotas abaixo.

### Fichas de inscrição (formulários públicos pós-login)

| Rota | Descrição |
|------|-----------|
| `GET /vem` | Formulário de inscrição VEM |
| `GET /ecc` | Formulário de inscrição ECC |
| `GET /sgm` | Formulário de inscrição SGM |

### Navegação e painéis

| Rota | Descrição |
|------|-----------|
| `GET /dashboard` | Painel principal |
| `GET /timeline` | Timeline de eventos |
| `GET /aniversario` | Aniversariantes |
| `GET /quadrante` | Geração do quadrante de trabalhadores |
| `GET /montagem` | Visualização da montagem |
| `GET /avaliacao` | Avaliação de trabalhadores |
| `POST /avaliacao` | Envio de avaliação |

### Termos

| Rota | Descrição |
|------|-----------|
| `GET /termo-sgm` | Termo de compromisso SGM |
| `GET /termo-vem` | Termo de compromisso VEM |

### Participantes

| Rota | Descrição |
|------|-----------|
| `GET /participantes` | Listagem de participantes |
| `POST /participantes` | Alteração de participante |
| `POST /participantes/{evento}/{pessoa}` | Confirmação de participação |

### Trabalhadores

| Rota | Descrição |
|------|-----------|
| `GET /trabalhadores/create` | Formulário de inscrição como trabalhador |
| `POST /trabalhadores` | Envio da inscrição |
| `GET /trabalhadores/review` | Revisão da própria inscrição |
| `DELETE /trabalhadores/{id}` | Remoção da própria inscrição |

### Pessoas

| Rota | Descrição |
|------|-----------|
| `GET /pessoas/{cpf}/busca` | Busca de pessoa por CPF (necessário para preenchimento de novas fichas) |
| `GET /pessoas/{pessoa}/edit` | Edição dos próprios dados pessoais |
| `PUT/PATCH /pessoas/{pessoa}` | Atualização dos próprios dados pessoais |

> **Importante:** o acesso é restrito à própria pessoa do usuário logado. Tentar editar os dados de outra pessoa retorna `403`. Administradores podem editar qualquer pessoa.

### Eventos

| Rota | Descrição |
|------|-----------|
| `GET /eventos` | Listagem de eventos |

### Configurações pessoais

| Rota | Descrição |
|------|-----------|
| `GET /settings/profile` | Edição do perfil |
| `GET /settings/password` | Alteração de senha |
| `GET /settings/appearance` | Preferências de aparência |

---

## Coordenador (Estático) e Administrador (`coord`, `admin`)

Além de tudo que o perfil autenticado básico acessa.

| Rota | Descrição |
|------|-----------|
| `GET /trabalhadores` | Listagem completa de trabalhadores |
| `POST /montagem` | Confirmação da montagem de equipe |

---

## Dirigente, Coordenador (Estático) e Administrador (`dirig`, `coord`, `admin`)

Além de tudo que os perfis básicos acessam.

| Rota | Descrição |
|------|-----------|
| `GET /eventos/{evento}/gerenciamento` | Gerenciamento de um evento específico (Coordenador tem acesso apenas às abas operacionais dos eventos de seu movimento) |

> **Observação:** Para `dirig` e `coord`, o acesso ao gerenciamento é restrito ao movimento indicado em `idt_movimento` na tabela `users`. Para `admin`, o acesso é irrestrito.

### Abas do gerenciamento de evento

| Aba | `coord` | `dirig` | `admin` |
|-----|---------|---------|---------|
| Resumo | ✓* | ✓* | ✓ |
| Participantes | ✓* | ✓* | ✓ |
| Trabalhadores | ✓* | ✓* | ✓ |
| Presença | ✓* | ✓* | ✓ |
| Crachás | ✗ | ✓* | ✓ |
| Quadrante | ✓* | ✓* | ✓ |
| Fichas | ✗ | ✓* | ✓ |
| Voluntários | ✗ | ✓* | ✓ |
| Prestação de Contas | ✗ | ✓* | ✓ |
| Restrições | ✓* | ✓* | ✓ |

> `*` Restrito ao movimento associado para `dirig` e `coord`.

---

## Coordenador (Estático), Dirigente, Coordenador de Equipe e Administrador (`coord`, `dirig`, `coord_equipe`, `admin`)

| Rota | Descrição |
|------|-----------|
| `GET /minha-equipe` | Visualização dos membros da equipe coordenada pelo usuário |

---

## Mercadinho e Administrador (`sales`, `admin`)

| Rota | Descrição |
|------|-----------|
| `GET /mercadinho/{evento?}` | Visualização e operação do mercadinho (vendas, catálogo, etc.) |

---

## Visitação e Administrador (`visit`, `admin`)

| Rota | Descrição |
|------|-----------|
| `GET /minhas-fichas` | Visualização e gerenciamento das fichas atribuídas para visitação |

---

## Dirigente, Visitação e Administrador (`dirig`, `visit`, `admin`)

Acesso ao CRUD e listagem das Fichas de inscrição (VEM, ECC, SGM). O acesso para Dirigentes e Visitação é restrito aos seus movimentos/fichas.

### Fichas VEM, ECC e SGM

| Rota | Descrição |
|------|-----------|
| `GET /fichas/{tipo}` | Listagem de fichas do tipo específico |
| `GET /fichas/{tipo}/{id}/approve` | Aprovação de ficha |
| `GET /fichas/{tipo}/create` | Formulário de criação de ficha |
| `POST /fichas/{tipo}` | Criação de ficha |
| `GET /fichas/{tipo}/{id}` | Visualização de ficha |
| `GET /fichas/{tipo}/{id}/edit` | Formulário de edição de ficha |
| `PUT/PATCH /fichas/{tipo}/{id}` | Atualização de ficha |
| `DELETE /fichas/{tipo}/{id}` | Exclusão de ficha |

---

## Somente Administrador (`admin`)

Acesso exclusivo a todas as operações de criação, edição, exclusão e visualização de recursos.

### Utilitários do Sistema

| Rota | Descrição |
|------|-----------|
| `GET /limpar-tudo` | Limpeza de cache, views e configurações |
| `GET /otimizar-tudo` | Otimização da aplicação |
| `GET /storage-link` | Criação de links simbólicos de storage |
| `GET /encerrar-eventos` | Finaliza eventos manualmente |

### Contatos

| Rota | Descrição |
|------|-----------|
| `GET /contatos` | Listagem de contatos recebidos |
| `DELETE /contatos/{id}` | Exclusão de contato |

### Eventos (CRUD completo)

| Rota | Descrição |
|------|-----------|
| `GET /eventos/create` | Formulário de criação |
| `POST /eventos` | Criação de evento |
| `GET /eventos/{evento}` | Visualização de evento |
| `GET /eventos/{evento}/edit` | Formulário de edição |
| `PUT/PATCH /eventos/{evento}` | Atualização de evento |
| `DELETE /eventos/{evento}` | Exclusão de evento |

### Pessoas (CRUD completo)

| Rota | Descrição |
|------|-----------|
| `GET /pessoas` | Listagem de pessoas |
| `GET /pessoas/create` | Formulário de criação |
| `POST /pessoas` | Criação de pessoa |
| `GET /pessoas/{pessoa}` | Visualização de pessoa |
| `GET /pessoas/{pessoa}/edit` | Formulário de edição |
| `PUT/PATCH /pessoas/{pessoa}` | Atualização de pessoa |
| `DELETE /pessoas/{pessoa}` | Exclusão de pessoa |


### Configurações do sistema

| Rota | Descrição |
|------|-----------|
| `GET /configuracoes` | Painel de configurações |
| `GET /configuracoes/role` | Gerenciamento de perfis de usuário |
| `POST /configuracoes/role` | Criação de perfil |
| `POST /configuracoes/role/change` | Alteração de perfil de usuário |
| `CRUD /configuracoes/equipe` | Tipos de equipe |
| `CRUD /configuracoes/movimento` | Tipos de movimento |
| `CRUD /configuracoes/responsavel` | Tipos de responsável |
| `CRUD /configuracoes/restricao` | Tipos de restrição |

---

## Resumo visual

```
Rota / Recurso                  │ user │ dirig │ coord │ coord_equipe │ sales │ visit │ admin
────────────────────────────────┼──────┼───────┼───────┼──────────────┼───────┼───────┼──────
Home / Contato                  │  ✓   │   ✓   │   ✓   │      ✓       │   ✓   │   ✓   │  ✓
Dashboard / Timeline            │  ✓   │   ✓   │   ✓   │      ✓       │   ✓   │   ✓   │  ✓
Aniversário / Quadrante         │  ✓   │   ✓   │   ✓   │      ✓       │   ✓   │   ✓   │  ✓
Montagem (visualizar)           │  ✓   │   ✓   │   ✓   │      ✓       │   ✓   │   ✓   │  ✓
Avaliação                       │  ✓   │   ✓   │   ✓   │      ✓       │   ✓   │   ✓   │  ✓
Termos SGM / VEM                │  ✓   │   ✓   │   ✓   │      ✓       │   ✓   │   ✓   │  ✓
Fichas (formulário inscrição)   │  ✓   │   ✓   │   ✓   │      ✓       │   ✓   │   ✓   │  ✓
Participantes                   │  ✓   │   ✓   │   ✓   │      ✓       │   ✓   │   ✓   │  ✓
Trabalhadores (inscrição)       │  ✓   │   ✓   │   ✓   │      ✓       │   ✓   │   ✓   │  ✓
Eventos (listagem)              │  ✓   │   ✓   │   ✓   │      ✓       │   ✓   │   ✓   │  ✓
Pessoa (editar próprios dados)  │  ✓   │   ✓   │   ✓   │      ✓       │   ✓   │   ✓   │  ✓
Settings pessoais               │  ✓   │   ✓   │   ✓   │      ✓       │   ✓   │   ✓   │  ✓
────────────────────────────────┼──────┼───────┼───────┼──────────────┼───────┼───────┼──────
Trabalhadores (listagem)        │  ✗   │   ✗   │   ✓   │      ✗       │   ✗   │   ✗   │  ✓
Montagem (confirmar)            │  ✗   │   ✗   │   ✓   │      ✗       │   ✗   │   ✗   │  ✓
────────────────────────────────┼──────┼───────┼───────┼──────────────┼───────┼───────┼──────
Minha Equipe                    │  ✗   │   ✓   │   ✓   │      ✓       │   ✗   │   ✗   │  ✓
Mercadinho                      │  ✗   │   ✗   │   ✗   │      ✗       │   ✓   │   ✗   │  ✓
Minhas Fichas                   │  ✗   │   ✗   │   ✗   │      ✗       │   ✗   │   ✓   │  ✓
────────────────────────────────┼──────┼───────┼───────┼──────────────┼───────┼───────┼──────
Gerenciamento de evento         │  ✗   │   ✓*  │   ✓*  │      ✗       │   ✗   │   ✗   │  ✓
  └ Resumo                      │  ✗   │   ✓*  │   ✓*  │      ✗       │   ✗   │   ✗   │  ✓
  └ Participantes               │  ✗   │   ✓*  │   ✓*  │      ✗       │   ✗   │   ✗   │  ✓
  └ Trabalhadores               │  ✗   │   ✓*  │   ✓*  │      ✗       │   ✗   │   ✗   │  ✓
  └ Presença                    │  ✗   │   ✓*  │   ✓*  │      ✗       │   ✗   │   ✗   │  ✓
  └ Crachás                     │  ✗   │   ✓*  │   ✗   │      ✗       │   ✗   │   ✗   │  ✓
  └ Quadrante                   │  ✗   │   ✓*  │   ✓*  │      ✗       │   ✗   │   ✗   │  ✓
  └ Fichas                      │  ✗   │   ✓*  │   ✗   │      ✗       │   ✗   │   ✗   │  ✓
  └ Voluntários                 │  ✗   │   ✓*  │   ✗   │      ✗       │   ✗   │   ✗   │  ✓
  └ Prestação de Contas         │  ✗   │   ✓*  │   ✗   │      ✗       │   ✗   │   ✗   │  ✓
────────────────────────────────┼──────┼───────┼───────┼──────────────┼───────┼───────┼──────
Contatos                        │  ✗   │   ✗   │   ✗   │      ✗       │   ✗   │   ✗   │  ✓
Pessoas (CRUD completo)         │  ✗   │   ✗   │   ✗   │      ✗       │   ✗   │   ✗   │  ✓
Fichas VEM/ECC/SGM (CRUD)       │  ✗   │   ✓*  │   ✗   │      ✗       │   ✗   │   ✓   │  ✓
Eventos (CRUD)                  │  ✗   │   ✗   │   ✗   │      ✗       │   ✗   │   ✗   │  ✓
Configurações do sistema        │  ✗   │   ✗   │   ✗   │      ✗       │   ✗   │   ✗   │  ✓
```

> `*` Restrito ao movimento associado para dirig e coord.
