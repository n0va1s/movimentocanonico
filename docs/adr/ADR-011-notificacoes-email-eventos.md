# ADR-011: Implementação de Notificações por Email via Events e Listeners

- **Status:** Aceito
- **Data:** 2026-06-28

---

## Contexto

O sistema precisa enviar notificações por email para os usuários em diferentes momentos (como o envio de e-mail de boas-vindas após o registro, ou na confirmação de ações). Inicialmente, não havia uma estrutura padronizada para o disparo de emails no domínio da aplicação. A implementação requer uma solução extensível, de fácil manutenção e que siga as boas práticas do framework Laravel, promovendo uma clara separação de responsabilidades e evitando acoplamento forte nos fluxos principais de negócio.

## Decisão

Foi decidido utilizar a arquitetura orientada a eventos (Event-Driven) do Laravel para o envio de e-mails, aliada à fachada `Mail` e as classes `Mailable`.

1. **Events e Listeners:** O envio de e-mails será acionado exclusivamente por *Listeners* que "escutam" *Events* específicos que acontecem no sistema. Por exemplo, a classe `App\Listeners\SendWelcomeEmail` escuta o evento `Illuminate\Auth\Events\Registered`. Dessa forma, o fluxo de criação de conta (ou outro de negócio) apenas dispara o evento, sem se importar com o envio real do e-mail.
2. **Mailables:** Toda a configuração visual, anexos e variáveis de e-mail ficarão restritas em classes `Mailable` (como `App\Mail\BoasVindasMail`). Estas classes gerenciam os templates Blade e a composição da mensagem, oferecendo código transacional e elegante.

*Nota de Engenharia:* No momento atual da implementação (MVP), os envios são síncronos na mesma requisição (o Listener não implementa a interface `ShouldQueue`). Recomenda-se para o futuro a implementação de Jobs/Queues via `ShouldQueue` nos Listeners para melhorar o tempo de resposta HTTP.

## Alternativas consideradas

- **Envio Direto no Controller / Actions:** Executar a chamada da fachada `Mail::to(...)` diretamente dentro da Action de registro (ex: `CreateNewUser`).
  - *Descartada* porque quebra o princípio da responsabilidade única (SRP). Adicionar outras consequências futuras ao registro de um usuário deixaria a Action massiva.
- **Uso Exclusivo de Jobs e Filas Diretas:** Fazer um dispatch de Job de e-mail (ex: `dispatch(new EnviaEmailJob($user))`) diretamente após a ação principal, sem passar por eventos.
  - *Descartada parcialmente*. Eventos semanticamente descrevem melhor o contexto de uma ação que ocorreu ("O Usuário se registrou", "Ficha foi deferida") comparado a uma ordem imperativa de execução de serviço, garantindo que vários Listeners (não apenas o de E-mail) reajam ao mesmo evento, se necessário.

## Consequências

**Positivas:**
- Baixo acoplamento entre os fluxos síncronos da aplicação e a consequência da ação.
- Melhor testabilidade, permitindo simular e isolar os fluxos com `Event::fake()` e `Mail::fake()`.
- Centralização dos templates de e-mail e seus dados em classes Mailables, isolando também a interface de formatação da interface de regra de envio.

**Negativas:**
- Pequeno acréscimo na complexidade arquitetural e cognitiva; torna-se necessário verificar o mapa de Eventos (`EventServiceProvider` ou auto-discovery) para rastrear tudo o que acontece após uma determinada ação no sistema.
