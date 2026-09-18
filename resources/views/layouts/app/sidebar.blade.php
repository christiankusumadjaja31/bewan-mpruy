<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <!-- Sidebar Tanpa Garis Pemisah (Border Dihapus) -->
        <aside class="fixed inset-y-0 left-0 z-50 flex w-16 flex-col items-center justify-between bg-zinc-50 py-4 dark:bg-zinc-900">
            
            <!-- 1. KELOMPOK ATAS: Menu Navigasi Utama -->
            <div class="flex w-full flex-col items-center gap-3">
                <nav class="flex w-full flex-col items-center gap-2 px-3">
                    <!-- Dashboard -->
                    <a href="{{ route('dashboard') }}" wire:navigate title="Dashboard"
                       class="flex h-10 w-10 -ml-0.5 items-center justify-center rounded-lg transition {{ request()->routeIs('dashboard') ? 'bg-zinc-800 text-white' : 'text-zinc-400 hover:bg-zinc-800/50 hover:text-zinc-200' }}">
                        <flux:icon.home class="size-5" />
                    </a>

                    <!-- Habit -->
                    <a href="{{ route('habits.index') }}" wire:navigate title="My Habits"
                       class="flex h-10 w-10 -ml-0.5 items-center justify-center rounded-lg transition {{ request()->routeIs('habits.*') ? 'bg-zinc-800 text-white' : 'text-zinc-400 hover:bg-zinc-800/50 hover:text-zinc-200' }}">
                        <flux:icon.check-circle class="size-5" />
                    </a>

                    <!-- Challenge -->
                    <a href="{{ route('challenges.index') }}" wire:navigate title="Challenge"
                       class="flex h-10 w-10 -ml-0.5 items-center justify-center rounded-lg transition {{ request()->routeIs('challenges.*') ? 'bg-zinc-800 text-white' : 'text-zinc-400 hover:bg-zinc-800/50 hover:text-zinc-200' }}">
                        <flux:icon.trophy class="size-5" />
                    </a>
                </nav>
            </div>

            <!-- 2. KELOMPOK BAWAH: Tombol Profil (Dropdown Settings & Logout) -->
            <div class="flex w-full justify-center px-3">
                <flux:dropdown position="right" align="end">
                    <button title="Profile & Options" class="flex h-10 w-10 -ml-0.5 items-center justify-center rounded-lg bg-zinc-800 text-xs font-medium text-white transition hover:bg-zinc-800/50 hover:text-zinc-200 focus:outline-none">
                        {{ auth()->user()->initials() }}
                    </button>

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

                        <!-- Menu Settings -->
                        <flux:menu.radio.group>
                            <flux:menu.item :href="route('profile.edit')" icon="wrench-screwdriver" wire:navigate>
                                {{ __('Settings') }}
                            </flux:menu.item>
                        </flux:menu.radio.group>

                        <flux:menu.separator />

                        <!-- Tombol Log Out -->
                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <flux:menu.item
                                as="button"
                                type="submit"
                                icon="arrow-right-start-on-rectangle"
                                class="w-full cursor-pointer text-red-400 hover:text-red-500"
                                data-test="logout-button"
                            >
                                {{ __('Log out') }}
                            </flux:menu.item>
                        </form>
                    </flux:menu>
                </flux:dropdown>
            </div>
        </aside>

        <!-- Konten Utama -->
        <div class="pl-16">
            {{ $slot }}
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>