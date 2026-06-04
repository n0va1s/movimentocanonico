---
name: perfis-acesso
description: Guia de perfis de acesso, permissões de usuários e controle de rotas no ecossistema Laravel do PNSL-NTM.
---

# Skill: Perfis de Acesso e Controle de Rotas (PNSL-NTM)

Esta Skill define as diretrizes obrigatórias e inegociáveis para o controle de acesso, permissões de usuários, restrições de rotas e segurança na camada de autorização do ecossistema Laravel do PNSL-NTM. Qualquer nova rota, middleware, controller ou política de acesso (Gate/Policy) deve seguir estritamente as definições deste documento.

---

## 1. Perfis Disponíveis

A autorização de usuários no sistema é categorizada nos quatro perfis abaixo, mapeados no banco de dados e controlados via middleware `role` aplicado nos grupos de rotas em `routes/web.php`.

| Perfil | Identificador | Descrição |
|--------|--------------|-----------|
| Administrador | `admin` | Acesso total ao sistema |
| Coordenador | `coord` | Acesso operacional a eventos e equipes |
| Especialista | `espec` | Acesso ao gerenciamento de eventos específicos |
| Usuário | `user` | Acesso básico pós-login |

---

## 2. Matriz de Rotas e Permissões

### Rotas Públicas (Sem Autenticação)
Acessíveis por qualquer visitante externo (sem necessidade de login).

| Rota | Descrição |
|------|-----------|
| `GET /` | Página inicial |
| `POST /` | Envio de formulário de contato |

---

### Todos os Perfis Autenticados (`user`, `coord`, `espec`, `admin`)
Qualquer usuário autenticado possui acesso básico a estas rotas.

#### Fichas de Inscrição (Formulários públicos pós-login)
*   `GET /vem` - Formulário de inscrição VEM
*   `GET /ecc` - Formulário de inscrição ECC
*   `GET /sgm` - Formulário de inscrição SGM

#### Navegação e Painéis
*   `GET /dashboard` - Painel principal
*   `GET /timeline` - Timeline de eventos
*   `GET /aniversario` - Aniversariantes
*   `GET /quadrante` - Geração do quadrante de trabalhadores
*   `GET /montagem` - Visualização da montagem
*   `GET /avaliacao` - Avaliação de trabalhadores
*   `POST /avaliacao` - Envio de avaliação

#### Termos
*   `GET /termo-sgm` - Termo de compromisso SGM
*   `GET /termo-vem` - Termo de compromisso VEM

#### Participantes
*   `GET /participantes` - Listagem de participantes
*   `POST /participantes` - Alteração de participante
*   `POST /participantes/{evento}/{pessoa}` - Confirmação de participação

#### Trabalhadores
*   `GET /trabalhadores/create` - Formulário de inscrição como trabalhador
*   `POST /trabalhadores` - Envio da inscrição
*   `GET /trabalhadores/review` - Revisão da própria inscrição
*   `DELETE /trabalhadores/{id}` - Remoção da própria inscrição

#### Pessoas
*   `GET /pessoas/{pessoa}/edit` - Edição dos próprios dados pessoais
*   `PUT/PATCH /pessoas/{pessoa}` - Atualização dos próprios dados pessoais
> **Regra de Segurança Crítica:** O acesso é estritamente restrito à própria pessoa do usuário logado. Tentar acessar ou editar os dados de outra pessoa deve retornar `403 Forbidden` (exceto para `admin`, que possui acesso irrestrito).

#### Eventos e Configurações Pessoais
*   `GET /eventos` - Listagem de eventos
*   `GET /settings/profile` - Edição do perfil
*   `GET /settings/password` - Alteração de senha
*   `GET /settings/appearance` - Preferências de aparência

---

### Coordenador e Administrador (`coord`, `admin`)
Acesso a rotas operacionais de controle de trabalhadores e montagem.

| Rota | Descrição |
|------|-----------|
| `GET /trabalhadores` | Listagem completa de trabalhadores |
| `POST /montagem` | Confirmação da montagem de equipe |

---

### Especialista, Coordenador e Administrador (`espec`, `coord`, `admin`)
Acesso restrito ao gerenciamento operacional de eventos específicos.

*   `GET /eventos/{evento}/gerenciamento` - Gerenciamento de um evento específico

> **Regra de Segurança Crítica:** Para `coord` e `espec`, o acesso ao gerenciamento é restrito **exclusivamente** aos eventos em que o usuário está ativamente cadastrado como trabalhador. Para `admin`, o acesso é irrestrito.

#### Permissões por Aba no Gerenciamento do Evento:
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

> `*` Exige que o usuário esteja ativamente cadastrado como trabalhador no evento específico. Além disso, o perfil `coord` exige `ind_coordenador = true` na tabela de trabalhadores para liberar o acesso.

---

### Somente Administrador (`admin`)
Acesso exclusivo e irrestrito a todas as operações críticas e CRUDs completos do sistema.

#### Contatos
*   `GET /contatos` - Listagem de contatos recebidos
*   `DELETE /contatos/{id}` - Exclusão de contato

#### Eventos (CRUD Completo)
*   `GET /eventos/create` | `POST /eventos` | `GET /eventos/{evento}` | `GET /eventos/{evento}/edit` | `PUT/PATCH /eventos/{evento}` | `DELETE /eventos/{evento}`

#### Pessoas (CRUD Completo)
*   `GET /pessoas` | `GET /pessoas/{cpf}/busca` | `GET /pessoas/create` | `POST /pessoas` | `GET /pessoas/{pessoa}` | `GET /pessoas/{pessoa}/edit` | `PUT/PATCH /pessoas/{pessoa}` | `DELETE /pessoas/{pessoa}`

#### Fichas VEM, ECC e SGM (CRUDs Completos)
*   `GET/POST/PUT/DELETE` para `/fichas/vem`, `/fichas/ecc` e `/fichas/sgm` (incluindo as rotas `/approve` de aprovação).

#### Configurações Globais do Sistema
*   `GET /configuracoes` - Painel de configurações
*   `GET /configuracoes/role` - Gerenciamento de perfis de usuário
*   `POST /configuracoes/role` - Criação de perfil
*   `POST /configuracoes/role/change` - Alteração de perfil de usuário
*   CRUDs auxiliares: `equipe`, `movimento`, `responsavel`, `restricao`

---

## 3. Resumo Visual de Permissões

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

---

## 4. Checklist de Validação de Acesso e Segurança

Sempre que criar novas rotas, endpoints, controllers ou views, valide os seguintes pontos:

- [ ] **Proteção de Rota:** A rota possui o middleware `role` explícito ou está encapsulada em um grupo de rotas com o middleware correspondente em `routes/web.php`?
- [ ] **Validação de ID Próprio:** Para rotas de edição de perfil/dados pessoais, o controller valida se o ID solicitado é exatamente igual a `auth()->id()` ou `auth()->user()->idt_pessoa`?
- [ ] **Vínculo com Evento:** Para rotas sob `/eventos/{evento}/gerenciamento`, o controller ou a Policy valida se o usuário autenticado (`coord` ou `espec`) está ativamente alocado como trabalhador nesse evento?
- [ ] **Validação de Coordenador:** Para o perfil `coord`, o controller valida se o flag `ind_coordenador` é verdadeiro para aquele usuário no contexto do evento?
- [ ] **Exclusividade Admin:** Qualquer alteração estrutural, CRUDs de base de dados globais (pessoas, eventos inteiros, contatos ou fichas) e configurações estão estritamente bloqueados para qualquer usuário que não possua a role `admin`?
