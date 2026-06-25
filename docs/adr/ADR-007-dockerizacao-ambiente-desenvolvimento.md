# ADR-007: Dockerização do ambiente de desenvolvimento e produção

- **Status:** Aceito
- **Data:** 2025-12-09
- **Commits de referência:**
  - `b4c8631` — *Dockerização da aplicação. Ampliação da cobertura de testes*
  - `ac8df93` — *Revisão da dockerização da aplicação*
  - `5e82f6a` — *Atualizacao das bibliotecas e padronizacao da configuracao docker*

---

## Contexto

O time de desenvolvimento trabalha em máquinas com sistemas operacionais diferentes. A ausência de um ambiente padronizado causava o clássico problema de "funciona na minha máquina": diferenças de versão de PHP, MySQL e Node geravam bugs difíceis de reproduzir. Além disso, o projeto precisava de uma configuração documentada para facilitar a entrada de novos colaboradores.

## Decisão

Adotar **Docker** e **Docker Compose** para padronizar o ambiente de desenvolvimento. Foram criados:

- `Dockerfile` — imagem base para desenvolvimento
- `Dockerfile.production` — imagem otimizada para produção
- `docker-compose.yml` — orquestração local (PHP-FPM, Nginx, MySQL)
- `docker-compose.production.yml` — configuração de produção
- `docker/nginx/nginx.conf`, `docker/php/php.ini`, `docker/php/opcache.ini`, `docker/mysql/my.cnf` — configurações específicas por serviço
- `deploy.sh` — script de deploy assistido

## Alternativas consideradas

- **Laravel Sail:** O wrapper oficial do Laravel para Docker. Descartado por ser mais opinativo e menos flexível para as configurações específicas de Nginx e MySQL necessárias.
- **Ambiente local nativo (XAMPP/Laragon):** Já era o padrão anterior. Descartado por não garantir paridade entre máquinas dos desenvolvedores.
- **Vagrant:** Mais pesado que Docker e com adoção decrescente na comunidade PHP.

## Consequências

**Positivas:**
- Ambiente idêntico para todos os desenvolvedores, independente do SO.
- Onboarding simplificado: `docker compose up` sobe toda a stack.
- Configurações de PHP, Nginx e MySQL versionadas junto ao código.
- Imagem de produção separada com otimizações (OPcache, sem devDependencies).

**Negativas:**
- O ambiente de produção real (Locaweb hospedagem compartilhada) não usa Docker, criando divergência entre o ambiente dockerizado e o de produção.
- Overhead de aprendizado para desenvolvedores não familiarizados com Docker.
- Consumo de recursos maior em máquinas com pouca RAM.
