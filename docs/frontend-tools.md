# Ferramentas de desenvolvimento do front-end

Na raiz do repositório, execute o instalador com Git disponível. As revisões das ferramentas estão fixadas no script. Você pode executar novamente o comando para concluir uma instalação interrompida.

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File scripts/setup-frontend-tools.ps1
```

Abra uma nova sessão do Codex na raiz do projeto para carregar as skills de `.agents/skills`. Use `-Tools Skills` ao chamar o script se precisar apenas das skills.

## Como aplicar as skills

- `$accessibility-basic-check` para revisar contraste nos temas claro e escuro, foco visível, ordem de tabulação e rótulos. Confirme no navegador os estados de hover, foco, erro e desabilitado.
- `$css-layout-helper` para diagnosticar Flexbox e Grid e corrigir o menor trecho possível.
- `$impeccable` para crítica e refinamento da interface. `$design-taste-frontend` e `$frontend-design` ajudam a explorar direção visual e hierarquia. Selecione uma conforme o trabalho.
- `$ui-ux-pro-max` para consultar padrões de interface e comparar referências. As buscas locais da skill requerem Python 3.
- `$figma-create-design-system-rules` para registrar as convenções do projeto em um design system. `$figma-implement-design` usa o link de um frame e os dados do Figma para implementar e conferir a interface.

Ao trazer uma referência para o sistema, procure os componentes Blade/Livewire em `resources/views` e os estilos em `resources/css/app.css`. Reutilize os tokens e componentes existentes, depois verifique a tela no navegador em larguras de celular e desktop e nos dois temas.

## Figma

O endpoint MCP está em `.codex/config.toml`. Na raiz do projeto, autentique com `codex mcp login figma` e autorize no navegador usando uma conta com acesso ao arquivo de design. [Documentação do Figma MCP](https://developers.figma.com/docs/figma-mcp-server/remote-server-installation/)

## Penpot

Com Docker Compose disponível, inicie o ambiente de prototipagem e abra `http://localhost:9001`. O Mailpit do Penpot fica em `http://localhost:9025`.

```powershell
docker compose -p frontend-penpot -f .frontend-tools/penpot/compose.yaml up -d
```

Crie uma conta local e use o projeto de design para manter paleta, tipografia e componentes. [Guia de instalação do Penpot](https://help.penpot.dev/technical-guide/getting-started/)

## Screenshot to Code

Preencha o arquivo local `.frontend-tools/screenshot-to-code/.env` com pelo menos uma chave de provedor, como `OPENAI_API_KEY`, `ANTHROPIC_API_KEY` ou `GEMINI_API_KEY`. Use capturas autorizadas e sem dados pessoais, pois a imagem será enviada ao provedor configurado.

```powershell
docker compose -p frontend-screenshot --project-directory .frontend-tools/screenshot-to-code -f scripts/screenshot-to-code.compose.yaml up -d --build
```

Abra `http://localhost:5174`, envie a captura e selecione HTML + Tailwind para gerar um ponto de partida. Adapte os componentes e interações ao Blade/Livewire antes de incorporar o resultado. [Documentação do Screenshot to Code](https://github.com/abi/screenshot-to-code#-getting-started)
