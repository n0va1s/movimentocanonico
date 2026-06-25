# ADR-006: Deploy via FTP para Locaweb usando GitHub Actions

- **Status:** Aceito
- **Data:** 2026-02-20
- **Commits de referência:**
  - `4f2c92b` — *Adiciona workflow de deploy via FTP para Locaweb*
  - `1c29238` — *Refatora workflows de CI/CD: remove lint e testes, ajusta deploy*
  - `e169b80` — *Ajuste no diretório do servidor para o deploy via FTP. Agora vai liso*

---

## Contexto

O sistema é hospedado na Locaweb em um plano de hospedagem compartilhada que não oferece acesso SSH direto nem suporte a containers. O deploy manual via FTP era lento, propenso a erros e dependia de um desenvolvedor com acesso às credenciais. Era necessário automatizar o processo de entrega.

## Decisão

Criar um workflow no **GitHub Actions** (`.github/workflows/deploy.yml`) que, ao receber um push na branch `main`, realiza o upload dos arquivos via FTP para o servidor Locaweb. As credenciais FTP são armazenadas como secrets no repositório GitHub. O workflow permite também execução manual via `workflow_dispatch`.

## Alternativas consideradas

- **Deploy via SSH + rsync:** Mais eficiente e seguro, mas a Locaweb no plano utilizado não oferece acesso SSH.
- **Deploy via Git no servidor:** Requereria acesso SSH e configuração de hooks no servidor remoto.
- **Serviço de hospedagem com suporte a containers (Railway, Render, Fly.io):** Mais moderno e alinhado com a dockerização do ambiente de desenvolvimento, mas implicaria migração de hospedagem e custo adicional.

## Consequências

**Positivas:**
- Deploy automatizado e reproduzível a cada push na branch principal.
- Credenciais protegidas como secrets do GitHub, sem exposição no código.
- Possibilidade de trigger manual para deploys sob demanda.

**Negativas:**
- FTP é um protocolo não criptografado por padrão (mitigado pelo uso de FTPS quando disponível).
- Sem rollback automático: em caso de falha, é necessário re-deploy manual da versão anterior.
- O ambiente de produção (hospedagem compartilhada) diverge do ambiente de desenvolvimento (Docker), aumentando o risco de inconsistências.
- Lint e testes foram removidos do pipeline de CI para simplificar o fluxo, reduzindo a proteção contra regressões.
