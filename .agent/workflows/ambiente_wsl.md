---
description: Configurações de ambiente de desenvolvimento com WSL e Laravel Sail
---

# Diretivas do Ambiente de Desenvolvimento (WSL & Laravel Sail)

## 1. Contexto do Ambiente
- **Sistema Operacional Host:** Windows (executando comandos no PowerShell).
- **Sistema Operacional de Execução (Runtime):** Linux / WSL.
- **Ambiente Laravel:** Laravel Sail (Docker / Docker Compose rodando no WSL).

## 2. Regra de Ouro para Comandos
Sempre que você precisar rodar qualquer comando de terminal (Artisan, Composer, PHP, npm, etc.), **nunca** rode-o diretamente no ambiente Windows. Você deve envelopar o comando usando o prefixo do WSL e do Laravel Sail.

### Exemplos de Tradução de Comandos:

| Ação Desejada | Comando Tradicional | Comando Correto para Executar |
| :--- | :--- | :--- |
| Rodar Migrations | `php artisan migrate` | `wsl ./vendor/bin/sail artisan migrate` |
| Executar Testes | `php artisan test` / `vendor/bin/pest` | `wsl ./vendor/bin/sail test` |
| Rodar Teste Específico | `php artisan test --filter=NomeTest` | `wsl ./vendor/bin/sail test --filter=NomeTest` |
| Instalar Pacote Composer | `composer require vendor/package` | `wsl ./vendor/bin/sail composer require vendor/package` |
| Rodar Dev Server Node | `npm run dev` | `wsl ./vendor/bin/sail npm run dev` |
| Compilar Assets Node | `npm run build` | `wsl ./vendor/bin/sail npm run build` |
| Rodar Tinker | `php artisan tinker` | `wsl ./vendor/bin/sail tinker` |

## 3. Leitura e Escrita de Arquivos
- Para **ler, pesquisar ou escrever em arquivos** (usando ferramentas como `view_file`, `write_to_file`, `replace_file_content` ou `grep_search`), continue usando os caminhos absolutos do Windows normalmente (ex: `c:\Users\S852362501\Documents\Projects\pnsl-ntm\...`). As ferramentas do agente lidam com isso automaticamente.
- Apenas a **execução de comandos de runtime** (onde o PHP, Node ou o Banco de Dados são necessários) deve ser enviada via WSL + Sail.

## 4. Banco de Dados e Serviços
- O banco de dados (MySQL/MariaDB), Redis, Mailpit e outros serviços rodam dentro dos containers Docker do Sail no WSL.
- Não tente conectar usando portas locais do Windows diretamente se o Sail não as tiver exposto no `compose.yaml`. Sempre confie na execução de comandos internos pelo Sail.
