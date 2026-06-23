<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TestRoutes extends Command
{
    protected $signature = 'route:test {--report-file=route_test_report.md}';

    protected $description = 'Testa todas as rotas GET para cada perfil e valida autorização';

    /**
     * Mapa de expectativas: URI resolvida => [perfil => status_esperado]
     * Aceita int ou array de ints (quando múltiplos status são válidos).
     */
    private array $expectativas = [
        // Públicas
        '/' => ['guest' => 200, 'user' => 200, 'coord' => 200, 'espec' => 200, 'admin' => 200],
        '/storage-link' => ['guest' => 200, 'user' => 200, 'coord' => 200, 'espec' => 200, 'admin' => 200],

        // Auth obrigatória — guest → 302 redirect para login
        '/dashboard' => ['guest' => 302, 'user' => 200, 'coord' => 200, 'espec' => 200, 'admin' => 200],
        '/vem' => ['guest' => 302, 'user' => 200, 'coord' => 200, 'espec' => 200, 'admin' => 200],
        '/ecc' => ['guest' => 302, 'user' => 200, 'coord' => 200, 'espec' => 200, 'admin' => 200],
        '/sgm' => ['guest' => 302, 'user' => 200, 'coord' => 200, 'espec' => 200, 'admin' => 200],
        '/timeline' => ['guest' => 302, 'user' => 200, 'coord' => 200, 'espec' => 200, 'admin' => 200],
        '/participantes' => ['guest' => 302, 'user' => 200, 'coord' => 200, 'espec' => 200, 'admin' => 200],
        '/aniversario' => ['guest' => 302, 'user' => 200, 'coord' => 200, 'espec' => 200, 'admin' => 200],
        '/termo-sgm' => ['guest' => 302, 'user' => 200, 'coord' => 200, 'espec' => 200, 'admin' => 200],
        '/termo-vem' => ['guest' => 302, 'user' => 200, 'coord' => 200, 'espec' => 200, 'admin' => 200],
        '/quadrante' => ['guest' => 302, 'user' => 200, 'coord' => 200, 'espec' => 200, 'admin' => 200],
        '/montagem' => ['guest' => 302, 'user' => 200, 'coord' => 200, 'espec' => 200, 'admin' => 200],
        '/avaliacao' => ['guest' => 302, 'user' => 200, 'coord' => 200, 'espec' => 200, 'admin' => 200],
        '/trabalhadores/create' => ['guest' => 302, 'user' => 200, 'coord' => 200, 'espec' => 200, 'admin' => 200],
        '/trabalhadores/review' => ['guest' => 302, 'user' => 200, 'coord' => 200, 'espec' => 200, 'admin' => 200],
        '/settings/profile' => ['guest' => 302, 'user' => 200, 'coord' => 200, 'espec' => 200, 'admin' => 200],
        '/settings/password' => ['guest' => 302, 'user' => 200, 'coord' => 200, 'espec' => 200, 'admin' => 200],
        '/settings/appearance' => ['guest' => 302, 'user' => 200, 'coord' => 200, 'espec' => 200, 'admin' => 200],

        // Somente admin
        '/contatos' => ['guest' => 302, 'user' => 403, 'coord' => 403, 'espec' => 403, 'admin' => 200],
        '/configuracoes' => ['guest' => 302, 'user' => 403, 'coord' => 403, 'espec' => 403, 'admin' => 200],
        '/configuracoes/role' => ['guest' => 302, 'user' => 403, 'coord' => 403, 'espec' => 403, 'admin' => 200],
        '/configuracoes/equipe' => ['guest' => 302, 'user' => 403, 'coord' => 403, 'espec' => 403, 'admin' => 200],
        '/configuracoes/movimento' => ['guest' => 302, 'user' => 403, 'coord' => 403, 'espec' => 403, 'admin' => 200],
        '/configuracoes/responsavel' => ['guest' => 302, 'user' => 403, 'coord' => 403, 'espec' => 403, 'admin' => 200],
        '/configuracoes/restricao' => ['guest' => 302, 'user' => 403, 'coord' => 403, 'espec' => 403, 'admin' => 200],
        '/eventos/create' => ['guest' => 302, 'user' => 403, 'coord' => 403, 'espec' => 403, 'admin' => 200],
        '/eventos/1/edit' => ['guest' => 302, 'user' => 403, 'coord' => 403, 'espec' => 403, 'admin' => [200, 404]],

        // Admin + coord
        '/trabalhadores' => ['guest' => 302, 'user' => 403, 'coord' => 200, 'espec' => 403, 'admin' => 200],

        // Admin + espec (ID fictício → 404 aceitável para autenticados)
        '/eventos/1/gerenciamento' => ['guest' => 302, 'user' => 403, 'coord' => 403, 'espec' => [200, 404], 'admin' => [200, 404]],

        // Recursos — todos autenticados têm acesso à listagem
        '/eventos' => ['guest' => 302, 'user' => 200, 'coord' => 200, 'espec' => 200, 'admin' => 200],
        '/eventos/1' => ['guest' => 302, 'user' => [200, 404], 'coord' => [200, 404], 'espec' => [200, 404], 'admin' => [200, 404]],
        '/pessoas' => ['guest' => 302, 'user' => 403, 'coord' => 403, 'espec' => 403, 'admin' => 200],
        '/pessoas/1' => ['guest' => 302, 'user' => [200, 404], 'coord' => [200, 404], 'espec' => [200, 404], 'admin' => [200, 404]],
        '/fichas/vem' => ['guest' => 302, 'user' => 200, 'coord' => 200, 'espec' => 200, 'admin' => 200],
        '/fichas/vem/1' => ['guest' => 302, 'user' => [200, 404], 'coord' => [200, 404], 'espec' => [200, 404], 'admin' => [200, 404]],
        '/fichas/ecc' => ['guest' => 302, 'user' => 200, 'coord' => 200, 'espec' => 200, 'admin' => 200],
        '/fichas/ecc/1' => ['guest' => 302, 'user' => [200, 404], 'coord' => [200, 404], 'espec' => [200, 404], 'admin' => [200, 404]],
        '/fichas/sgm' => ['guest' => 302, 'user' => 200, 'coord' => 200, 'espec' => 200, 'admin' => 200],
        '/fichas/sgm/1' => ['guest' => 302, 'user' => [200, 404], 'coord' => [200, 404], 'espec' => [200, 404], 'admin' => [200, 404]],
    ];

    private array $perfis = ['guest', 'user', 'coord', 'espec', 'admin'];

    private array $usuarios = [];

    public function handle(): void
    {
        $this->criarUsuarios();

        $rows = [];
        $markdown = $this->cabecalhoMarkdown();
        $falhas = 0;
        $total = 0;

        $rotasGet = collect(Route::getRoutes()->getRoutes())->filter(
            fn ($r) => in_array('GET', $r->methods()) && ! $this->deveIgnorar($r->uri())
        );

        $this->info('Rotas GET encontradas: '.$rotasGet->count());

        foreach ($rotasGet as $route) {
            $uri = $route->uri();
            $testUri = $this->resolverUri($uri);
            $this->line("  Testando: {$testUri} [{$uri}]");

            foreach ($this->perfis as $perfil) {
                $total++;

                try {
                    [$status, $msg] = $this->executarRequisicao($testUri, $perfil);
                } catch (\Throwable $e) {
                    $status = 500;
                    $msg = get_class($e).': '.substr($e->getMessage(), 0, 60);
                    $this->error("ERRO em {$testUri} [{$perfil}]: ".$e->getMessage());
                }

                $esperado = $this->expectativas[$testUri] ?? null;
                $statusEsp = $esperado[$perfil] ?? '?';
                $passou = $this->validarStatus($status, $statusEsp);

                if (! $passou) {
                    $falhas++;
                }

                $icone = match (true) {
                    $statusEsp === '?' => '⚪',
                    $passou => '✅',
                    default => '❌',
                };

                $espStr = is_array($statusEsp) ? implode('/', $statusEsp) : (string) $statusEsp;

                $rows[] = [
                    'URI' => $uri,
                    'Perfil' => $perfil,
                    'Status' => $status,
                    'Esperado' => $espStr,
                    'Resultado' => $icone,
                    'Mensagem' => $msg,
                ];

                $markdown .= "| {$uri} | {$perfil} | {$status} | {$espStr} | {$icone} | {$msg} |\n";
            }
        }

        $this->table(['URI', 'Perfil', 'Status', 'Esperado', 'Resultado', 'Mensagem'], $rows);

        $resumo = "\n\n## Resumo\n- Total: {$total}\n- ✅ Aprovados: ".($total - $falhas)."\n- ❌ Falhas: {$falhas}";
        file_put_contents(base_path($this->option('report-file')), $markdown.$resumo);

        $this->newLine();
        $this->info("Total: {$total} | ✅ ".($total - $falhas)." | ❌ {$falhas}");
        $this->info('Relatório salvo em: '.base_path($this->option('report-file')));

        $this->removerUsuarios();
    }

    // -------------------------------------------------------------------------

    private function executarRequisicao(string $testUri, string $perfil): array
    {
        try {
            $request = Request::create($testUri, 'GET', [], [], [], [
                'HTTP_HOST' => 'localhost',
                'SERVER_PORT' => '80',
            ]);

            // Injeta usuário no guard
            $user = $this->usuarios[$perfil] ?? null;
            if ($user) {
                $request->setUserResolver(fn () => $user);
                Auth::guard('web')->setUser($user);
            } else {
                Auth::guard('web')->logout();
            }

            // Substitui o request atual no container
            app()->instance('request', $request);
            \Illuminate\Support\Facades\Request::swap($request);

            // Dispatch via pipeline de middleware
            $router = app('router');
            $response = $router->dispatch($request);
            $status = $response->getStatusCode();

            $msg = 'OK';
            if (property_exists($response, 'exception') && $response->exception) {
                $msg = substr($response->exception->getMessage(), 0, 80);
            }

            return [$status, $msg ?: 'OK'];
        } catch (HttpException $e) {
            return [$e->getStatusCode(), substr($e->getMessage(), 0, 80) ?: 'HTTP '.$e->getStatusCode()];
        } catch (\Throwable $e) {
            return [500, substr($e->getMessage(), 0, 80)];
        }
    }

    private function criarUsuarios(): void
    {
        $this->info('Criando usuários temporários...');

        foreach (['user', 'coord', 'espec', 'admin'] as $role) {
            try {
                $user = User::firstOrCreate(
                    ['email' => "route_tester_{$role}@example.com"],
                    [
                        'name' => "Tester {$role}",
                        'password' => Hash::make('password123'),
                        'email_verified_at' => now(),
                        'role' => $role,
                    ]
                );
                // Garante role mesmo se já existia
                $user->role = $role;

                $this->usuarios[$role] = $user;
                $this->line("  ✅ {$role} (id={$user->id})");
            } catch (\Throwable $e) {
                $this->error("  ❌ Falha ao criar {$role}: ".$e->getMessage());
                throw $e;
            }
        }

        $this->usuarios['guest'] = null;
    }

    private function removerUsuarios(): void
    {
        $this->info('Removendo usuários temporários...');
        foreach (['user', 'coord', 'espec', 'admin'] as $role) {
            try {
                $this->usuarios[$role]?->forceDelete();
            } catch (\Throwable $e) {
                $this->line("  ⚠️ Não foi possível deletar o usuário {$role}: " . $e->getMessage());
            }
        }
    }

    private function validarStatus(int $status, mixed $esperado): bool
    {
        if ($esperado === '?') {
            return true;
        }

        return is_array($esperado) ? in_array($status, $esperado) : $status === $esperado;
    }

    private function resolverUri(string $uri): string
    {
        $resolved = preg_replace('/\{[a-zA-Z0-9_?\[\]\-]+\}/', '1', $uri);
        $resolved = '/'.ltrim($resolved, '/');

        return $resolved === '' ? '/' : $resolved;
    }

    private function deveIgnorar(string $uri): bool
    {
        foreach (['_ignition', 'sanctum', 'livewire', 'flux', 'up', '_debugbar', 'storage', 'limpar-tudo', 'otimizar-tudo'] as $p) {
            if (str_starts_with($uri, $p)) {
                return true;
            }
        }

        return false;
    }

    private function cabecalhoMarkdown(): string
    {
        return "# Relatório de Testes de Rotas\n\nGerado em: ".now()->format('d/m/Y H:i:s')."\n\n"
            ."| URI | Perfil | Status | Esperado | Resultado | Mensagem |\n"
            ."|-----|--------|--------|----------|-----------|----------|\n";
    }
}
