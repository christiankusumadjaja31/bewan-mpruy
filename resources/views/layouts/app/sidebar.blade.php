<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <!-- Sidebar Tanpa Garis Pemisah (Border Dihapus) -->
                <aside class="hidden lg:flex fixed inset-y-0 left-0 z-50 w-16 flex-col items-center justify-between bg-zinc-50 py-4 dark:bg-zinc-900">
            
            <!-- 1. KELOMPOK ATAS: Menu Navigasi Utama -->
            <div class="flex w-full flex-col items-center gap-3">
                <nav class="flex w-full flex-col items-center gap-2 px-3">
                    <!-- Dashboard -->
                    <a href="{{ route('dashboard') }}" wire:navigate title="Dashboard"
                       class="flex h-10 w-10 -ml-0.5 items-center justify-center rounded-lg transition {{ request()->routeIs('dashboard') ? 'text-white' : 'text-zinc-500 hover:text-zinc-300' }}">
                        <flux:icon.home class="size-5" />
                    </a>

                    <!-- Habit -->
                    <a href="{{ route('habits.index') }}" wire:navigate title="My Habits"
                       class="flex h-10 w-10 -ml-0.5 items-center justify-center rounded-lg transition {{ request()->routeIs('habits.*') ? 'text-white' : 'text-zinc-500 hover:text-zinc-300' }}">
                        <flux:icon.check-circle class="size-5" />
                    </a>

                    <!-- Challenge -->
                    <a href="{{ route('challenges.index') }}" wire:navigate title="Challenge"
                       class="flex h-10 w-10 -ml-0.5 items-center justify-center rounded-lg transition {{ request()->routeIs('challenges.*') ? 'text-white' : 'text-zinc-500 hover:text-zinc-300' }}">
                        <flux:icon.trophy class="size-5" />

                    <!-- Shop -->
                    <a href="{{ route('shop.index') }}" wire:navigate title="Shop"
                    class="flex h-10 w-10 -ml-0.5 items-center justify-center rounded-lg transition {{ request()->routeIs('shop.*') ? 'text-white' : 'text-zinc-500 hover:text-zinc-300' }}">
                        <flux:icon.shopping-bag class="size-5" />
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
        <div class="lg:pl-16">
            {{ $slot }}
        </div>

        {{-- ============================== --}}
        {{-- BOTTOM NAV (MOBILE ONLY) --}}
        {{-- ============================== --}}
        <nav class="lg:hidden fixed bottom-0 inset-x-0 z-[45] bg-zinc-950/95 backdrop-blur border-t border-zinc-800 flex items-stretch h-16">
            <a href="{{ route('dashboard') }}" wire:navigate class="flex-1 flex flex-col items-center justify-center gap-1">
                <svg class="w-5 h-5 {{ request()->routeIs('dashboard') ? 'text-zinc-100' : 'text-zinc-600' }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75" />
                </svg>
                <span class="text-[10px] {{ request()->routeIs('dashboard') ? 'text-zinc-100 font-medium' : 'text-zinc-600' }}">Dashboard</span>
            </a>

            <a href="{{ route('habits.index') }}" wire:navigate class="flex-1 flex flex-col items-center justify-center gap-1">
                <svg class="w-5 h-5 {{ request()->routeIs('habits.*') ? 'text-zinc-100' : 'text-zinc-600' }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="text-[10px] {{ request()->routeIs('habits.*') ? 'text-zinc-100 font-medium' : 'text-zinc-600' }}">Habit</span>
            </a>

            <a href="{{ route('challenges.index') }}" wire:navigate class="flex-1 flex flex-col items-center justify-center gap-1">
                <svg class="w-5 h-5 {{ request()->routeIs('challenges.*') ? 'text-zinc-100' : 'text-zinc-600' }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 01-.982-3.172M9.497 14.25a7.454 7.454 0 00.981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 007.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 002.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 012.916.52 6.003 6.003 0 01-5.395 4.972m0 0a6.726 6.726 0 01-2.749 1.35m0 0a6.772 6.772 0 01-3.044 0" />
                </svg>
                <span class="text-[10px] {{ request()->routeIs('challenges.*') ? 'text-zinc-100 font-medium' : 'text-zinc-600' }}">Challenge</span>
            </a>

            <a href="{{ route('profile.edit') }}" wire:navigate class="flex-1 flex flex-col items-center justify-center gap-1">
                <svg class="w-5 h-5 {{ request()->routeIs('profile.*') ? 'text-zinc-100' : 'text-zinc-600' }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                </svg>
                <span class="text-[10px] {{ request()->routeIs('profile.*') ? 'text-zinc-100 font-medium' : 'text-zinc-600' }}">Profile</span>
            </a>
        </nav>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>