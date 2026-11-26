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
- `$motion-design` para planejar ritmo e transições. Use `$gsap-core`, `$gsap-timeline` e `$gsap-performance` para implementar e revisar animações, e `$three-js` quando a tela precisar de uma cena 3D.
- `$figma-create-design-system-rules` para registrar as convenções do projeto em um design system. `$figma-implement-design` usa o link de um frame e os dados do Figma para implementar e conferir a interface.
- `$figma-generate-design` com `$figma-use` para explorar wireframes e variantes em um arquivo do Figma. Use `$figma-create-new-file` se precisar de um arquivo novo.

Ao trazer uma referência para o sistema, procure os componentes Blade/Livewire em `resources/views` e os estilos em `resources/css/app.css`. Reutilize os tokens e componentes existentes, depois verifique a tela no navegador em larguras de celular e desktop e nos dois temas.

## Motion, ícones e som

Use `npm ci` para instalar as dependências registradas no projeto. Importe as bibliotecas no módulo da tela que as utilizar, com carregamento sob demanda quando fizer sentido.

- **GSAP** (`gsap`) para transições e microinterações. Respeite `prefers-reduced-motion` e limpe as animações ao desmontar o componente. [Documentação do GSAP](https://gsap.com/docs/v3/)
- **Three.js** (`three`) para cenas 3D, com uma alternativa estática quando a cena for decorativa ou o navegador não tiver suporte. [Documentação do Three.js](https://threejs.org/docs/)
- **Morphicons** (`morphicons`) para transformar ícones SVG de traço. Use a API JavaScript no Blade/Livewire e preserve o rótulo acessível do botão durante a transição. [Documentação do Morphicons](https://www.morphicons.com/docs)
- **UISFX** (`uisfx`) para sons de ações. Deixe o som desligado por padrão e mantenha feedback visual equivalente. [Documentação do UISFX](https://uisfx.com/)

## Direções visuais para protótipos

- **Dark minimalista:** fundos entre `#0A0A0A` e `#121212`, divisores discretos e fonte mono reservada para dados. Dê mais contraste ao texto e ao foco de teclado.
- **Neo-brutalismo editorial:** contornos sólidos, sombras rígidas e tipografia de destaque para os títulos. Use o mesmo tratamento nas ações equivalentes.
- **Bento assimétrico:** varie a área dos módulos conforme a prioridade do conteúdo. Preserve a ordem de leitura e de tabulação quando o grid passar para uma coluna.

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
