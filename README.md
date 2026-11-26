# pnsl-ntm

## Sobre o Projeto
NTM significa "Não tenhais medo!", frase icônica do Papa São João Paulo II, frequentemente também destacada pelo Papa Francisco. Essa mensagem de coragem e fé inspira os encontros de jovens da Paróquia Nossa Senhora do Lago em Brasília-DF, sendo a base temática e motivacional deste sistema para gestão dessas informações.

## Responsáveis
- Gabriel Carvalho - oliveiracgabriel052@gmail.com  
- João Paulo Novais - jp.trabalho@gmail.com  
- Thales - contato pendente  

## Tecnologias Utilizadas
- **Laravel 12**: Framework backend PHP para o desenvolvimento da aplicação principal.  
- **MySQL**: Banco de dados relacional para armazenamento das informações.  
- **Mailtrap**: Serviço para envio de e-mails em ambiente de desenvolvimento.  
- **Cache em banco de dados**: Para otimizar desempenho armazenando dados temporários.  
- **Laravel UI**: Biblioteca frontend para interfaces de usuário baseadas em Laravel.  
- **Lovable**: Ferramenta de prototipação rápida para facilitar o design e o planejamento visual.  
- **GitHub**: Repositório centralizado de código fonte e controle de versões.  
- **Docker-Compose**: Gerenciador da infraestrutura de desenvolvimento, para facilitar o ambiente e treinamento da equipe.  

## Configuração do Ambiente
1. Clonar o repositório:  
   `git clone <URL-do-repositório>`  
2. Configurar o `.env` com dados do banco MySQL e Mailtrap.  
3. Rodar docker-compose para preparar o ambiente:  
   `docker-compose up -d`  
4. Executar as migrations e seeders do Laravel:  
   `php artisan migrate --seed`  
5. Iniciar o servidor de desenvolvimento Laravel:  
   `php artisan serve`  

## Desenvolvimento do front-end

Veja o [guia de ferramentas de front-end](docs/frontend-tools.md) para instalar as skills de design e acessibilidade e configurar Figma, Penpot e Screenshot to Code.

## Como Contribuir
- Forkar o repositório e criar branches para novas funcionalidades ou correções.  
- Seguir as boas práticas do Laravel e padrões já adotados. 
- Submeter Pull Requests detalhando as alterações feitas e testes realizados.  
- Participar das discussões e melhorias via issues no GitHub.

## Contato
Para dúvidas ou sugestões, use os e-mails dos responsáveis ou abra uma issue no GitHub.
 o sistema.
