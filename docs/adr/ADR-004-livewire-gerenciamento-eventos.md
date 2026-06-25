# ADR-004: Uso de Livewire para o gerenciamento de eventos

- **Status:** Aceito
- **Data:** 2026-04-27
- **Commit de referência:** `5959ec8` — *feat: implement event management system with new Enums, Livewire components, and database enhancements*

---

## Contexto

A tela de gerenciamento de um evento concentra múltiplas visões: resumo, fichas, participantes, voluntários, trabalhadores, quadrante, presenças, crachás e contas. Cada aba exibe dados diferentes e pode ter interações independentes (aprovar ficha, confirmar presença, etc.). Implementar isso com o ciclo tradicional de requisição/resposta HTTP completo resultaria em recarregamentos de página frequentes e uma experiência de usuário fragmentada.

## Decisão

Adotar **Laravel Livewire** para o componente de gerenciamento de eventos (`livewire/evento/gerenciamento`). Cada aba é um partial Blade carregado dinamicamente pelo componente Livewire, que mantém estado no servidor e atualiza apenas as partes necessárias da página via AJAX transparente.

## Alternativas consideradas

- **SPA com Vue.js ou React:** Ofereceria mais interatividade, mas exigiria uma API REST separada, duplicação de lógica de negócio no frontend e um ciclo de desenvolvimento mais longo para o time atual.
- **Múltiplas páginas com links:** Cada aba seria uma rota separada. Simples, mas perde o contexto do evento entre navegações e fragmenta a experiência.
- **Alpine.js puro com requisições fetch:** Possível para interações simples, mas sem o binding de estado servidor-cliente que o Livewire oferece.

## Consequências

**Positivas:**
- Toda a lógica permanece em PHP/Laravel, sem necessidade de API REST dedicada.
- Atualizações parciais da página sem recarregamento completo.
- Integração natural com Blade e Eloquent.
- Menor curva de aprendizado para o time já familiarizado com Laravel.

**Negativas:**
- Componentes Livewire complexos podem ter problemas de performance com muitos dados (N+1 queries).
- Debugging de estado servidor-cliente é menos intuitivo que uma SPA convencional.
- Dependência do Livewire como camada adicional sobre o Laravel.
