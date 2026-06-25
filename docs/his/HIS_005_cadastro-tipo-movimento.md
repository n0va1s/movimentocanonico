# HIS-005: Cadastro de Tipo de Movimento

**Como** administrador do sistema,
**quero** cadastrar, editar e excluir os tipos de movimentos (ex: VEM, ECC, Segue-Me),
**para que** os eventos possam ser corretamente classificados e organizados por movimento.

---

## Critérios de Aceitação

- ✅ O sistema deve permitir o preenchimento do nome do movimento (obrigatório).
- ✅ O sistema deve permitir o preenchimento da sigla do movimento (obrigatório).
- ✅ O sistema deve permitir informar a data de início do movimento (obrigatório).
- ✅ O formulário deve exibir mensagens de erro caso campos obrigatórios estejam vazios ou inválidos.
- ✅ O tipo de movimento deve ser salvo corretamente no banco de dados com todos os dados informados.
- ✅ Ao cadastrar com sucesso, o sistema deve redirecionar para a listagem com uma mensagem de confirmação.
- ✅ Deve haver a possibilidade de cancelar o cadastro e retornar à listagem.
- ✅ O layout do formulário deve ser responsivo e seguir os padrões visuais do sistema (Tailwind CSS).
- ✅ A exclusão de um tipo de movimento só deve ser permitida se ele não estiver associado a nenhum evento.

---

## Campos do Formulário

| Campo             | Tipo  | Obrigatório | Regras                                |
|-------------------|-------|-------------|---------------------------------------|
| Nome do Movimento | Texto | Sim         | Máximo de 255 caracteres              |
| Sigla             | Texto | Sim         | Máximo de 10 caracteres               |
| Data de Início    | Data  | Sim         | Data válida no formato AAAA-MM-DD     |

---

## Mensagens Esperadas

| Situação                                          | Mensagem                                                                        |
|---------------------------------------------------|---------------------------------------------------------------------------------|
| Cadastro realizado com sucesso                    | "Tipo de movimento cadastrado com sucesso!"                                     |
| Campo nome vazio                                  | "O nome do movimento é obrigatório."                                            |
| Campo sigla vazio                                 | "A sigla do movimento é obrigatória."                                           |
| Campo data de início vazio                        | "A data de início é obrigatória."                                               |
| Tentativa de excluir movimento vinculado a evento | "Não é possível excluir: este tipo de movimento está vinculado a eventos."      |
