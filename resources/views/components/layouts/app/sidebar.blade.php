<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white dark:bg-zinc-800 font-sans antialiased text-zinc-900 dark:text-zinc-100">
    <flux:sidebar sticky stashable class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />
        <a href="{{ route('dashboard') }}" class="me-5 flex items-center space-x-2 rtl:space-x-reverse" wire:navigate aria-label="Página Inicial do Sistema">
            <x-app-logo aria-hidden="true" />
        </a>
        <flux:navlist variant="outline">
            <flux:navlist.group :heading="__('Acesse')" class="grid">
                <flux:navlist.item icon="home" :href="route('home')" :current="request()->routeIs('home')"
                    wire:navigate>
                    {{ __('Início') }}
                </flux:navlist.item>
                <flux:navlist.item icon="bookmark" :href="route('dashboard')" :current="request()->routeIs('dashboard')"
                    wire:navigate>
                    {{ __('Painel') }}
                </flux:navlist.item>
                @if (Auth::user() && Auth::user()->hasRole('admin', 'espec'))
                    <flux:navlist.item icon="cog" :href="route('configuracoes.index')"
                        :current="request()->routeIs('configuracoes.index')" wire:navigate>
                        {{ __('Configurações') }}
                    </flux:navlist.item>
                @endif
                @if (Auth::user() && Auth::user()->isAdmin())
                    <flux:navlist.item icon="phone" :href="route('contatos.index')"
                        :current="request()->routeIs('contatos.index')" wire:navigate>
                        {{ __('Contatos') }}
                    </flux:navlist.item>
                @endif
                <flux:navlist.item icon="clock" :href="route('timeline.index')"
                    :current="request()->routeIs('timeline.index')" wire:navigate>
                    {{ __('Aura') }}
                </flux:navlist.item>
                <flux:navlist.item icon="calendar" :href="route('eventos.index')"
                    :current="request()->routeIs('eventos.index')" wire:navigate>
                    {{ __('Eventos') }}
                </flux:navlist.item>
                @if (Auth::user() && Auth::user()->hasRole('admin', 'espec', 'coord'))
                    <flux:navlist.item icon="chat-bubble-left-right" :href="route('mensagens.index')"
                        :current="request()->routeIs('mensagens.*')" wire:navigate>
                        {{ __('Mensagens') }}
                    </flux:navlist.item>
                @endif
                @if (Auth::user() && Auth::user()->pessoa?->idt_pessoa)
                    <flux:navlist.item icon="user"
                        :href="route('pessoas.edit', ['pessoa' => Auth::user()->pessoa->idt_pessoa])"
                        :current="request()->routeIs('pessoas.edit')" wire:navigate>
                        {{ __('Meus Dados') }}
                    </flux:navlist.item>
                @else
                    <flux:navlist.item icon="user"
                        :href="route('pessoas.create')"
                        :current="request()->routeIs('pessoas.create')" wire:navigate>
                        {{ __('Meus Dados') }}
                    </flux:navlist.item>
                @endif
                @if (Auth::user() && Auth::user()->podeAcessarMinhasFichas())
                    <flux:navlist.item icon="document-text" :href="route('minhas-fichas.index')"
                        :current="request()->routeIs('minhas-fichas.index')" wire:navigate>
                        {{ __('Minhas Fichas') }}
                    </flux:navlist.item>
                @endif
                @if (Auth::user() && Auth::user()->hasRole('admin', 'sales'))
                    <flux:navlist.item icon="shopping-cart" :href="route('mercadinho.index')"
                        :current="request()->routeIs('mercadinho.index*')" wire:navigate>
                        {{ __('Mercadinho') }}
                    </flux:navlist.item>
                @endif
                @if (Auth::user() && Auth::user()->hasRole('admin', 'coord', 'espec', 'sales'))
                    <flux:navlist.item icon="user-group" :href="route('trabalhadores.minha-equipe')"
                        :current="request()->routeIs('trabalhadores.minha-equipe')" wire:navigate>
                        {{ __('Minha Equipe') }}
                    </flux:navlist.item>
                @endif

            </flux:navlist.group>
        </flux:navlist>
        <flux:spacer />

        <div class="flex items-center my-4 gap-2">
            <!-- Sun Icon (left) -->
            <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-yellow-600 dark:text-zinc-100" fill="none"
                viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 3v1m0 16v1m8.66-13.66l-.71.71M4.05 19.07l-.71.71M21 12h-1M4 12H3m16.66 5.66l-.71-.71M4.05 4.93l-.71-.71M12 5a7 7 0 100 14 7 7 0 000-14z" />
            </svg>
            <!-- Switch Button -->
            <button x-data="{ dark: $flux.appearance === 'dark' }" @click="dark = !dark; $flux.appearance = dark ? 'dark' : 'light'"
                :aria-pressed="dark" type="button"
                class="relative flex items-center rounded-full w-12 h-6 bg-zinc-200 dark:bg-zinc-700 transition-colors duration-200 focus:outline-none">
                <!-- Switch Track -->
                <span class="absolute inset-0 rounded-full bg-zinc-300 dark:bg-zinc-600 transition-colors"></span>
                <!-- Switch Knob -->
                <span
                    class="absolute top-0 left-0 h-6 w-6 bg-white dark:bg-zinc-800 rounded-full shadow transition-transform duration-200"
                    :class="dark ? 'translate-x-6' : 'translate-x-0'"></span>
                <span class="sr-only">
                    {{ __('Alternar modo noturno') }}
                </span>
            </button>
            <!-- Moon Icon (right) -->
            <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-zinc-800 dark:text-yellow-300" fill="none"
                viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M21 12.79A9 9 0 1111.21 3a7 7 0 109.79 9.79z" />
            </svg>
        </div>


        <!-- Desktop User Menu -->
        <flux:dropdown class="hidden lg:block" position="bottom" align="start">
            <flux:profile :name="auth()->user()->name" :initials="auth()->user()->initials()"
                icon:trailing="chevrons-up-down" />
            <flux:menu class="w-[220px]">
                <flux:menu.radio.group>
                    <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                        <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                            <span
                                class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                {{ auth()->user()->initials() }}
                            </span>
                        </span>
                        <div class="grid flex-1 text-start text-sm leading-tight">
                            <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                            <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                        </div>
                    </div>
                </flux:menu.radio.group>
                <flux:menu.separator />
                <flux:menu.radio.group>
                    <flux:menu.item :href="route('settings.profile')" icon="cog" wire:navigate>
                        {{ __('Settings') }}
                    </flux:menu.item>
                </flux:menu.radio.group>
                <flux:menu.separator />
                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                        {{ __('Log Out') }}
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:sidebar>
    <!-- Mobile User Menu -->
    <flux:header class="lg:hidden">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
        <flux:spacer />
        <flux:dropdown position="top" align="end">
            <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" />
            <flux:menu>
                <flux:menu.radio.group>
                    <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                        <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                            <span
                                class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                {{ auth()->user()->initials() }}
                            </span>
                        </span>
                        <div class="grid flex-1 text-start text-sm leading-tight">
                            <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                            <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                        </div>
                    </div>
                </flux:menu.radio.group>
                <flux:menu.separator />
                <flux:menu.radio.group>
                    <flux:menu.item :href="route('settings.profile')" icon="cog" wire:navigate>
                        {{ __('Settings') }}
                    </flux:menu.item>
                </flux:menu.radio.group>
                <flux:menu.separator />
                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                        {{ __('Log Out') }}
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:header>
    {{ $slot }}
    @fluxScripts
</body>

</html>
