---
name: desenvolvimento-frontend
description: Guia sênior de desenvolvimento frontend no ecossistema Laravel, focado em Acessibilidade (a11y), Carregamento Não Bloqueante, Mobile First, PWA e Componentes Livewire Flux.
---

# Skill: Desenvolvimento Frontend Sênior (PNSL-NTM)

Esta Skill define os padrões e as diretrizes inegociáveis para o desenvolvimento e manutenção das interfaces do usuário (UI/UX) do projeto PNSL-NTM. O objetivo é garantir interfaces extremamente rápidas, perfeitamente responsivas, totalmente acessíveis e alinhadas com as melhores práticas de Progressive Web Apps (PWA) e Componentes Flux.

> [!IMPORTANT]
> A **fonte de verdade** para identidade visual, paleta de cores, tipografia, tokens de design, jornadas de usuário e diretrizes completas de acessibilidade é o documento [DESIGN.md](file:///home/n0va1s/projects/movimentocanonico/docs/DESIGN.md). Esta Skill define os **princípios de implementação**; o DESIGN.md define **o quê** implementar.

---

## 1. Diretrizes de Arquitetura UI/UX

### A. Acessibilidade Máxima (a11y — WCAG 2.2 Nível AA)
O sistema deve ser plenamente acessível a qualquer pessoa. Aplique rigorosamente (referência completa em [DESIGN.md §7](file:///home/n0va1s/projects/movimentocanonico/docs/DESIGN.md)):
1. **Semântica HTML:** Use tags nativas (`<header>`, `<main>`, `<nav>`, `<footer>`, `<article>`, `<section>`) em vez de empilhar `<div>`. Um único `<h1>` por página, sem pular níveis de heading.
2. **Navegação via Teclado:** Todo componente interativo (botões, links, dropdowns) deve possuir estados de `:focus-visible` claros e permitir navegação fluida via `Tab`. Inclua skip link "Pular para conteúdo" em todas as páginas.
3. **Atributos ARIA:** Insira `aria-label` em botões de ícone sem texto, `aria-expanded` em menus retráteis, `aria-live="polite"` em componentes assíncronos/Livewire de atualização em tempo real, e `role="progressbar"` com `aria-valuenow` em barras de progresso.
4. **Contraste e Cores:** Mantenha contraste WCAG AA mínimo (4.5:1 para texto normal, 3:1 para texto grande e componentes UI). Não use cores como única pista visual (adicione badges ou ícones de texto auxiliares). Valide em **ambos os temas** (light e dark).
5. **Movimento Reduzido:** Respeite `prefers-reduced-motion` com regra CSS que desabilita animações. Nenhuma transição de UI deve durar mais que 300ms.
6. **Zoom e Redimensionamento:** Layout funcional em zoom até 200%. Nunca use `user-scalable=no` no viewport meta. Prefira `rem`/`em` sobre `px` fixo para fontes.

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

## 3. Tokens de Design (Referência Rápida)

Consulte [DESIGN.md](file:///home/n0va1s/projects/movimentocanonico/docs/DESIGN.md) para a lista completa. Referência rápida:

| Token | Valor | Tailwind |
|:------|:------|:---------|
| **Fonte** | Nunito (400, 500, 600, 700, 800) | `font-sans` |
| **Primary** | `#6366f1` | `indigo-500` |
| **Accent** | `#f472b6` | `pink-400` |
| **Tertiary** | `#06b6d4` | `cyan-500` |
| **Success** | `#10b981` | `emerald-500` |
| **Warning** | `#f59e0b` | `amber-500` |
| **Danger** | `#ef4444` | `red-500` |
| **Border Radius (Card)** | `12px` | `rounded-lg` |
| **Touch Target Mínimo** | `48×48px` | `min-h-12 min-w-12` |

> Nunca use valores hex ad-hoc. Sempre referencie os tokens do DESIGN.md.

---

## 4. Checklist de Validação Frontend
Antes de salvar qualquer modificação Blade, revise os seguintes tópicos:

### Acessibilidade (a11y)
- [ ] Elementos interativos estão focáveis via `Tab`? `:focus-visible` visível?
- [ ] Existem `aria-label` para botões de ícone sem texto?
- [ ] Regiões Livewire dinâmicas possuem `aria-live="polite"`?
- [ ] Barras de progresso possuem `role="progressbar"` + `aria-valuenow`?
- [ ] Contraste ≥ 4.5:1 (texto normal) e ≥ 3:1 (texto grande) em ambos os temas?
- [ ] `prefers-reduced-motion` é respeitado (animações desabilitadas)?
- [ ] Heading hierarchy correta (h1 único, sem pular níveis)?
- [ ] Skip link "Pular para conteúdo" presente?

### Mobile First
- [ ] A tela encolhe para 320px sem scroll horizontal?
- [ ] Touch targets ≥ 48px em mobile?
- [ ] Layout funcional em zoom 200%?

### Design Tokens
- [ ] Cores seguem a paleta do DESIGN.md (sem hex ad-hoc)?
- [ ] Tipografia usa Nunito com pesos permitidos (400–800)?
- [ ] Border radius segue os tokens definidos?

### Componentes
- [ ] Elementos genéricos substituídos por Flux (`<flux:input>`, `<flux:select>`, `<flux:checkbox>`, `<flux:radio>`)?
- [ ] Conteúdo assíncrono exibe skeleton loading com `animate-pulse`?

### Performance & PWA
- [ ] Não foram adicionadas bibliotecas JS grandes (preferindo JS puro, Alpine.js ou Livewire nativo)?
- [ ] Recursos estáticos com tags corretas para cache e imagens com lazy load?
- [ ] SVGs usados em vez de imagens rasterizadas para ícones?
