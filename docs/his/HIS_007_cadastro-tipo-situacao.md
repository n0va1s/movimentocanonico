# HIS-007: Cadastro de Tipo de Situação

**Como** administrador do sistema,
**quero** cadastrar, editar e excluir tipos de situação (ex: Cadastrada, Avaliada, Encaminhada),
**para que** o status das fichas seja devidamente classificado e organizado.

---

## Critérios de Aceitação

- ✅ O sistema deve permitir o preenchimento do nome do tipo de situação (obrigatório).
- ✅ O sistema deve bloquear o envio do formulário com o campo nome em branco.
- ✅ O formulário deve exibir mensagens de erro caso campos obrigatórios estejam vazios ou inválidos.
- ✅ O tipo de situação deve ser salvo corretamente no banco de dados com todos os dados informados.
- ✅ Ao cadastrar com sucesso, o sistema deve redirecionar para a listagem com uma mensagem de confirmação.
- ✅ Deve haver a possibilidade de cancelar o cadastro e retornar à listagem.
- ✅ O layout do formulário deve ser responsivo e seguir os padrões visuais do sistema (Tailwind CSS).
- ✅ A exclusão de um tipo de situação só deve ser permitida se ele não estiver associado a nenhuma ficha.

---

## Campos do Formulário

| Campo               | Tipo  | Obrigatório | Regras                    |
|---------------------|-------|-------------|---------------------------|
| Nome Tipo Situação  | Texto | Sim         | Máximo de 10 caracteres   |
| Descrição           | Texto | Não         | Máximo de 255 caracteres  |

---

## Mensagens Esperadas

| Situação                                          | Mensagem                                                                  |
|---------------------------------------------------|---------------------------------------------------------------------------|
| Campo nome vazio                                  | "Nome da Situação é obrigatório."                                         |
| Tentativa de excluir tipo vinculado a fichas      | "Não é possível excluir: este tipo de situação está vinculado a fichas."  |
| Cadastro realizado com sucesso                    | "Tipo de situação cadastrado com sucesso!"                                |
