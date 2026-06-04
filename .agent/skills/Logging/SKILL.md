---
name: logging-monitoramento-rastreabilidade
description: Skill senior para padronização de Logging PSR-3, Rastreabilidade com Trace IDs, Contextos de Log e Monitoramento de Exceptions em Laravel.
---

# Skill: Logging, Monitoramento e Rastreabilidade (Laravel)

Esta Skill define os padrões e as diretrizes inegociáveis para o registro de logs, rastreabilidade de transações ponta a ponta e monitoramento de falhas do projeto PNSL-NTM. O objetivo é garantir que logs de produção sejam ricos em contexto, fáceis de filtrar através de agregadores de logs e extremamente úteis para debugging sem expor dados sensíveis.

---

## 1. Rastreabilidade com Trace IDs (Middleware)

O projeto possui o `TraceIdMiddleware` registrado no ciclo global de requisições HTTP, que anexa um identificador único universal (UUID) a cada request.

### A. Como Funciona
1. O middleware gera um UUID para o request.
2. Define o cabeçalho `X-Trace-ID` na requisição (permitindo que clientes HTTP vejam o ID do trace).
3. Registra o trace no container do Laravel (`app()->instance('trace_id', $traceId)`).
4. Injeta o trace automaticamente no contexto compartilhado do logger do Laravel via `Log::withContext(['trace_id' => $traceId])`.

> [!TIP]
> Qualquer log gerado via classe `Log::` (independente de onde seja acionado no fluxo) incluirá de forma transparente a chave `"trace_id"` no JSON final do log.

---

## 2. Contexto Base para Logs (LogContext Trait)

Para enriquecer os logs de controladores com informações de ambiente do usuário sem repetir código, utilize inegociavelmente a Trait `App\Traits\LogContext` em todos os controladores.

### A. Estrutura da Trait `LogContext`
```php
namespace App\Traits;

use Illuminate\Http\Request;

trait LogContext
{
    protected function getLogContext(Request $request): array
    {
        return [
            'user_id' => auth()->check() ? auth()->id() : 'guest',
            'ip' => $request->ip(),
            'route_name' => $request->route() ? $request->route()->getName() : null,
        ];
    }
}
```

### B. Exemplo de Uso Prático em Controladores
Sempre recupere o contexto no início do método e passe-o em todas as chamadas de logs subsequentes:

```php
public function store(FichaRequest $request)
{
    $start = microtime(true);
    $context = $this->getLogContext($request);

    Log::info('Tentativa de criação de ficha iniciada', array_merge($context, [
        'candidato' => $request->input('nom_candidato'),
    ]));

    try {
        // Fluxo de criação...
        
        $duration = round((microtime(true) - $start) * 1000, 2);
        Log::notice('Ficha criada com sucesso', array_merge($context, [
            'ficha_id' => $ficha->id,
            'duration_ms' => $duration,
        ]));
        
    } catch (\Throwable $e) {
        // Ver regras de log de exceções abaixo
    }
}
```

---

## 3. Classificação Rigorosa de Níveis de Logs (PSR-3)

Você deve aplicar os níveis de severidade adequados para cada cenário técnico, evitando logs poluídos em produção:

| Severidade | PSR-3 Método | Descrição e Caso de Uso |
| :--- | :--- | :--- |
| **DEBUG** | `Log::debug()` | Informações altamente detalhadas úteis apenas em desenvolvimento/sandbox. |
| **INFO** | `Log::info()` | Início de operações chaves, acessos a telas e requisições normais de leitura. |
| **NOTICE** | `Log::notice()` | Operações concluídas com absoluto sucesso que afetam o negócio (ex: cadastros, edições). |
| **WARNING** | `Log::warning()` | Falhas de usuário ou erros de fluxo previstos e tratados (ex: CPF duplicado, login incorreto). |
| **ERROR** | `Log::error()` | Falhas inesperadas de sistema, exceptions capturadas e erros severos de banco/rollbacks. |

---

## 4. Padrão Técnico para Log de Exceções (Exceptions)

> [!WARNING]
> Nunca logue apenas o `$e->getMessage()`. Isso oculta o local do erro e dificulta a depuração.
> Logue sempre o contexto completo do erro contendo a classe da exceção, arquivo físico e a linha exata da falha.

### Padrão Inegociável de Log no Catch Block
```php
try {
    // Fluxo de banco ou rede...
} catch (\Throwable $e) {
    DB::rollBack();

    Log::error('Erro inesperado ao salvar ficha', array_merge($context, [
        'exception' => get_class($e),
        'message'   => $e->getMessage(),
        'file'      => $e->getFile(),
        'line'      => $e->getLine(),
    ]));

    return back()->with('error', 'Ocorreu um erro. Tente novamente.');
}
```

---

## 5. Proteção de Dados Sensíveis (Lei Geral de Proteção de Dados - LGPD)

> [!CAUTION]
> É estritamente **proibido** registrar dados confidenciais do usuário nos payloads e parâmetros de logs.
> Nunca grave em texto aberto no log:
> - Senhas e tokens de autenticação (`password`, `token`, `bearer_token`).
> - Números completos de cartões de crédito.
> - Dados pessoais não essenciais de triagem médica.

### Como higienizar dados em logs:
Sempre use sanitização se precisar logar payloads de requests:
```php
// Higienização manual de chaves sensíveis
$payloadLog = $request->except(['password', 'password_confirmation', 'med_foto']);
Log::info('Tentativa de login', array_merge($context, ['payload' => $payloadLog]));
```

---

## 6. Checklist de Validação de Logging
Sempre que for solicitado a criar logs ou modificar controladores, valide:
- [ ] O controlador faz uso da trait `App\Traits\LogContext`?
- [ ] O contexto `$context` é recuperado no início e mesclado em todas as chamadas de `Log::`?
- [ ] O nível `notice` é usado para sucessos de escrita chaves e `warning` para erros previstos do usuário?
- [ ] Todos os blocos `catch (\Throwable $e)` possuem log nível `error` detalhando classe, mensagem, arquivo e linha da exceção?
- [ ] Não há vazamento de dados sensíveis (senhas/tokens) nas variáveis extras passadas ao log?
