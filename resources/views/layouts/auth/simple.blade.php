<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-950 font-sans text-zinc-300 antialiased selection:bg-emerald-500/30 selection:text-emerald-50">
        <a
            href="#auth-main"
            class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[60] focus:rounded-lg focus:bg-emerald-500 focus:px-4 focus:py-3 focus:text-sm focus:font-semibold focus:text-zinc-950"
        >
            Skip to content
        </a>

        <div class="relative flex min-h-svh flex-col overflow-hidden">
            <div class="pointer-events-none absolute inset-x-0 -top-48 mx-auto h-[26rem] max-w-2xl bg-[radial-gradient(ellipse_at_center,rgba(16,185,129,0.14),transparent_65%)]" aria-hidden="true"></div>

            <header class="relative mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-6 sm:py-8">
                <a
                    href="{{ route('home') }}"
                    class="inline-flex min-h-11 items-center gap-2.5 rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-950"
                    aria-label="{{ config('app.name') }} home"
                >
                    @if (file_exists(public_path('images/nudge-mark.png')))
                        <img src="{{ asset('images/nudge-mark.png') }}" alt="" class="h-6 w-auto" />
                    @else
                        <span class="flex size-8 items-center justify-center rounded-lg border border-zinc-800 bg-zinc-900">
                            <span class="font-heading text-xs font-bold leading-none text-emerald-400">N</span>
                        </span>
                    @endif
                    <span class="font-heading text-[17px] font-semibold tracking-tight text-zinc-50">{{ config('app.name') }}</span>
                </a>

                <a
                    href="{{ route('home') }}"
                    class="inline-flex min-h-11 items-center rounded-lg px-2 text-sm text-zinc-500 transition hover:text-zinc-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-950"
                >
                    Back to home
                </a>
            </header>

            <main id="auth-main" class="relative flex flex-1 items-center justify-center px-6 pb-12 pt-4 sm:pb-16">
                <div class="w-full max-w-[27rem]">
                    <div class="rounded-2xl border border-zinc-800/80 bg-zinc-900/80 p-6 shadow-2xl shadow-black/40 sm:p-8">
                        {{ $slot }}
                    </div>

                    <p class="mt-6 text-center text-[13px] text-zinc-600">Habits that stick, kept together.</p>
                </div>
            </main>

            <footer class="relative border-t border-zinc-800/60">
                <div class="mx-auto flex w-full max-w-6xl flex-col items-center justify-between gap-1 px-6 py-5 text-[13px] text-zinc-600 sm:flex-row">
                    <span>&copy; {{ date('Y') }} {{ config('app.name') }}</span>
                    <span>Free while in beta</span>
                </div>
            </footer>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
