# ADR-008: Gates de autorização para controle de acesso por perfil

- **Status:** Aceito
- **Data:** 2026-05-12
- **Commits de referência:**
  - `647775a` — *refactor: criacao de gates de autorização para admin, coord, user e espec*
  - `2a99188` — *Refatoração da gestão de perfis e introdução de novas funcionalidades de equipes e restrições*
  - `98776dd` — *Renomeada a PerfilUsuario para Role para facilitar leitura*

---

## Contexto

O sistema possui quatro perfis de acesso: **admin**, **coord** (coordenador), **user** (usuário comum) e **espec** (especialista). Cada perfil tem permissões distintas sobre as rotas e funcionalidades. A implementação inicial usava um middleware customizado (`OnlyManagerMiddleware`) que verificava o perfil diretamente, sem uma abstração clara de autorização. Isso tornava difícil adicionar novas regras e testar o controle de acesso.

## Decisão

Substituir o middleware de verificação direta por **Laravel Gates** definidos no `AppServiceProvider`. Cada gate corresponde a um perfil (`is-admin`, `is-coord`, `is-user`, `is-espec`) e verifica o campo de perfil do `User` autenticado. As rotas protegidas usam o middleware `can:nome-do-gate` ou verificações `Gate::allows()` nas views. Um `RoleMiddleware` foi criado para aplicar os gates nas rotas de forma declarativa.

Os perfis são representados pelo Enum `Perfil` (ver ADR-002), garantindo consistência entre a definição dos gates e os valores armazenados.

## Alternativas consideradas

- **Pacote Spatie Laravel Permission:** Solução completa com roles e permissions em banco de dados. Descartado por ser mais complexo do que o necessário para quatro perfis fixos sem necessidade de configuração dinâmica em produção.
- **Policies por model:** Adequado para autorização em nível de recurso (ex: "este usuário pode editar este evento?"). Complementar aos gates, mas não substitui a verificação de perfil global.
- **Middleware customizado por rota:** Abordagem anterior. Descartada por dispersar a lógica de autorização e dificultar testes centralizados.

## Consequências

**Positivas:**
- Lógica de autorização centralizada no `AppServiceProvider`, fácil de auditar.
- Gates são testáveis de forma isolada.
- Integração nativa com diretivas Blade (`@can`) e helpers (`Gate::allows()`).
- Teste dedicado `AutorizacaoRotasTest` verifica que todas as rotas protegidas respondem corretamente para cada perfil.

**Negativas:**
- Perfis são fixos no código; adicionar um novo perfil requer deploy.
- Gates verificam apenas o perfil, sem granularidade de permissão por recurso específico (para isso seriam necessárias Policies).
