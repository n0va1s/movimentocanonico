# HIS-001: Cadastro de Evento

**Como** coordenação geral,
**quero** cadastrar novos eventos no sistema,
**para que** eles fiquem disponíveis para gerenciamento e participação pelos demais usuários.

---

## Critérios de Aceitação

- ✅ O sistema deve permitir o preenchimento do nome/descrição do evento (obrigatório).
- ✅ O sistema deve permitir informar um número identificador do evento (opcional).
- ✅ O sistema deve permitir a definição das datas de início e término do evento (ambas opcionais, mas com validação lógica).
- ✅ Se preenchidas, a data de término não pode ser anterior à data de início.
- ✅ Deve haver uma opção de marcar o evento como "Pós Encontro".
- ✅ O formulário deve exibir mensagens de erro caso campos obrigatórios estejam vazios ou inválidos.
- ✅ O evento deve ser salvo corretamente no banco de dados com todos os dados informados.
- ✅ Ao cadastrar com sucesso, o sistema deve redirecionar para a listagem de eventos com uma mensagem de confirmação.
- ✅ Deve haver a possibilidade de cancelar o cadastro e retornar à listagem de eventos.
- ✅ O layout do formulário deve ser responsivo e seguir os padrões visuais do sistema (Tailwind CSS).

---

## Campos do Formulário

| Campo               | Tipo     | Obrigatório | Regras                                                      |
|---------------------|----------|-------------|-------------------------------------------------------------|
| Descrição do Evento | Texto    | Sim         | Máximo de 255 caracteres                                    |
| Número do Evento    | Texto    | Não         | Até 5 caracteres                                            |
| Data de Início      | Data     | Não         | Se preenchido, deve ser igual ou anterior à data de término |
| Data de Término     | Data     | Não         | Se preenchido, deve ser igual ou posterior à data de início |
| Pós Encontro        | Checkbox | Não         | Valor booleano (sim/não)                                    |

---

## Mensagens Esperadas

| Situação                                          | Mensagem                                                          |
|---------------------------------------------------|-------------------------------------------------------------------|
| Cadastro realizado com sucesso                    | "Evento cadastrado com sucesso!"                                  |
| Campo descrição vazio                             | "A descrição do evento é obrigatória."                            |
| Data de término anterior à data de início         | "A data de término não pode ser anterior à data de início."       |
