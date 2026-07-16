<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head', ['title' => 'Sem Autorização'])
</head>

<body class="min-h-screen bg-zinc-50 dark:bg-zinc-900 flex items-center justify-center p-6 antialiased font-sans">
    <div class="max-w-md w-full text-center space-y-6">
        <!-- Ícone Premium de Bloqueio/Escudo -->
        <div class="relative inline-flex items-center justify-center">
            <div class="absolute inset-0 bg-red-500/20 dark:bg-red-500/10 rounded-full blur-xl w-24 h-24 mx-auto left-0 right-0"></div>
            <div class="relative bg-white dark:bg-zinc-800 border border-red-200 dark:border-red-900/50 rounded-2xl p-5 shadow-lg">
                <svg class="w-12 h-12 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
            </div>
        </div>

        <!-- Conteúdo do Erro -->
        <div class="space-y-3">
            <h1 class="text-3xl font-extrabold text-zinc-900 dark:text-zinc-50 tracking-tight">
                Sem Autorização
            </h1>
            <p class="text-zinc-500 dark:text-zinc-400 text-sm max-w-sm mx-auto leading-relaxed">
                {{ $exception->getMessage() ?: 'Você não tem permissão para acessar esta página ou recurso.' }}
            </p>
        </div>

        <!-- Botões de Ação -->
        <div class="flex flex-col sm:flex-row gap-3 justify-center pt-2">
            <a href="javascript:history.back()" 
                class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-semibold text-zinc-700 dark:text-zinc-200 bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700/80 border border-zinc-200 dark:border-zinc-700 rounded-lg transition-all shadow-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Voltar
            </a>
            
            <a href="{{ auth()->check() ? route('dashboard') : route('home') }}" 
                class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-indigo-500 to-purple-500 hover:from-indigo-600 hover:to-purple-600 rounded-lg shadow-md hover:shadow-lg transition-all">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
                Ir para o Painel
            </a>
        </div>
    </div>
    @fluxScripts
</body>

</html>
