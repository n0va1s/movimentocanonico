# HIS-002: Cadastro de Ficha do VEM

**Como** coordenação geral,
**quero** cadastrar, editar e excluir fichas de candidatos ao VEM,
**para que** possamos organizar e acompanhar as inscrições, informações pessoais e restrições dos participantes.

---

## Critérios de Aceitação

- ✅ O sistema deve permitir o preenchimento dos dados pessoais do candidato, incluindo nome, apelido, data de nascimento, contato e endereço.
- ✅ O sistema deve permitir a escolha do evento ao qual o candidato está se inscrevendo.
- ✅ O sistema deve permitir informar os responsáveis (pai, mãe ou outro) e seus respectivos contatos.
- ✅ O sistema deve permitir indicar se o candidato possui restrição alimentar e, se sim, listar e detalhar as restrições.
- ✅ O sistema deve validar campos obrigatórios com mensagens claras.
- ✅ Ao salvar, os dados devem ser persistidos corretamente nas tabelas relacionadas (`ficha`, `ficha_vem`, `ficha_saude`, etc.).
- ✅ O sistema deve redirecionar para a listagem após o cadastro com uma mensagem de sucesso.
- ✅ O layout deve ser responsivo e aderente ao padrão visual do sistema (Tailwind CSS).
- ✅ O formulário deve ser bloqueado para edição se a ficha estiver com `ind_aprovado = true`.
- ✅ Deve ser possível visualizar os dados mesmo quando bloqueados.
- ✅ As opções de restrição alimentar devem aparecer apenas quando selecionada a opção "Possui restrição alimentar".
- ✅ Caso a opção de restrição alimentar seja desmarcada, o sistema deve apagar quaisquer informações anteriormente preenchidas sobre as restrições.

---

## Campos do Formulário

| Campo                       | Tipo              | Obrigatório  | Regras / Observações                                  |
|-----------------------------|-------------------|--------------|-------------------------------------------------------|
| Evento                      | Select            | Sim          | Deve listar apenas eventos do tipo VEM                |
| Nome Completo               | Texto             | Sim          | Máximo de 255 caracteres                              |
| Apelido                     | Texto             | Sim          | Máximo de 100 caracteres                              |
| Data de Nascimento          | Data              | Sim          | Formato: AAAA-MM-DD                                   |
| Gênero                      | Select            | Sim          | Opções: M, F, Outro                                   |
| Telefone                    | Texto             | Não          | Máscara: (00) 00000-0000                              |
| Email                       | Email             | Sim          | Deve ser válido                                       |
| Endereço                    | Texto             | Não          | Até 500 caracteres                                    |
| Tamanho da Camiseta         | Select            | Sim          | Opções: PP, P, M, G, GG                               |
| Como soube do evento        | Select            | Não          | Ex: Indicação, Padre, Outro                           |
| É católico?                 | Checkbox          | Não          | Valor booleano                                        |
| Toca instrumento?           | Checkbox          | Não          | Valor booleano                                        |
| Nome do Pai                 | Texto             | Não          | Campo usado em `ficha_vem`                            |
| Telefone do Pai             | Texto             | Não          |                                                       |
| Nome da Mãe                 | Texto             | Não          |                                                       |
| Telefone da Mãe             | Texto             | Não          |                                                       |
| Onde estuda                 | Texto             | Não          |                                                       |
| Com quem mora               | Texto             | Não          |                                                       |
| Falar com (responsável)     | Select            | Não          | Lista de responsáveis pré-cadastrados                 |
| Possui restrição alimentar? | Checkbox          | Não          | Exibe campos de restrição se marcado                  |
| Restrições alimentares      | Lista + Texto     | Condicional  | Seleção múltipla com complemento opcional             |

---

## Mensagens Esperadas

| Situação                                  | Mensagem                                                    |
|-------------------------------------------|-------------------------------------------------------------|
| Cadastro realizado com sucesso            | "Ficha cadastrada com sucesso!"                             |
| Campo nome completo vazio                 | "O campo nome completo é obrigatório."                      |
| Data de nascimento não preenchida         | "A data de nascimento é obrigatória."                       |
| Email inválido                            | "O campo email deve conter um endereço válido."             |
| Edição concluída com sucesso              | "Ficha atualizada com sucesso!"                             |
| Tentativa de editar ficha aprovada        | "Não é possível editar: a ficha já foi aprovada."           |
