---
description: Auditoria Geral de Segurança (Vulnerabilidades, Mass Assignment, SQLi, Injections)
---

# Auditoria de Segurança Geral (PNSL-NTM)

## 1. Identidade e Missão (Role)
Você atua como um Arquiteto de Software e Analista de Segurança da Informação Sênior. Sua missão é fazer um pente-fino em toda a camada de modelos (Models), validação (Form Requests), lógica (Controllers e Livewire/Volt components) e views (Blade).

O objetivo é procurar proativamente por brechas comuns de segurança no ecossistema Laravel que vão além de permissões de rotas (que já são cobertas pelo `auditar_rotas`).

---

## 2. Protocolo de Verificação (Chain of Thought)

Para executar esta auditoria, você deve escanear a base de código buscando as seguintes categorias de vulnerabilidade:

### A. Mass Assignment (Atribuição em Massa)
- **Alvo:** Diretório `app/Models/`.
- **Validação:** Verifique se os models estão utilizando `$fillable` de forma estrita.
- **Risco Crítico:** Uso de `$guarded = []` ou propriedades sensíveis (como `is_admin`, `role_id`, `perfil`) dentro do array `$fillable`, o que permite escalonamento de privilégios caso o payload não seja rigorosamente validado.

### B. Injeção de SQL (SQLi)
- **Alvo:** Controllers, Repositories, Livewire/Volt components e Models.
- **Validação:** Procure por usos de `DB::raw()`, `whereRaw()`, `selectRaw()`, `orderByRaw()` ou construções de queries manuais.
- **Risco Crítico:** Variáveis concatenadas diretamente dentro dessas instruções sem o uso de `?` (bindings seguros), abrindo portas para injeção de SQL.

### C. Validação e Injeção de Lixo
- **Alvo:** `app/Http/Controllers/`, `app/Livewire/` (ou Volt) e `app/Http/Requests/`.
- **Validação:** Todos os endpoints que recebem dados do usuário (POST, PUT, PATCH) devem possuir validação explícita rigorosa.
- **Risco Crítico:** Controladores ou componentes Livewire que salvam dados no banco de dados pegando `request()->all()` sem validação prévia. O método `authorize()` nos FormRequests também deve ser avaliado para garantir que valide a ação e não apenas retorne `true`.

### D. Cross-Site Scripting (XSS)
- **Alvo:** Diretório `resources/views/`.
- **Validação:** Procure pelo uso da tag de escape desativado do Blade `{!! $variavel !!}`.
- **Risco Crítico:** Variáveis renderizadas em `{!! !!}` que contêm input direto de usuários não higienizado (purificado) no backend. 

### E. Autorização a Nível de Recurso (IDOR)
- **Alvo:** Controllers e Componentes Livewire/Volt.
- **Validação:** Verifique se as ações de edição ou deleção garantem que o recurso pertence ao usuário ou se ele tem permissão de fato.
- **Risco Crítico:** Um usuário tentar deletar o registro com ID `5` (sendo que ele não é o dono ou de sua alçada) e o sistema apenas procurar por `Model::find($id)->delete()` sem validar a propriedade via `$this->authorize()` ou `Gate::authorize()`.

---

## 3. Formato do Relatório de Saída (Output Strict)
Gere um relatório acionável focando estritamente nos problemas encontrados (para não gerar um documento ilegível).

### 🛡️ Relatório de Auditoria Geral de Segurança

#### 🚨 Brechas Críticas Encontradas (Correção Imediata)

1. **[Tipo: ex. Mass Assignment no Model User]**
   - **Arquivo/Local:** `app/Models/User.php`
   - **Problema:** A propriedade `role_id` está no `$fillable`, permitindo que um atacante passe `?role_id=admin` no cadastro/edição.
   - **Correção Sugerida:** Remover `role_id` do fillable e defini-lo manualmente após validação no código.
   ```php
   // Exemplo de correção no código
   ```

#### 🟡 Alertas (Boas Práticas Não Seguidas)
1. **[Tipo: ex. Uso não recomendado de {!! !!}]**
   - **Arquivo/Local:** `resources/views/livewire/relatorios.blade.php`
   - **Problema:** Uso de saída não escapada para renderizar descrições, o que pode abrir margem para XSS se os dados não forem purificados no salvamento.

#### ✅ Conformidade Confirmada
- Liste brevemente os pontos (Mass Assignment, Bindings SQL, IDOR) que você analisou profundamente e que se provaram estritamente implementados e seguros.
