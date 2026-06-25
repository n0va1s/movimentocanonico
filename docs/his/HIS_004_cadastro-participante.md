# HIS-004: Cadastro de Participante

**Como** administrador do sistema,
**quero** cadastrar, editar, listar e excluir participantes de um evento,
**para que** possamos organizar e acompanhar quem confirmou presença em cada encontro.

---

## Critérios de Aceitação

- ✅ Deve existir uma tabela no banco de dados com os campos necessários para identificar o participante e o evento relacionado.
- ✅ Deve ser possível criar um novo participante vinculado a um evento.
- ✅ Deve ser possível listar todos os participantes de um evento.
- ✅ Deve ser possível editar os dados de um participante existente.
- ✅ Deve ser possível excluir um participante (exclusão lógica via SoftDelete).
- ✅ O campo nome deve ser obrigatório e único por evento.
- ✅ Deve haver validação para evitar duplicidade de participantes no mesmo evento.
- ✅ Os participantes ativos devem ser exibidos nas listagens e relatórios do evento.
- ✅ O front-end deve ter uma interface amigável com formulário e tabela de listagem.
- ✅ Apenas usuários com permissão adequada (coordenação ou admin) podem gerenciar participantes.

---

## Campos do Formulário

| Campo    | Tipo   | Obrigatório | Regras                                    |
|----------|--------|-------------|-------------------------------------------|
| Nome     | Texto  | Sim         | Máximo de 255 caracteres, único por evento |
| Evento   | Select | Sim         | Lista de eventos disponíveis              |
| Ativo    | Flag   | Sim         | Valor booleano (padrão: ativo)            |

---

## Mensagens Esperadas

| Situação                                      | Mensagem                                                              |
|-----------------------------------------------|-----------------------------------------------------------------------|
| Cadastro realizado com sucesso                | "Participante cadastrado com sucesso!"                                |
| Campo nome vazio                              | "O nome do participante é obrigatório."                               |
| Participante já cadastrado no mesmo evento    | "Este participante já está cadastrado neste evento."                  |
| Exclusão realizada com sucesso                | "Participante removido com sucesso."                                  |
