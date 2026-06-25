# HIS-006: Cadastro de Tipo de Responsável

**Como** administrador do sistema,
**quero** cadastrar, editar e excluir tipos de responsáveis (ex: Mãe, Pai, Avó, Avô),
**para que** os responsáveis pelos candidatos sejam devidamente classificados e organizados nas fichas.

---

## Critérios de Aceitação

- ✅ O sistema deve permitir o preenchimento do nome do tipo de responsável (obrigatório).
- ✅ O sistema deve bloquear o envio do formulário com o campo nome em branco.
- ✅ O formulário deve exibir mensagens de erro caso campos obrigatórios estejam vazios ou inválidos.
- ✅ O tipo de responsável deve ser salvo corretamente no banco de dados com todos os dados informados.
- ✅ Ao cadastrar com sucesso, o sistema deve redirecionar para a listagem com uma mensagem de confirmação.
- ✅ Deve haver a possibilidade de cancelar o cadastro e retornar à listagem.
- ✅ O layout do formulário deve ser responsivo e seguir os padrões visuais do sistema (Tailwind CSS).
- ✅ A exclusão de um tipo de responsável só deve ser permitida se ele não estiver associado a nenhuma ficha ou pessoa.

---

## Campos do Formulário

| Campo                 | Tipo  | Obrigatório | Regras                   |
|-----------------------|-------|-------------|--------------------------|
| Nome Tipo Responsável | Texto | Sim         | Máximo de 10 caracteres  |

---

## Mensagens Esperadas

| Situação                                                   | Mensagem                                                                                    |
|------------------------------------------------------------|---------------------------------------------------------------------------------------------|
| Campo nome vazio                                           | "Nome de Responsável é obrigatório."                                                        |
| Tentativa de excluir tipo vinculado a fichas ou pessoas    | "Não é possível excluir: este tipo de responsável está vinculado a fichas ou pessoas."      |
| Cadastro realizado com sucesso                             | "Tipo de responsável cadastrado com sucesso!"                                               |
