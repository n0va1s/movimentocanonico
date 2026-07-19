<?php

use App\Http\Middleware\RestrictDirigMovement;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\TraceIdMiddleware;
use App\Notifications\SystemExceptionTelegram;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Livewire\Features\SupportDisablingBackButtonCache\DisableBackButtonCacheMiddleware;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'dirig.movimento' => RestrictDirigMovement::class,
        ]);

        $middleware->append(TraceIdMiddleware::class);
        $middleware->web(append: [
            DisableBackButtonCacheMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->reportable(function (Throwable $e) {
            if ($e instanceof HttpExceptionInterface && $e->getStatusCode() < 500) {
                return;
            }

            // Tenta logar normalmente, mas se a Facade não estiver pronta, usa error_log
            try {
                if (class_exists(\Illuminate\Support\Facades\Log::class) && \Illuminate\Support\Facades\Log::getFacadeRoot()) {
                    \Illuminate\Support\Facades\Log::error($e->getMessage(), [
                        'exception' => get_class($e),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                } else {
                    error_log('EARLY EXCEPTION: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
                }
            } catch (\Throwable $loggingException) {
                error_log('LOGGING EXCEPTION: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            }

            // Protege contra falha no cache (ex: banco indisponível) para não
            // silenciar o erro original nem impedir o log acima
            try {
                $errorHash = md5($e->getMessage().$e->getFile().$e->getLine());

                if (! Cache::has('error_notification_'.$errorHash)) {
                    $traceId = app()->has('trace_id') ? app('trace_id') : 'N/A';
                    $chatId = config('services.telegram-bot-api.chat_id');

                    if ($chatId) {
                        Notification::route('telegram', $chatId)
                            ->notify(new SystemExceptionTelegram($e, $traceId));
                    }

                    Cache::put('error_notification_'.$errorHash, true, now()->addMinutes(5));
                }
            } catch (Throwable $cacheException) {
                if (class_exists(\Illuminate\Support\Facades\Log::class) && \Illuminate\Support\Facades\Log::getFacadeRoot()) {
                    \Illuminate\Support\Facades\Log::warning('Falha ao enviar notificação de erro (cache/telegram indisponível)', [
                        'cache_error' => $cacheException->getMessage(),
                    ]);
                } else {
                    error_log('CACHE ERROR: ' . $cacheException->getMessage());
                }
            }
        });
    })->create();
