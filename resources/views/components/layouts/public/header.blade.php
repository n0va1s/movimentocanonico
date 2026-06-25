<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white dark:bg-zinc-800">
    <flux:header container class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

        <a href="{{ route('home') }}" class="ms-2 me-5 flex items-center space-x-2 rtl:space-x-reverse lg:ms-0"
            wire:navigate>
            <x-app-logo />
        </a>

        <flux:spacer />

        <flux:navbar class="-mb-px max-lg:hidden">
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
        class="lg:hidden border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

        <a href="{{ route('home') }}" class="ms-1 flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
            <x-app-logo />
        </a>

        <flux:navlist variant="outline">
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
