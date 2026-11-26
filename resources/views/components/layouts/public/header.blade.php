<!DOCTYPE html>
<html lang="pt-BR" class="dark">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white dark:bg-zinc-800">
    @php($haCandidaturaAberta = \App\Models\Evento::comCandidaturaAberta()->exists())
    <flux:header container class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" aria-label="Abrir menu" />

        <a href="{{ route('home') }}" class="ms-2 me-5 flex items-center space-x-2 rtl:space-x-reverse lg:ms-0"
            wire:navigate>
            <x-app-logo />
        </a>

        <flux:spacer />

        <flux:navbar class="-mb-px max-lg:hidden">
            @if ($haCandidaturaAberta)
                <flux:navbar.item icon="hand-raised" :href="route('trabalhadores.candidatura.eventos')"
                    :current="request()->routeIs('trabalhadores.candidatura*')">Quero trabalhar</flux:navbar.item>
            @endif
            <flux:navbar.item icon="home" :href="route('home')" :current="request()->routeIs('home')" wire:navigate>
                {{ __('Início') }}
            </flux:navbar.item>
            <flux:navbar.item icon="arrow-right-end-on-rectangle" :href="route('login')"
                :current="request()->routeIs('login')" wire:navigate>
                {{ __('Acesse') }}
            </flux:navbar.item>
            @if (!Auth::check()) 
            <flux:navbar.item icon="user-plus" :href="route('register')" :current="request()->routeIs('register')"
                wire:navigate>
                {{ __('Cadastre-se') }}
            </flux:navbar.item>
            @endif
        </flux:navbar>
    </flux:header>

    <!-- Mobile Menu -->
    <flux:sidebar stashable sticky
        class="lg:hidden data-flux-sidebar-collapsed-mobile:invisible border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.toggle class="lg:hidden" icon="x-mark" aria-label="Fechar menu" />

        <a href="{{ route('home') }}" class="ms-1 flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
            <x-app-logo />
        </a>

        <flux:navlist variant="outline">
            @if ($haCandidaturaAberta)
                <flux:navlist.item icon="hand-raised" :href="route('trabalhadores.candidatura.eventos')"
                    :current="request()->routeIs('trabalhadores.candidatura*')">Quero trabalhar</flux:navlist.item>
            @endif
            <flux:navlist.group :heading="__('Acesse')">
                <flux:navlist.item icon="home" :href="route('home')" :current="request()->routeIs('home')"
                    wire:navigate>
                    {{ __('Início') }}
                </flux:navlist.item>
                <flux:navlist.item icon="arrow-right-end-on-rectangle" :href="route('login')"
                    :current="request()->routeIs('login')" wire:navigate>
                    {{ __('Acesse') }}
                </flux:navlist.item>
                @if (!Auth::check()) 
                <flux:navlist.item icon="user-plus" :href="route('register')"
                    :current="request()->routeIs('register')" wire:navigate>
                    {{ __('Cadastre-se') }}
                </flux:navlist.item>
                @endif
            </flux:navlist.group>
        </flux:navlist>
    </flux:sidebar>

    {{ $slot }}

    @fluxScripts
</body>

</html>
