# HIS-008: Inscrição para Evento como Trabalhador

**Como** usuário autenticado,
**quero** me inscrever para trabalhar em um evento,
**para que** eu possa me voluntariar em alguma equipe e contribuir para que o encontro aconteça.

---

## Critérios de Aceitação

- ✅ O nome completo e o telefone devem ser preenchidos automaticamente com os dados do usuário logado e não devem ser editáveis.
- ✅ O sistema deve permitir a escolha de no mínimo 1 e no máximo 3 equipes de interesse.
- ✅ O campo Habilidades deve ser de livre preenchimento.
- ✅ O campo "Primeira vez trabalhando?" deve ter o valor padrão como "Não".
- ✅ Deve ser exibida uma mensagem de sucesso após a inscrição ser realizada.
- ✅ Os dados do interessado devem ser salvos corretamente no banco de dados como candidato a trabalhador do evento.
- ✅ Deve haver a possibilidade de cancelar a inscrição e retornar à listagem de eventos.
- ✅ O layout do formulário deve ser responsivo e seguir os padrões visuais do sistema (Tailwind CSS).

---

## Campos do Formulário

| Campo                      | Tipo               | Obrigatório | Regras                                          |
|----------------------------|--------------------|-------------|-------------------------------------------------|
| Nome Completo              | Texto (bloqueado)  | Sim         | Preenchido automaticamente. Máx. 50 caracteres  |
| Telefone                   | Texto (bloqueado)  | Sim         | Preenchido automaticamente. Até 11 caracteres   |
| Equipes de Interesse       | Seleção múltipla   | Não         | Mínimo 1, máximo 3 equipes                      |
| Habilidades                | Texto              | Não         | Máximo de 255 caracteres                        |
| Primeira vez trabalhando?  | Checkbox           | Não         | Valor booleano. Padrão: Não                     |

---

## Mensagens Esperadas

| Situação                                      | Mensagem                                                                                                              |
|-----------------------------------------------|-----------------------------------------------------------------------------------------------------------------------|
| Inscrição realizada com sucesso               | "Inscrição feita com sucesso! Fique de olho no celular, vamos entrar em contato por ligação ou por mensagem."        |
| Mais de 3 equipes selecionadas                | "Você pode selecionar no máximo 3 equipes de interesse."                                                              |
