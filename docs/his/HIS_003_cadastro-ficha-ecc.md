# HIS-003: Cadastro de Ficha do ECC

**Como** coordenação geral,
**quero** cadastrar, editar e revisar as fichas de casais participantes,
**para que** possamos organizar o encontro de forma completa, coletando as informações de ambos os cônjuges.

---

## Critérios de Aceitação

- ✅ O sistema deve permitir o preenchimento dos dados pessoais do candidato e do cônjuge.
- ✅ O sistema deve permitir selecionar o evento ECC relacionado.
- ✅ O sistema deve permitir informar o tamanho da camiseta do candidato e do cônjuge.
- ✅ O sistema deve validar os campos obrigatórios com mensagens de erro.
- ✅ O sistema deve permitir marcar se o participante possui restrição alimentar.
- ✅ As opções de restrição alimentar devem aparecer apenas quando selecionada a opção "Possui restrição alimentar".
- ✅ Caso a opção de restrição alimentar seja desmarcada, o sistema deve apagar quaisquer informações anteriormente preenchidas sobre as restrições.
- ✅ O formulário deve seguir os padrões visuais do sistema e ser responsivo.
- ✅ O sistema deve redirecionar para a listagem com uma mensagem de confirmação após o cadastro bem-sucedido.
- ✅ Deve ser possível cancelar o preenchimento e retornar para a listagem.

---

## Campos do Formulário

### Dados do Candidato

| Campo                   | Tipo     | Obrigatório | Regras                                    |
|-------------------------|----------|-------------|-------------------------------------------|
| Nome completo           | Texto    | Sim         | Máximo de 255 caracteres                  |
| Apelido                 | Texto    | Sim         | Máximo de 100 caracteres                  |
| Data de nascimento      | Data     | Sim         | Formato AAAA-MM-DD                        |
| Telefone                | Texto    | Não         | Máscara nacional (até 20 caracteres)      |
| E-mail                  | Email    | Sim         | E-mail válido                             |
| Endereço                | Texto    | Não         | Até 500 caracteres                        |
| Tamanho da camiseta     | Select   | Sim         | PP, P, M, G, GG                           |
| Como soube do evento    | Select   | Não         | Indicação, Padre, Outro                   |
| Católico                | Checkbox | Não         | Valor booleano                            |
| Toca instrumento        | Checkbox | Não         | Valor booleano                            |

### Dados do Cônjuge

| Campo                          | Tipo   | Obrigatório | Regras                                    |
|--------------------------------|--------|-------------|-------------------------------------------|
| Nome completo do cônjuge       | Texto  | Sim         | Máximo de 150 caracteres                  |
| Apelido do cônjuge             | Texto  | Não         | Máximo de 50 caracteres                   |
| Telefone do cônjuge            | Texto  | Sim         | Máscara nacional (até 15 caracteres)      |
| Data de nascimento do cônjuge  | Data   | Sim         | Formato AAAA-MM-DD                        |
| Tamanho da camiseta do cônjuge | Select | Sim         | PP, P, M, G, GG                           |

### Restrições Alimentares

| Campo                        | Tipo          | Obrigatório  | Regras                                           |
|------------------------------|---------------|--------------|--------------------------------------------------|
| Possui restrição alimentar   | Checkbox      | Não          | Mostra/esconde os campos abaixo                  |
| Tipo de restrição alimentar  | Checkbox      | Condicional  | Lista de tipos (com base no banco)               |
| Complemento da restrição     | Texto         | Condicional  | Um por restrição selecionada, máx. 255 caracteres |

---

## Mensagens Esperadas

| Situação                                          | Mensagem                                                                    |
|---------------------------------------------------|-----------------------------------------------------------------------------|
| Cadastro realizado com sucesso                    | "Ficha do ECC cadastrada com sucesso!"                                      |
| Campo nome do cônjuge vazio                       | "O nome do cônjuge é obrigatório."                                          |
| Data de nascimento do cônjuge não preenchida      | "A data de nascimento do cônjuge é obrigatória."                            |
| Tamanho de camiseta do cônjuge não selecionado    | "O tamanho da camiseta do cônjuge é obrigatório."                           |
| Restrição desmarcada após preenchimento           | "As restrições alimentares foram apagadas porque a opção foi desmarcada."   |
| Erro ao salvar                                    | "Erro ao salvar a ficha. Verifique os campos obrigatórios."                 |
