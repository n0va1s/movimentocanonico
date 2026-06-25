# ADR-005: Notificações de sistema via Telegram

- **Status:** Aceito
- **Data:** 2026-04-26
- **Commits de referência:**
  - `e388089` — *adicionado biblioteca de notificação telegram*
  - `e6e3bc4` — *adicionado service do bot api*
  - `b5bbd31` — *adicionado no store envio de notificação*
  - `27771be` — *feat: add system exception notifications to Telegram*

---

## Contexto

O sistema precisa notificar a equipe de operação sobre eventos críticos (exceções não tratadas, erros de sistema) e sobre ações relevantes dos usuários (envio de contatos pela página pública). O ambiente de hospedagem (Locaweb via FTP) não oferece infraestrutura de monitoramento nativa, e o time não possui ferramentas de APM configuradas.

## Decisão

Utilizar o **Telegram Bot API** como canal de notificações operacionais. Uma biblioteca PHP de notificação Telegram foi adicionada via Composer. Foi criado um `TelegramService` que encapsula o envio de mensagens, e as notificações são disparadas em dois pontos: ao receber um novo contato pela página pública e ao capturar exceções não tratadas pelo sistema.

## Alternativas consideradas

- **Email:** Já utilizado para comunicação com usuários finais (boas-vindas, aniversário). Menos adequado para alertas operacionais em tempo real por causa da latência e do risco de cair em spam.
- **Slack:** Requereria conta paga ou workspace dedicado. O time já usa Telegram para comunicação interna.
- **Serviços de APM (Sentry, Bugsnag):** Mais completos, mas com custo adicional e configuração mais complexa para o ambiente atual.

## Consequências

**Positivas:**
- Alertas em tempo real no canal que o time já monitora.
- Implementação simples e sem custo adicional.
- Funciona independentemente da infraestrutura de hospedagem.

**Negativas:**
- Dependência de um serviço externo (Telegram) para monitoramento.
- Sem histórico estruturado de erros (apenas mensagens no chat).
- Token do bot precisa ser gerenciado como segredo no ambiente de produção.
- Não substitui um sistema de logging estruturado para análise de tendências.
