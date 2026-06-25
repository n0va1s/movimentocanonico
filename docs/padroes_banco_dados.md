# Padrões de Modelagem e Banco de Dados

Este documento define os padrões adotados para a criação de tabelas, colunas, migrations e modelagem de dados. O objetivo é fornecer contexto e especificação para desenvolvedores e agentes de IA que trabalham na manutenção e evolução da aplicação.

## 1. Alinhamento das Tabelas com o Negócio

A modelagem reflete diretamente os termos e regras de negócio. Em vez de utilizar termos puramente técnicos, utilizamos a linguagem onipresente do negócio, facilitando a comunicação entre a equipe de desenvolvimento e os usuários e regras de negócio.

**Exemplos:**
- `evento`: Representa um encontro específico que está sendo organizado (ex: "XXX VEM").
- `participante`: Representa a pessoa que está fazendo/participando do encontro pela primeira vez (também conhecido como encontrista/venista).
- `trabalhador`: Representa a pessoa que está servindo/trabalhando em alguma equipe no encontro.
- `voluntario`: Representa a intenção ou cadastro de uma pessoa disposta a trabalhar em equipes de futuros eventos.

## 2. Nomenclatura das Tabelas Relacionais e Auxiliares

Tabelas auxiliares, de detalhamento ou de relacionamento (como N:N) devem sempre iniciar com o nome da **tabela principal** à qual estão associadas. Isso garante que as tabelas fiquem agrupadas logicamente quando listadas em ordem alfabética no banco de dados e facilita a identificação imediata de sua dependência.

**Exemplos:**
- `evento_foto`: Imagens e logos associadas/dependentes do `evento`.
- `pessoa_saude`: Informações médicas, remédios e restrições dependentes da entidade `pessoa`.
- `pessoa_foto`: Fotos anexadas diretamente à `pessoa`.
- `ficha_ecc`: Detalhes específicos de uma `ficha` de inscrição aplicados restritamente ao ECC.
- `ficha_saude`: Restrições temporárias preenchidas durante a submissão de uma `ficha`.

## 3. Padrões de Nomenclatura de Campos (Prefixos)

Todas as colunas das tabelas do projeto seguem rígidos padrões de prefixos. São utilizadas **três letras seguidas de underline (`_`)**, que indicam o tipo semântico e a função da informação esperada para aquela coluna. Esse padrão deve ser respeitado em novas implementações para evitar ambiguidades e facilitar o mapeamento de ORM.

Abaixo estão explicados cada um dos padrões com seus respectivos exemplos de uso reais encontrados na base:

### `idt_` (Identificadores PK / FK)
Usado para chaves primárias (PK) e relacionamentos com chaves estrangeiras (FK).
- `idt_pessoa`: Identificador único da tabela pessoa.
- `idt_evento`: Identificador único da tabela evento.
- `idt_movimento`: Chave estrangeira que identifica ao qual tipo de movimento determinado cadastro pertence.

### `nom_` (Nomes Próprios ou Títulos)
Usado para strings de texto curto ou médio referentes a nomes próprios, títulos ou identificações.
- `nom_pessoa`: Nome completo de uma pessoa.
- `nom_movimento`: Nome nominal descritivo do movimento (ECC, Segue-me).
- `nom_apelido`: Sobrenome curto, apelido, ou "como a pessoa gostaria de ser chamada".

### `des_` (Descrições)
Usado para textos descritivos curtos de lugares, propriedades de relacionamento e afins.
- `des_endereco`: Endereço completo digitado para a residência.
- `des_mora_quem`: Campo em ficha indicando com quem o inscrito mora.
- `des_tipo_responsavel`: Descritivo de um tipo de responsabilidade.

### `txt_` (Textos Longos / Observações)
Usado para colunas do tipo `TEXT`, reservado para observações textuais amplas, detalhamentos ou informações adicionais.
- `txt_observacao`: Anotações ou considerações abertas gerais em uma Ficha de inscrição.
- `txt_informacao`: Espaço para orientações detalhadas de um tipo de evento ou configuração.
- `txt_complemento`: Complementos explicativos sobre uma restrição alimentar ou tipo de remédio informado em ficha de saúde.

### `ind_` (Indicadores Lógicos / Booleanos)
Usado para valores tipificados de lógica `boolean` (Verdadeiro ou Falso / 0 ou 1 / Checkboxes). O sufixo deve sempre ser legível como uma afirmação ou condição.
- `ind_restricao`: Flag indicando que o candidato TEM alguma restrição.
- `ind_consentimento`: Flag indicando que ocorreu a manifestação do consentimento dos termos lgpd/autorizações.
- `ind_catolico`: Flag que valida com True ou False se um familiar/cônjuge é devidamente batizado na religião.

### `dat_` (Datas)
Usado para campos atrelados ao tipo de `date` ou `datetime`.
- `dat_nascimento`: Data de aniversário de um registro.
- `dat_inicio`: Data referencial em que determinado encontro ou equipe começa a atuar.
- `dat_casamento`: Data onde o matrimônio foi estabelecido, recolhida com os cônjuges.

### `tip_` (Tipos e Classificações em Chars)
Usado para colunas que definem "enums virtuais", com caracteres pequenos (1 a 5 posições) usados estritamente para classificar qual status as informações possuem.
- `tip_escolaridade`: Letra identificadora da escolaridade preenchido na inscrição (ex: F - Fundamental, M - Médio, S - Superior, P - Pós-Graduação).
- `tip_evento`: Sigla discriminadora no modelo "Polimórfico" de Eventos categorizando como 'E'- Encontro, 'P'- Pós.
- `tip_estado_civil`: Caractere alocando o estado civil, como Solteiro, Casado, etc.

### `val_` (Valores Financeiros / R$)
Usado em colunas contábeis ou de custos em que seu tipo no SGBD é `decimal(8,2)`.
- `val_camiseta`: Preço fixo referencial para a aquisição da vestimenta extra do evento.
- `val_trabalhador`: Montante ou valor de taxa monetária devida por quem vai atuar trabalhando num evento respectivo.
- `val_receita`: Saldo somado de caixas ou depósitos em tesouraria atrelado ao próprio evento.

### `qtd_` (Quantidades Numerárias)
Usado para armazenar valores de contagem `integer`, quantificadores não decimais ou saldo escalar matemático puro.
- `qtd_filhos`: Número exato de quantos dependentes a estrutura conjugal do ECC tem atrelados a seu registro.
- `qtd_vaga`: Fator de controle ou limite técnico total de lugares abertos nas rotinas em que um Evento puder suportar.
- `qtd_pontos`: Base para incremento/decremento atrelado a moedas de gamificação sobre atitudes ou participações de pessoas.

### `num_` (Números ou Identidades Não Somáveis)
Usado para serializar números atrelados a documentos formais ou edições em strings. Não sofrem operações matemáticas ou aritméticas.
- `num_cpf_conjuge`: Texto estrito validando e acomodando o padrão de formação de Cadastro da Pessoa Física para casais.
- `num_cpf_candidato`: Campo validador individual inserido pelo futuro Encontrista.
- `num_evento`: Designador cronológico numérico ordinal, geralmente romano ou serial, dos eventos gerados, "XXX" etc.

### `eml_` (Correio Eletrônico / Email)
Reserva para validação textual atrelada a contatos diretos utilizando correio eletrônico.
- `eml_pessoa`: Email padrão homologado final que irá pertencer à entidade unificada.
- `eml_candidato`: Email temporário digitado unicamente em contextos da ficha a ser conferido.
- `eml_responsavel`: O correio eletrônico para notificações diretas a tutores legais na adesão dos infantes.

### `tel_` (Telefones)
Agrupa o armazenamento string de naturezas voltadas a comunicação por telefonia ou mensageria instantânea com seus respectivos códigos de zona DDD, se existirem na tela de manipulação.
- `tel_pessoa`: Informação de linha móvel principal em nome da Pessoa registrada.
- `tel_candidato`: Inserção original preenchida no ato do submeter formulário próprio para o pretenso encontro.
- `tel_conjuge`: Linha do companheiro e par romântico num casal contida em instâncias casuais como no caso do ECC.

### `tam_` (Dimensões de Tamanhos Físicos)
Reserva exclusiva para formatação e seleção de opções relativas ao tamanho físico (largura, comprimento) para itens como roupas ou produtos manufaturáveis em tamanhos (P, M, G, GG).
- `tam_camiseta`: Campo com opção preenchida com letras pelo voluntariante na etapa VEM.
- `tam_camiseta_conjuge`: Dimensão atrelada somente ao vestimenta para parceiros inserida via Ficha de origem de movimento.

### `med_` (Mídias e Arquivos em Storage)
Usado para strings que refletem os nomes ou *paths* dos arquivos binários processados para o ecossistema e disco (storages do framework), não armazenando os *blobs* integralmente.
- `med_foto`: Expressa com precisão a extensão local, identificador e sub-pastas atribuídas aos perfis da Base via Uploader de Avatar.
- `med_logo`: URL base relativa voltada exibição de *Assets* e publicidade de marketing do respectivo *Cover* em Evento listado.
- `med_conjuge`: Imagem ou sub-registro individual, podendo abrigar fotos parciais daquele que compõe relacionamentos para cadastramento ECC.

### `usu_` (Auditoria de Usuários Logados)
Para *tracking*, rastro log ou identificação pontual da chave cruzada para com usuários `users` que executam transações diretas em alguma estrutura dependente.
- `usu_inclusao`: ID referencial fixado indicando permanentemente "Quem deu entrada neste dado pela 1ª vez".
- `usu_alteracao`: ID que muda mediante todo commit subsequente na referida Ficha registrando sua mutabilidade contínua.
