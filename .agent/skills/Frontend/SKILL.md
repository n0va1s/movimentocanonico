---
name: desenvolvimento-frontend
description: Guia sênior de desenvolvimento frontend no ecossistema Laravel, focado em Acessibilidade (a11y), Carregamento Não Bloqueante, Mobile First, PWA e Componentes Livewire Flux.
---

# Skill: Desenvolvimento Frontend Sênior (PNSL-NTM)

Esta Skill define os padrões e as diretrizes inegociáveis para o desenvolvimento e manutenção das interfaces do usuário (UI/UX) do projeto PNSL-NTM. O objetivo é garantir interfaces extremamente rápidas, perfeitamente responsivas, totalmente acessíveis e alinhadas com as melhores práticas de Progressive Web Apps (PWA) e Componentes Flux.

---

## 1. Diretrizes de Arquitetura UI/UX

### A. Acessibilidade Máxima (a11y)
O sistema deve ser plenamente acessível a qualquer pessoa. Aplique rigorosamente:
1. **Semântica HTML:** Use tags nativas (`<header>`, `<main>`, `<nav>`, `<footer>`, `<article>`, `<section>`) em vez de empilhar `<div>`.
2. **Navegação via Teclado:** Todo componente interativo (botões, links, dropdowns) deve possuir estados de `:focus-visible` claros e permitir navegação fluida via `Tab`.
3. **Atributos ARIA:** Insira `aria-label` em botões de ícone sem texto, `aria-expanded` em menus retráteis e `aria-live="polite"` em componentes assíncronos/Livewire de atualização em tempo real.
4. **Contraste e Cores:** Mantenha contraste WCAG AA mínimo (4.5:1 para texto normal). Não use cores como única pista visual (adicione badges ou ícones de texto auxiliares).

### B. Carregamento Não Bloqueante e Leveza (Zero Render-Blocking)
Para garantir velocidade instantânea e manter a UI interativa no primeiro segundo:
1. ** Scripts Assecundários:** Evite bloquear o carregamento do DOM. Utilize a diretiva `@vite` e garanta que scripts externos (quando estritamente necessários) usem `defer` ou `async`.
2. **CSS Crítico:** Priorize estilizações inline leves ou o Tailwind compilado de forma enxuta. Evite injetar frameworks CSS secundários pesados no Blade.
3. **SVG Otimizado:** Nunca utilize imagens rasterizadas (PNG/JPG) para ícones. Use exclusivamente elementos `<svg>` puros inline ou os componentes integrados `<flux:icon:[nome] />` que carregam apenas o vetor necessário.
4. **Lazy Loading:** Imagens de perfil ou fotos de eventos devem carregar sob demanda usando `<img loading="lazy" ...>`.

### C. Design Mobile First (Responsividade Fluida)
Desenhe incondicionalmente pensando em telas pequenas primeiro. O layout desktop é uma expansão do fluxo mobile.
1. **Media Queries Progressivas:** No CSS ou classes Tailwind, defina as regras base para telas pequenas e vá escalando usando prefixos como `sm:`, `md:`, `lg:` e `xl:`.
2. **Touch Targets Seguros:** Todo elemento clicável em telas mobile deve possuir uma área mínima de clique de **48px x 48px** para evitar toques acidentais (use padding ou flex gap).
3. **Layout Flexível:** Dê preferência a CSS Flexbox e Grid (`grid-cols-1 md:grid-cols-2`) para que os formulários e listagens se adaptem naturalmente à largura do celular.

### D. Melhores Práticas de PWA (Offline & Instalação)
A aplicação deve ser rápida e utilizável em condições ruins de rede:
1. **Manifesto PWA:** Mantenha o `manifest.json` com caminhos corretos de ícones, cor de fundo harmônica e tipo de exibição `standalone`.
2. **Estratégias de Cache:** Configure service workers para cachear fontes (Google Fonts), ícones SVG estáticos e layouts críticos do app shell.
3. **Evitar Bloqueios no Boot:** Não exiba telas pesadas de "Splash Screen" com scripts de renderização síncronos; exiba apenas esqueletos inline ultra leves (Skeletons).

---

## 2. Utilização de Componentes Flux (Livewire 3)

O projeto usa a biblioteca premium de componentes **Livewire Flux** (`livewire/flux`). Você **deve** priorizar o uso desses componentes integrados, pois eles já vêm otimizados, acessíveis e compatíveis com Dark Mode nativo.

> [!IMPORTANT]
> Use os componentes Flux sempre que possível para novos inputs, modais e layouts, mas **sempre respeitando e harmonizando com os estilos e as cores existentes nas telas**.

### Exemplos de Transposição de HTML Nativo para Flux

#### Inputs e Textareas
```html
<!-- Ruim (Sem acessibilidade integrada e design ad-hoc) -->
<label class="text-xs">Observações</label>
<textarea name="txt_observacao" class="border border-gray-300 rounded p-2"></textarea>

<!-- Excelente (Componente Flux leve e acessível) -->
<flux:textarea 
    name="txt_observacao" 
    label="Observações" 
    placeholder="Informe observações relevantes..." 
    rows="3" 
/>
```

#### Botões com Ícones
```html
<!-- Ruim -->
<button class="bg-blue-500 hover:bg-blue-600 text-white rounded px-4 py-2 flex items-center">
    <svg ...></svg> Excluir
</button>

<!-- Excelente (Flux gerencia o tamanho do toque e o padding do ícone automaticamente) -->
<flux:button variant="danger" icon="trash" type="submit">
    Excluir
</flux:button>
```

#### Badges e Status
```html
<!-- Ruim -->
<span class="text-green-700 bg-green-100 rounded px-2 py-1 text-xs font-bold">Aprovado</span>

<!-- Excelente -->
<flux:badge color="green" icon="check-circle">Aprovado</flux:badge>
```

---

## 3. Checklist de Validação Frontend
Antes de salvar qualquer modificação Blade, revise os seguintes tópicos:
- [ ] **Acessibilidade:** Elementos interativos estão focáveis? Existem `aria-label` para ícones sozinhos?
- [ ] **Mobile First:** A tela encolhe para 320px de largura sem scroll horizontal e as áreas de toque têm pelo menos 48px?
- [ ] **Leveza:** Não foram adicionadas bibliotecas JS grandes (preferindo JS puro, Alpine.js ou Livewire nativo)?
- [ ] **Flux:** Elementos genéricos de formulários foram substituídos pelos respectivos `<flux:input>`, `<flux:select>`, `<flux:checkbox>` ou `<flux:radio>`?
- [ ] **PWA:** Recursos estáticos estão com tags corretas para cache de rede e imagens de fotos usam lazy load?
