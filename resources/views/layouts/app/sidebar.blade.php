<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Inicio')" class="grid">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Parámetros')">
                    <flux:sidebar.item icon="calendar" :href="route('gestiones.index')" :current="request()->routeIs('gestiones')" wire:navigate>
                        {{ __('Gestiones') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="scale" :href="route('antiguedades.index')" :current="request()->routeIs('antiguedades')" wire:navigate>
                        {{ __('Antigüedad') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="globe-americas" :href="route('feriados.index')" :current="request()->routeIs('feriados.index')" wire:navigate>
                        {{ __('Feriados') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="shield-check" :href="route('users.index')" :current="request()->routeIs('users.index')" wire:navigate>
                        {{ __('Usuarios') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Operaciones')">
                    <flux:sidebar.item icon="users" :href="route('empleados.index')" :current="request()->routeIs('empleados')" wire:navigate>
                        {{ __('Empleados') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="calendar-days" :href="route('vacaciones.index')" :current="request()->routeIs('vacaciones.index')" wire:navigate>
                       {{ __('Vacaciones') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="clipboard-document-check" :href="route('solicitudes-vacaciones.index')" :current="request()->routeIs('solicitudes-vacaciones.index')" wire:navigate>
                       {{ __('Sol. de Vacación') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="clock" :href="route('compensaciones.index')" :current="request()->routeIs('compensaciones.index')" wire:navigate>
                       {{ __('Compensaciones') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="clipboard-document-check" :href="route('solicitudes-compensaciones.index')" :current="request()->routeIs('solicitudes-compensaciones.index')" wire:navigate>
                       {{ __('Sol. de Compensación') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="finger-print" :href="route('actividades.index')" :current="request()->routeIs('actividades.index')" wire:navigate>
                       {{ __('Actividad') }}
                    </flux:sidebar.item>
                    </flux:sidebar.group>

            </flux:sidebar.nav>

            <flux:spacer />

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @livewireScripts
        @fluxScripts
    </body>
</html>
