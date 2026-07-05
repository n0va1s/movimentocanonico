# Regras Globais do Projeto (Movimento Canônico)

O agente deve RIGOROSAMENTE seguir estas regras e diretrizes em todas as suas ações.

## 1. Ambiente de Desenvolvimento (WSL & Laravel Sail)
- **Uso do Laravel Sail:** Este projeto utiliza Laravel Sail. O agente **NUNCA** deve executar comandos PHP, Composer, Artisan ou NPM diretamente no shell local.
- **Formato dos Comandos:** Todos os comandos de execução de aplicação devem ser executados através do Sail (ex: `./vendor/bin/sail artisan migrate`, `./vendor/bin/sail test`, `./vendor/bin/sail npm run dev`).
- **Contexto WSL:** O ambiente do agente já roda dentro do WSL (Linux). Não use o comando `wsl` antes do Sail no terminal bash, use diretamente `./vendor/bin/sail`.

## 2. Arquitetura Frontend e UI (Laravel 13)
- **Componentes Volt:** Na criação de novas páginas e funcionalidades frontend, o agente DEVE focar na utilização do **Livewire Volt** (arquitetura single-file para componentes Livewire), mantendo a lógica de UI e a view juntas de forma coesa quando apropriado.
- **TailwindCSS & Flux:** Priorize ABSOLUTAMENTE o uso de utilitários do **TailwindCSS** e da biblioteca de componentes **Livewire Flux** (`<flux:...>`) para a construção de interfaces modernas, consistentes e rápidas.

## 3. Integração com Skills e Padrões (MANDATÓRIO)
O agente deve aplicar de forma proativa as diretrizes documentadas nas seguintes Skills:

### A. Design System, Acessibilidade (a11y) e Mobile First
- O Design System da aplicação é regido pela skill **`desenvolvimento-frontend`**.
- O agente deve **SEMPRE** garantir altíssima acessibilidade (padrões a11y), suporte a navegação por teclado, atributos ARIA adequados e design responsivo com foco absoluto em **Mobile First**.

### B. Banco de Dados e Modelagem
- Siga RIGOROSAMENTE as diretrizes da skill **`padroes-banco-dados`**.
- Respeite o padrão de nomenclatura de banco de dados para tabelas, colunas, chaves primárias, estrangeiras e convenções de modelagem.

### C. Segurança e Perfis de Acesso
- A segurança, middlewares e controle de rotas são regidos pela skill **`perfis-acesso`**.
- Sempre respeite e implemente as validações e os perfis definidos para a aplicação de forma que usuários e perfis estejam devidamente isolados.
