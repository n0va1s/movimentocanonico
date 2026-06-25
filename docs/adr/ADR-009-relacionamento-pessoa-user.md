# ADR-009: Relacionamento bidirecional entre Pessoa e User

- **Status:** Aceito
- **Data:** 2025-10-18
- **Commits de referência:**
  - `5cd71a3` — *Retaforei para criar o usuario apos salvar a pessoa via model e vice-versa*
  - `7551006` — *Refatoracao da tabela Pessoa para vincular a Users*
  - `d900efb` — *refactor: limpeza do cadastro de pessoa e novos campos de perfil*

---

## Contexto

O sistema distingue dois conceitos: **User** (conta de acesso ao sistema, gerenciada pelo Laravel Breeze/Auth) e **Pessoa** (entidade do domínio que representa um membro do movimento, com dados pessoais, fotos, restrições alimentares, etc.). Nem toda Pessoa tem um User (ex: cônjuges e filhos cadastrados automaticamente ao aprovar uma ficha ECC), e nem todo User tem uma Pessoa associada inicialmente.

Era necessário definir como esses dois conceitos se relacionam e como manter a consistência entre eles.

## Decisão

Estabelecer um relacionamento **1:1 opcional** entre `Pessoa` e `User`, com a chave estrangeira `user_id` na tabela `pessoa`. A criação é bidirecional via observers/hooks no model:

- Ao salvar uma `Pessoa` com email, um `User` correspondente é criado automaticamente se não existir.
- Ao criar um `User` via registro, uma `Pessoa` vinculada é criada automaticamente.

O email é o campo de ligação entre os dois registros.

## Alternativas consideradas

- **User como extensão de Pessoa (herança):** Pessoa seria a entidade base e User herdaria dela. Conflita com a estrutura do Laravel Auth, que espera um model `User` independente.
- **Pessoa dentro de User (campos adicionais na tabela users):** Simplificaria o modelo, mas misturaria dados de autenticação com dados de domínio, dificultando o caso de Pessoas sem acesso ao sistema.
- **Criação manual sempre separada:** O operador criaria Pessoa e User de forma independente. Descartado por ser propenso a inconsistências (Pessoa sem User ou vice-versa).

## Consequências

**Positivas:**
- Separação clara entre identidade de acesso (User) e entidade de domínio (Pessoa).
- Suporte ao caso de Pessoas sem conta de acesso (cônjuges, filhos de participantes ECC).
- Criação automática reduz trabalho manual e inconsistências.

**Negativas:**
- A lógica de criação automática acoplada ao model pode causar efeitos colaterais inesperados em testes e seeders (necessidade de `withoutObservers()` ou factories específicas).
- Sincronização de dados (ex: mudança de email) precisa ser tratada explicitamente nos dois models.
