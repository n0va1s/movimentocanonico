# Perfis de Acesso

Este documento descreve o que cada perfil de usuário pode acessar no sistema. O controle é feito via middleware `role` aplicado nos grupos de rotas em `routes/web.php`.

---

## Perfis disponíveis

| Perfil | Identificador | Descrição |
|--------|--------------|-----------|
| Administrador | `admin` | Acesso total ao sistema |
| Coordenador | `coord` | Acesso operacional a eventos e equipes |
| Especialista | `espec` | Acesso ao gerenciamento de eventos específicos (restrito ao seu movimento) |
| Usuário | `user` | Acesso básico pós-login |

---

## Rotas públicas (sem autenticação)

Acessíveis por qualquer visitante, sem necessidade de login.

| Rota | Descrição |
|------|-----------|
| `GET /` | Página inicial |
| `POST /` | Envio de formulário de contato |

---

## Todos os perfis autenticados (`user`, `coord`, `espec`, `admin`)

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

## Coordenador e Administrador (`coord`, `admin`)

Além de tudo que o perfil autenticado básico acessa.

| Rota | Descrição |
|------|-----------|
| `GET /trabalhadores` | Listagem completa de trabalhadores |
| `POST /montagem` | Confirmação da montagem de equipe |

---

## Especialista, Coordenador e Administrador (`espec`, `coord`, `admin`)

Além de tudo que os perfis anteriores acessam.

| Rota | Descrição |
|------|-----------|
| `GET /eventos/{evento}/gerenciamento` | Gerenciamento de um evento específico |

> **Observação:** Para `coord` e `espec`, o acesso ao gerenciamento é restrito aos eventos em que o usuário está cadastrado como trabalhador. Além disso, para `espec`, todo o acesso a eventos, fichas (VEM, ECC, SGM), importações e contadores do dashboard é restrito estritamente ao movimento indicado em `idt_movimento` na tabela `users`. Para `admin`, o acesso é irrestrito.

### Abas do gerenciamento de evento

| Aba | `coord` | `espec` | `admin` |
|-----|---------|---------|---------|
| Resumo | ✓* | ✓* | ✓ |
| Participantes | ✓* | ✓* | ✓ |
| Trabalhadores | ✓* | ✓* | ✓ |
| Presença | ✓* | ✓* | ✓ |
| Crachás | ✓* | ✓* | ✓ |
| Quadrante | ✓* | ✓* | ✓ |
| Fichas | ✗ | ✓* | ✓ |
| Voluntários | ✗ | ✓* | ✓ |
| Prestação de Contas | ✗ | ✓* | ✓ |

> `*` Restrito aos eventos em que o usuário está cadastrado como trabalhador. `coord` exige `ind_coordenador = true` na tabela de trabalhadores.

---

## Somente Administrador (`admin`)

Acesso exclusivo a todas as operações de criação, edição, exclusão e visualização de recursos.

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
| `GET /pessoas/{cpf}/busca` | Busca de pessoa por CPF |
| `GET /pessoas/create` | Formulário de criação |
| `POST /pessoas` | Criação de pessoa |
| `GET /pessoas/{pessoa}` | Visualização de pessoa |
| `GET /pessoas/{pessoa}/edit` | Formulário de edição |
| `PUT/PATCH /pessoas/{pessoa}` | Atualização de pessoa |
| `DELETE /pessoas/{pessoa}` | Exclusão de pessoa |

### Fichas VEM (CRUD completo)

| Rota | Descrição |
|------|-----------|
| `GET /fichas/vem` | Listagem de fichas VEM |
| `GET /fichas/vem/{id}/approve` | Aprovação de ficha |
| `GET /fichas/vem/create` | Formulário de criação |
| `POST /fichas/vem` | Criação de ficha |
| `GET /fichas/vem/{vem}` | Visualização de ficha |
| `GET /fichas/vem/{vem}/edit` | Formulário de edição |
| `PUT/PATCH /fichas/vem/{vem}` | Atualização de ficha |
| `DELETE /fichas/vem/{vem}` | Exclusão de ficha |

### Fichas ECC (CRUD completo)

| Rota | Descrição |
|------|-----------|
| `GET /fichas/ecc` | Listagem de fichas ECC |
| `GET /fichas/ecc/{id}/approve` | Aprovação de ficha |
| `GET /fichas/ecc/create` | Formulário de criação |
| `POST /fichas/ecc` | Criação de ficha |
| `GET /fichas/ecc/{ecc}` | Visualização de ficha |
| `GET /fichas/ecc/{ecc}/edit` | Formulário de edição |
| `PUT/PATCH /fichas/ecc/{ecc}` | Atualização de ficha |
| `DELETE /fichas/ecc/{ecc}` | Exclusão de ficha |

### Fichas SGM (CRUD completo)

| Rota | Descrição |
|------|-----------|
| `GET /fichas/sgm` | Listagem de fichas SGM |
| `GET /fichas/sgm/{id}/approve` | Aprovação de ficha |
| `GET /fichas/sgm/create` | Formulário de criação |
| `POST /fichas/sgm` | Criação de ficha |
| `GET /fichas/sgm/{sgm}` | Visualização de ficha |
| `GET /fichas/sgm/{sgm}/edit` | Formulário de edição |
| `PUT/PATCH /fichas/sgm/{sgm}` | Atualização de ficha |
| `DELETE /fichas/sgm/{sgm}` | Exclusão de ficha |

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
Rota / Recurso                  │ user │ espec │ coord │ admin
────────────────────────────────┼──────┼───────┼───────┼──────
Home / Contato                  │  ✓   │   ✓   │   ✓   │  ✓
Dashboard / Timeline            │  ✓   │   ✓   │   ✓   │  ✓
Aniversário / Quadrante         │  ✓   │   ✓   │   ✓   │  ✓
Montagem (visualizar)           │  ✓   │   ✓   │   ✓   │  ✓
Avaliação                       │  ✓   │   ✓   │   ✓   │  ✓
Termos SGM / VEM                │  ✓   │   ✓   │   ✓   │  ✓
Fichas (formulário inscrição)   │  ✓   │   ✓   │   ✓   │  ✓
Participantes                   │  ✓   │   ✓   │   ✓   │  ✓
Trabalhadores (inscrição)       │  ✓   │   ✓   │   ✓   │  ✓
Eventos (listagem)              │  ✓   │   ✓   │   ✓   │  ✓
Pessoa (editar próprios dados)  │  ✓   │   ✓   │   ✓   │  ✓
Settings pessoais               │  ✓   │   ✓   │   ✓   │  ✓
────────────────────────────────┼──────┼───────┼───────┼──────
Trabalhadores (listagem)        │  ✗   │   ✗   │   ✓   │  ✓
Montagem (confirmar)            │  ✗   │   ✗   │   ✓   │  ✓
────────────────────────────────┼──────┼───────┼───────┼──────
Gerenciamento de evento         │  ✗   │   ✓*  │   ✓*  │  ✓
  └ Resumo                      │  ✗   │   ✓*  │   ✓*  │  ✓
  └ Participantes               │  ✗   │   ✓*  │   ✓*  │  ✓
  └ Trabalhadores               │  ✗   │   ✓*  │   ✓*  │  ✓
  └ Presença                    │  ✗   │   ✓*  │   ✓*  │  ✓
  └ Crachás                     │  ✗   │   ✓*  │   ✓*  │  ✓
  └ Quadrante                   │  ✗   │   ✓*  │   ✓*  │  ✓
  └ Fichas                      │  ✗   │   ✓*  │   ✗   │  ✓
  └ Voluntários                 │  ✗   │   ✓*  │   ✗   │  ✓
  └ Prestação de Contas         │  ✗   │   ✓*  │   ✗   │  ✓
────────────────────────────────┼──────┼───────┼───────┼──────
Contatos                        │  ✗   │   ✗   │   ✗   │  ✓
Pessoas (CRUD completo)         │  ✗   │   ✗   │   ✗   │  ✓
Fichas VEM/ECC/SGM (CRUD)       │  ✗   │   ✗   │   ✗   │  ✓
Eventos (CRUD)                  │  ✗   │   ✗   │   ✗   │  ✓
Configurações do sistema        │  ✗   │   ✗   │   ✗   │  ✓
```

> `*` Restrito aos eventos em que o usuário está cadastrado como trabalhador. `coord` exige `ind_coordenador = true`.
