<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-100 dark:bg-zinc-900">

        {{-- ===== SIDEBAR ===== --}}
        <flux:sidebar sticky collapsible="mobile" class="w-60 border-e border-zinc-800/60 bg-zinc-950 dark:border-zinc-800/60 dark:bg-zinc-950">

            {{-- Brand --}}
            <flux:sidebar.header class="border-b border-zinc-800/60 px-4 py-4">
                <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-3">
                    <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-blue-500 to-blue-700 shadow-md shadow-blue-900/40">
                        <x-app-logo-icon class="size-4 fill-current text-white" />
                    </div>
                    <span class="text-sm font-semibold tracking-wide text-white">WareTrack</span>
                </a>
                <flux:sidebar.collapse class="ml-auto text-zinc-500 hover:text-zinc-300 lg:hidden" />
            </flux:sidebar.header>

            {{-- Navigation --}}
            <flux:sidebar.nav class="flex flex-col gap-5 px-3 py-4">

                {{-- General --}}
                <div>
                    <p class="mb-1.5 px-3 text-[10px] font-semibold uppercase tracking-widest text-zinc-600">{{ __('General') }}</p>
                    <div class="flex flex-col gap-0.5">
                        <x-nav-item href="{{ route('dashboard') }}" :active="request()->routeIs('dashboard')" icon="home">
                            {{ __('Dashboard') }}
                        </x-nav-item>
                        <x-nav-item href="{{ route('stock.index') }}" :active="request()->routeIs('stock.*')" icon="cube">
                            {{ __('Stock') }}
                        </x-nav-item>
                        <x-nav-item href="{{ route('reports.index') }}" :active="request()->routeIs('reports.*')" icon="chart-bar">
                            {{ __('Reports') }}
                        </x-nav-item>
                        <x-nav-item href="{{ route('deliveries.index') }}" :active="request()->routeIs('deliveries.*')" icon="truck">
                            {{ __('Deliveries') }}
                        </x-nav-item>
                        <x-nav-item href="{{ route('suppliers.index') }}" :active="request()->routeIs('suppliers.*')" icon="building-storefront">
                            {{ __('Suppliers') }}
                        </x-nav-item>
                    </div>
                </div>

                {{-- Admin --}}
                @if(auth()->user()?->isAdmin())
                    <div>
                        <p class="mb-1.5 px-3 text-[10px] font-semibold uppercase tracking-widest text-zinc-600">{{ __('Admin') }}</p>
                        <div class="flex flex-col gap-0.5">
                            <x-nav-item href="{{ route('categories.index') }}" :active="request()->routeIs('categories.*')" icon="tag">
                                {{ __('Categories') }}
                            </x-nav-item>
                            <x-nav-item href="{{ route('products.index') }}" :active="request()->routeIs('products.*')" icon="archive-box">
                                {{ __('Products') }}
                            </x-nav-item>
                            <x-nav-item href="{{ route('warehouses.index') }}" :active="request()->routeIs('warehouses.*')" icon="building-office">
                                {{ __('Warehouses') }}
                            </x-nav-item>
                            <x-nav-item href="{{ route('locations.index') }}" :active="request()->routeIs('locations.*')" icon="map-pin">
                                {{ __('Locations') }}
                            </x-nav-item>
                            <x-nav-item href="{{ route('users.index') }}" :active="request()->routeIs('users.*')" icon="users">
                                {{ __('Users') }}
                            </x-nav-item>
                        </div>
                    </div>
                @endif

            </flux:sidebar.nav>

            <flux:spacer />

            {{-- Bottom links --}}
            <div class="border-t border-zinc-800/60 px-3 py-3">
                <a
                    href="https://github.com/Yaman69420/WareTrack"
                    target="_blank"
                    class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-zinc-500 transition hover:bg-zinc-800/60 hover:text-zinc-300"
                >
                    <flux:icon.code-bracket class="size-4 shrink-0" />
                    {{ __('Repository') }}
                </a>
            </div>

            {{-- User footer --}}
            <div class="border-t border-zinc-800/60 p-3">
                <flux:dropdown position="top" align="start" class="w-full">
                    <button class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition hover:bg-zinc-800/60">
                        <flux:avatar
                            size="sm"
                            :name="auth()->user()->name"
                            :initials="auth()->user()->initials()"
                            class="shrink-0"
                        />
                        <div class="flex min-w-0 flex-1 flex-col text-left">
                            <span class="truncate text-sm font-medium text-zinc-100">{{ auth()->user()->name }}</span>
                            <span class="truncate text-xs text-zinc-500">
                                {{ auth()->user()->isAdmin() ? __('Administrator') : __('Warehouse Worker') }}
                            </span>
                        </div>
                        <flux:icon.chevrons-up-down class="size-4 shrink-0 text-zinc-500" />
                    </button>

                    <flux:menu class="w-56">
                        <div class="flex items-center gap-3 px-3 py-2.5">
                            <flux:avatar
                                :name="auth()->user()->name"
                                :initials="auth()->user()->initials()"
                                size="sm"
                            />
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium">{{ auth()->user()->name }}</p>
                                <p class="truncate text-xs text-zinc-500">{{ auth()->user()->email }}</p>
                            </div>
                        </div>
                        <flux:menu.separator />
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                        <flux:menu.separator />
                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full cursor-pointer" data-test="logout-button">
                                {{ __('Log out') }}
                            </flux:menu.item>
                        </form>
                    </flux:menu>
                </flux:dropdown>
            </div>
        </flux:sidebar>

        {{-- ===== MOBILE HEADER ===== --}}
        <flux:header class="border-b border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950 lg:hidden">
            <flux:sidebar.toggle class="text-zinc-500 lg:hidden" icon="bars-2" inset="left" />
            <div class="flex items-center gap-2">
                <div class="flex size-6 items-center justify-center rounded bg-gradient-to-br from-blue-500 to-blue-700">
                    <x-app-logo-icon class="size-3.5 fill-current text-white" />
                </div>
                <span class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">WareTrack</span>
            </div>
            <flux:spacer />
            <flux:dropdown position="bottom" align="end">
                <flux:avatar
                    size="sm"
                    :name="auth()->user()->name"
                    :initials="auth()->user()->initials()"
                    class="cursor-pointer"
                />
                <flux:menu>
                    <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                        <flux:avatar :name="auth()->user()->name" :initials="auth()->user()->initials()" />
                        <div class="grid flex-1 text-start text-sm leading-tight">
                            <span class="truncate font-medium">{{ auth()->user()->name }}</span>
                            <span class="truncate text-xs text-zinc-500">{{ auth()->user()->email }}</span>
                        </div>
                    </div>
                    <flux:menu.separator />
                    <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                    <flux:menu.separator />
                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full cursor-pointer" data-test="logout-button">
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
