# PRD — PNSL-NTM
## Product Requirements Document

**Produto:** Sistema de Gestão de Encontros — Paróquia Nossa Senhora do Lago  
**Versão:** 1.0  
**Data:** Maio de 2026  
**Responsáveis:** João Paulo Novais · Gabriel Carvalho · Thales  

---

## 1. Visão Geral

O **movimento** é um sistema web para gestão dos encontros de religiosos para paróquias. O sistema centraliza o ciclo de vida completo de três movimentos pastorais:

| Movimento | Público-alvo |
|-----------|-------------|
| **VEM** — Encontro de Adolescentes com Cristo | Jovens |
| **ECC** — Encontro de Casais com Cristo | Casais |
| **SGM** — Segue-Me | Jovens adultos |

O sistema substitui planilhas e processos manuais, oferecendo inscrição digital, gestão de equipes, de eventos, controle de presença, prestação de contas e um sistema de gamificação chamado **Aura**.

---

## 2. Objetivos do Produto

| Objetivo | Métrica de sucesso |
|----------|--------------------|
| Digitalizar o processo de inscrição nos encontros | 100% das fichas cadastradas pelo sistema |
| Reduzir o tempo de aprovação de fichas | Aprovação em 1 clique com criação automática de participante |
| Centralizar a gestão operacional de cada evento | Todas as abas do gerenciamento acessíveis em uma única tela |
| Engajar membros com histórico de participação | Cada membro visualiza sua Aura e linha do tempo |
| Registrar a prestação de contas financeira | Relatório financeiro preenchido para 100% dos eventos encerrados |

---

## 3. Usuários e Perfis de Acesso

O sistema possui quatro perfis com permissões progressivas:

| Perfil | Identificador | Descrição |
|--------|--------------|-----------|
| Usuário | `user` | Membro da comunidade. Pode se inscrever em fichas e como trabalhador, editar seus próprios dados e visualizar eventos. |
| Coordenador | `coord` | Acesso operacional. Gerencia eventos em que é coordenador (`ind_coordenador = true`). Visualiza trabalhadores e confirma montagem de equipe. |
| Especialista | `espec` | Acesso ampliado ao gerenciamento de eventos em que é trabalhador. Visualiza fichas, voluntários e prestação de contas. |
| Administrador | `admin` | Acesso total. CRUD completo de todos os recursos, configurações do sistema e gerenciamento de perfis. |

> Detalhamento completo em [perfis-de-acesso.md](perfis-de-acesso.md).

---

## 4. Funcionalidades

### 4.1 Página Pública

Acessível sem autenticação.

- Apresentação da paróquia e dos movimentos
- Lista dos próximos eventos
- Formulário de contato (notificação automática via Telegram ao admin)
- FAQ e termos de compromisso (VEM e SGM)

---

### 4.2 Autenticação e Cadastro

- Registro e login via Laravel Breeze
- Ao criar uma **Pessoa** com e-mail e data de nascimento, um **User** é criado automaticamente com senha = data de nascimento no formato `AAAAMMDD`
- E-mail de boas-vindas enviado automaticamente com as credenciais de acesso
- Suporte a dark mode e preferências de aparência

---

### 4.3 Fichas de Inscrição

Cada movimento possui um formulário especializado. A ficha base (`ficha`) armazena os dados comuns; tabelas especializadas guardam os dados específicos de cada tipo.

#### Ficha VEM
- Dados pessoais do candidato (nome, apelido, nascimento, gênero, e-mail, telefone, endereço, camiseta)
- Dados dos responsáveis (pai, mãe ou outro tipo pré-cadastrado)
- Informações complementares (onde estuda, com quem mora)
- Restrições alimentares (exibição condicional)
- Formulário bloqueado para edição após aprovação

#### Ficha ECC
- Dados do candidato e do cônjuge (incluindo CPF, camiseta, nascimento)
- Gerenciamento de filhos (nome, CPF, nascimento, e-mail, telefone)
- Restrições alimentares do casal
- Ao aprovar: cria automaticamente Pessoa para o candidato, cônjuge e cada filho com CPF informado

#### Ficha SGM
- Dados pessoais com campos de escolaridade, situação escolar e religião (via Enums)
- Foto e CPF obrigatórios
- Perguntas frequentes do Segue-Me 2026 integradas ao formulário

#### Fluxo de aprovação (comum a todos os tipos)
1. Coordenação revisa a ficha
2. Clique em "Aprovar" inverte `ind_aprovado`
3. Sistema cria automaticamente `Pessoa` (e `User` se não existir), `PessoaSaude` e `Participante`
4. Foto da ficha é movida para a pasta da pessoa
5. Clique em "Desaprovar" remove os participantes vinculados (reversível)

---

### 4.4 Gerenciamento de Evento

Tela central de operação de um evento, acessível via `/eventos/{evento}/gerenciamento` com abas independentes.

| Aba | Perfis | Descrição |
|-----|--------|-----------|
| Resumo | coord*, espec*, admin | Totalizadores (fichas, participantes, voluntários, trabalhadores) e status da prestação de contas |
| Fichas | espec*, admin | Listagem e aprovação de fichas VEM, ECC e SGM |
| Participantes | coord*, espec*, admin | Lista de participantes confirmados com cor de troca |
| Voluntários | espec*, admin | Candidatos a trabalhar no evento |
| Trabalhadores | coord*, espec*, admin | Equipes confirmadas com indicação de coordenador |
| Presença | coord*, espec*, admin | Controle de presença com toggle por pessoa, barra de progresso e filtro por tipo |
| Quadrante | coord*, espec*, admin | Visão impressa de participantes (por cor de troca) e trabalhadores (por equipe) |
| Crachás | coord*, espec*, admin | Geração e impressão de crachás |
| Prestação de Contas | espec*, admin | Registro financeiro do evento |

> `*` Restrito aos eventos em que o usuário está cadastrado como trabalhador.

---

### 4.5 Geração de Crachás

A aba **Crachás** gera automaticamente um crachá para cada participante e trabalhador do evento.

**Layout do crachá (8,6cm × 5,4cm):**
- Lateral esquerda: logo do evento (em escala de cinza)
- Badge colorido com o grupo (cor de troca para participantes / nome da equipe para trabalhadores)
- Nome de apelido em destaque (nome completo abaixo, se diferente)
- Rodapé: ícones e labels das restrições alimentares em vermelho

**Cores por faixa de troca:**

| Cor | Hex |
|-----|-----|
| Azul | `#3b82f6` |
| Verde | `#22c55e` |
| Vermelha | `#ef4444` |
| Amarela | `#eab308` |
| Laranja | `#f97316` |
| Padrão (sem cor) | `#a1a1aa` |

**Impressão:**
- Botão "Imprimir Tudo" aciona `window.print()`
- Layout de impressão: grade A4 com 2 crachás por linha, margem de 1cm
- Cores preservadas na impressão via `print-color-adjust: exact`
- Elementos de navegação ocultados automaticamente

---

### 4.6 Controle de Presença

- Lista unificada de participantes e trabalhadores do evento
- Toggle individual por pessoa (atualização em tempo real via Livewire)
- Barra de progresso com percentual de presença confirmada
- Filtro por tipo (todos / participantes / trabalhadores)
- Busca por nome ou apelido
- Indicação visual de menores de idade (nascimento < 18 anos)

---

### 4.7 Quadrante

Documento imprimível com a composição completa do evento:

- Cabeçalho: foto oficial, nome, número, tipo, datas e totalizadores
- Seção de participantes agrupados por cor de troca
- Seção de trabalhadores agrupados por equipe (coordenadores destacados com estrela)
- Otimizado para impressão A4 com quebra de página entre seções

---

### 4.8 Prestação de Contas Financeira

Registrada na aba **Prestação de Contas** e exibida no **Resumo** do evento.

**Campos:**
| Campo | Tipo | Descrição |
|-------|------|-----------|
| Receita (`val_investimento`) | Decimal | Total de entradas (inscrições, doações, etc.) |
| Despesa (`val_saldo`) | Decimal | Total de saídas (alimentação, material, etc.) |
| Relatório (`txt_relatorio`) | Texto livre | Observações para eventos futuros (ex: "X kg de carne comprados, X sobraram") |

**Saldo** é calculado em tempo real: `Receita − Despesa`.

**Status no Resumo:**
- Badge **"Realizada"** (verde) quando `txt_relatorio` está preenchido
- Badge **"Pendente"** (amarelo) quando ainda não foi registrada

---

### 4.9 Trabalhadores e Voluntários

**Candidatura (Voluntário):**
- Usuário se candidata a 1–3 equipes de interesse
- Campo de habilidade obrigatório por equipe (mínimo 5 caracteres, sem repetição de caracteres)
- Não permite candidatura duplicada para a mesma equipe no mesmo evento
- Dados salvos em `voluntario`

**Confirmação (Trabalhador):**
- Coordenação seleciona o voluntário, define a equipe e indica se é coordenador
- Cria registro em `trabalhador` e vincula todos os voluntários da pessoa ao trabalhador
- Campo `ind_primeira_vez` registra se é a primeira vez que a pessoa trabalha

---

### 4.10 Aura (Gamificação)

O sistema **Aura** é o mecanismo de gamificação que registra e exibe o histórico de participação de cada membro.

#### Modelo de dados

| Tabela | Campo | Descrição |
|--------|-------|-----------|
| `gamificacao` | `idt_pessoa` | Pessoa que recebeu os pontos |
| `gamificacao` | `qtd_pontos` | Quantidade de pontos do registro |
| `gamificacao` | `des_motivo` | Descrição do motivo (ex: "Participante VEM XXX") |
| `gamificacao` | `origem_id` + `origem_type` | Polimórfico: pode vir de `Trabalhador`, `Participante` ou outra fonte futura |
| `pessoa` | `qtd_pontos_total` | Total acumulado (atualizado automaticamente pelo Observer) |

#### Regras de pontuação (Observer)

O `GamificacaoObserver` mantém o total sincronizado automaticamente:

- **Ao criar** um registro em `gamificacao`: incrementa `qtd_pontos_total` na `pessoa`
- **Ao deletar** um registro em `gamificacao`: decrementa `qtd_pontos_total` na `pessoa`

#### Cálculo de nível

```
Nível = floor(qtd_pontos_total / 1000) + 1
```

Exemplos:
- 0–999 pontos → Nível 1
- 1000–1999 pontos → Nível 2
- 2000–2999 pontos → Nível 3

#### Progresso para o próximo nível

```
Progresso (%) = (qtd_pontos_total % 1000) / 10
```

#### Tela Aura (Linha do Tempo)

Acessível em `/timeline` por qualquer usuário autenticado.

**Cabeçalho:**
- Avatar com borda gradiente dourada
- Badge de nível (`Lv. N`) sobreposto ao avatar
- Nome de apelido e classe (`tip_habilidade`)
- Tag de estado civil: "Desbravador" (solteiro) ou "Comunidade" (casado)
- Card de **Total XP** com pontuação formatada
- Card de **Ranking** com posição entre todos os membros (`#N`)

**Linha do tempo:**
- Eventos agrupados por ano
- Cada card exibe: data, movimento (badge), nome do evento, tipo (Participante / Trabalhador)
- Para trabalhadores: nome da equipe e indicação de liderança (coordenador)

**Painel lateral:**
- Total de eventos participados
- Barra de progresso para o próximo nível
- Botão de acesso à listagem de eventos

---

### 4.11 Dashboard

Painel principal pós-login com:
- Totalizadores: eventos ativos, total de fichas, participantes e trabalhadores
- Lista dos próximos eventos com data e movimento
- Inscrições recentes (últimas fichas cadastradas)

---

### 4.12 Aniversariantes

- Listagem de membros aniversariantes
- Envio automático de e-mail de aniversário via comando agendado (`EnviarEmailAniversario`)

---

### 4.13 Configurações do Sistema (admin)

| Recurso | Descrição |
|---------|-----------|
| Perfis de usuário | Atribuição de perfil (`user`, `coord`, `espec`, `admin`) a qualquer usuário |
| Tipos de equipe | CRUD das equipes disponíveis nos eventos |
| Tipos de movimento | CRUD dos movimentos (VEM, ECC, SGM) com sigla e data de início |
| Tipos de responsável | CRUD dos tipos de responsável usados nas fichas VEM |
| Tipos de restrição | CRUD das restrições alimentares disponíveis nas fichas |

---

## 5. Notificações

| Evento | Canal | Destinatário |
|--------|-------|-------------|
| Novo contato pela página pública | Telegram | Admin |
| Exceção não tratada no sistema | Telegram | Admin |
| Cadastro de novo usuário | E-mail (boas-vindas) | Novo usuário |
| Aniversário | E-mail | Aniversariante |

---

## 6. Stack Tecnológica

| Camada | Tecnologia |
|--------|-----------|
| Backend | PHP 8.3 + Laravel 12 |
| Frontend | Livewire Flux + Volt + Blade + Tailwind CSS |
| Banco de dados | MySQL 8 |
| Cache e sessões | Redis 7 |
| Servidor web | Nginx |
| Testes | PestPHP |
| Formatação de código | Laravel Pint |
| Notificações | Telegram Bot API (`laravel-notification-channels/telegram`) |
| E-mail (dev) | Mailtrap |
| Deploy | GitHub Actions → FTP → Locaweb |
| Ambiente de desenvolvimento | Docker Compose |

---

## 7. Arquitetura

A aplicação segue o padrão MVC do Laravel com camadas de serviço para lógica de negócio complexa:

```
Nginx → PHP-FPM (Laravel) → MySQL
                          → Banco (cache, sessões, filas)
```

- **Controllers** — recebem requisições HTTP e delegam para Services
- **Services** — `FichaService`, `VoluntarioService`, `EventoService`, `ArquivoService`, `UserService`
- **Livewire Volt** — componentes reativos para o gerenciamento de eventos
- **Observers** — `GamificacaoObserver` mantém `qtd_pontos_total` sincronizado
- **Enums PHP nativos** — todos os campos de domínio fixo (gênero, estado civil, camiseta, etc.)
- **SoftDeletes** — exclusão lógica em `Pessoa`, `PessoaFoto`, `PessoaSaude`, `Trabalhador`, `Ficha`
- **Gates** — autorização centralizada por perfil no `AppServiceProvider`

> Detalhamento em [arquitetura.md](arquitetura.md).

---

## 8. Padrões de Dados

O banco segue convenção de prefixos em todas as colunas:

| Prefixo | Tipo | Exemplo |
|---------|------|---------|
| `idt_` | Identificadores (PK/FK) | `idt_pessoa`, `idt_evento` |
| `nom_` | Nomes próprios | `nom_pessoa`, `nom_apelido` |
| `des_` | Descrições curtas | `des_endereco`, `des_evento` |
| `txt_` | Textos longos | `txt_observacao`, `txt_relatorio` |
| `ind_` | Booleanos | `ind_aprovado`, `ind_restricao` |
| `dat_` | Datas | `dat_nascimento`, `dat_inicio` |
| `tip_` | Tipos/enums | `tip_evento`, `tip_genero` |
| `val_` | Valores financeiros | `val_investimento`, `val_despesa` |
| `qtd_` | Quantidades | `qtd_pontos_total`, `qtd_vaga` |
| `num_` | Números não somáveis | `num_cpf_pessoa`, `num_evento` |
| `eml_` | E-mails | `eml_pessoa`, `eml_candidato` |
| `tel_` | Telefones | `tel_pessoa`, `tel_conjuge` |
| `tam_` | Tamanhos físicos | `tam_camiseta` |
| `med_` | Arquivos/mídia | `med_foto`, `med_logo` |
| `usu_` | Auditoria de usuário | `usu_inclusao`, `usu_alteracao` |

> Detalhamento em [padroes_banco_dados.md](padroes_banco_dados.md).

---

## 9. Itens em Aberto / Backlog

| Item | Prioridade | Observação |
|------|-----------|------------|
| Persistência da prestação de contas | Alta | `saveFinanceiro()` tem `TODO` — campos existem no model, falta salvar do formulário |
| Regras de pontuação da Aura | Alta | Estrutura pronta, valores por ação (participante, trabalhador, coordenador) não definidos |
| Ficha SGM — aprovação automática | Média | Fluxo de aprovação implementado para VEM e ECC; SGM precisa de validação |
| Ranking da Aura | Média | Campo `posicaoNoRanking` exibido na tela, lógica de cálculo não implementada |
| Integração de pagamentos | Baixa | Campos `val_camiseta`, `val_trabalhador`, `val_venista` existem no model `Evento` |
| Migração para hospedagem com suporte a containers | Baixa | Ambiente Docker pronto; produção ainda usa Locaweb via FTP |
| Hook de pre-commit com Laravel Pint | Baixa | Pint configurado, mas sem automação no commit |

---

