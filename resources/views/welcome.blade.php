<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark scroll-smooth">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />

        <title>Nudge — habits that stick, kept together</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600|space-grotesk:500,600,700" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @fluxAppearance

        <style>
            @keyframes rise {
                from { opacity: 0; transform: translateY(14px); }
                to { opacity: 1; transform: none; }
            }

            .rise {
                animation: rise 0.7s cubic-bezier(0.22, 0.61, 0.36, 1) both;
            }

            .rise-delay-1 { animation-delay: 0.08s; }
            .rise-delay-2 { animation-delay: 0.16s; }

            html {
                -webkit-tap-highlight-color: transparent;
            }

            @media (prefers-reduced-motion: reduce) {
                .rise {
                    animation: none;
                }

                html {
                    scroll-behavior: auto;
                }
            }
        </style>
    </head>

    <body class="min-h-screen bg-zinc-950 font-sans text-zinc-300 antialiased selection:bg-emerald-500/30 selection:text-emerald-50">
        <a
            href="#main"
            class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[60] focus:rounded-lg focus:bg-emerald-500 focus:px-4 focus:py-3 focus:text-sm focus:font-semibold focus:text-zinc-950"
        >
            Skip to content
        </a>

        {{-- ===================== --}}
        {{-- Header --}}
        {{-- ===================== --}}
        <header class="sticky top-0 z-50 border-b border-zinc-800/60 bg-zinc-950/80 backdrop-blur-xl backdrop-saturate-150">
            <div class="mx-auto flex h-16 w-full max-w-6xl items-center justify-between gap-4 px-6 lg:px-8">
                <a
                    href="{{ route('home') }}"
                    class="inline-flex min-h-11 items-center gap-2.5 rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-950"
                    aria-label="Nudge home"
                >
                    {{-- Brand mark: public/images/nudge-mark.png (transparent mark). Falls back to the starter mark. --}}
                    @if (file_exists(public_path('images/nudge-mark.png')))
                        <img src="{{ asset('images/nudge-mark.png') }}" alt="" class="h-7 w-auto" />
                    @else
                        <span class="flex size-9 items-center justify-center rounded-xl border border-zinc-800 bg-zinc-900">
                            <span class="font-heading text-base font-bold leading-none text-emerald-400">N</span>
                        </span>
                    @endif
                    <span class="font-heading text-[17px] font-semibold tracking-tight text-zinc-50">Nudge</span>
                </a>

                <nav class="hidden items-center gap-1 md:flex" aria-label="Main">
                    <a href="#how" class="inline-flex min-h-11 items-center rounded-lg px-3 text-sm text-zinc-400 transition hover:text-zinc-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-950">How it works</a>
                    <a href="#features" class="inline-flex min-h-11 items-center rounded-lg px-3 text-sm text-zinc-400 transition hover:text-zinc-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-950">Features</a>
                    <a href="#together" class="inline-flex min-h-11 items-center rounded-lg px-3 text-sm text-zinc-400 transition hover:text-zinc-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-950">Challenges</a>
                </nav>

                <div class="flex items-center gap-1.5">
                    @auth
                        <a href="{{ route('dashboard') }}" class="inline-flex min-h-11 items-center rounded-lg px-3 text-sm text-zinc-300 transition hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-950">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center rounded-lg px-3 text-sm text-zinc-300 transition hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-950">Log in</a>

                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-emerald-500 px-4 text-sm font-semibold text-zinc-950 transition hover:bg-emerald-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-300 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-950">
                                Get started
                            </a>
                        @endif
                    @endauth
                </div>
            </div>
        </header>

        <main id="main">
            {{-- ===================== --}}
            {{-- Hero --}}
            {{-- ===================== --}}
            <section class="relative overflow-hidden" aria-labelledby="hero-title">
                <div class="pointer-events-none absolute inset-x-0 -top-40 mx-auto h-[520px] max-w-4xl bg-[radial-gradient(ellipse_at_center,rgba(16,185,129,0.16),transparent_65%)]" aria-hidden="true"></div>

                <div class="relative mx-auto grid w-full max-w-6xl items-center gap-16 px-6 pb-20 pt-16 lg:grid-cols-[minmax(0,1.3fr)_minmax(0,0.7fr)] lg:gap-12 lg:px-8 lg:pb-28 lg:pt-24">
                    <div class="rise">
                        <p class="inline-flex items-center gap-2 rounded-full border border-zinc-800 bg-zinc-900/60 py-1.5 pl-1.5 pr-3 text-xs text-zinc-400">
                            <span class="rounded-full bg-emerald-500/15 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wider text-emerald-400">New</span>
                            Shared challenges
                        </p>

                        <h1 id="hero-title" class="mt-6 font-heading text-4xl font-bold leading-[1.05] tracking-tight text-zinc-50 sm:text-5xl xl:text-6xl">
                            Build habits.<br class="hidden sm:block">
                            Keep them together.
                        </h1>

                        <p class="mt-6 max-w-lg text-pretty text-[17px] leading-7 text-zinc-400">
                            A quiet habit tracker for people who are tired of starting over alone. Track your day, keep the streak alive, and let a few friends keep you honest.
                        </p>

                        <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="inline-flex h-12 items-center justify-center rounded-xl bg-emerald-500 px-6 text-[15px] font-semibold text-zinc-950 transition hover:bg-emerald-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-300 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-950">
                                    Start tracking — it's free
                                </a>
                            @endif

                            <a href="#how" class="inline-flex h-12 items-center justify-center rounded-xl border border-zinc-800 bg-zinc-900/60 px-6 text-[15px] font-medium text-zinc-200 transition hover:border-zinc-700 hover:bg-zinc-800/70 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-950">
                                See how it works
                            </a>
                        </div>

                        <p class="mt-5 text-[13px] text-zinc-500">No card required · Set up in under a minute</p>
                    </div>

                    {{-- Product preview --}}
                    <div class="rise rise-delay-2 relative">
                        <figure role="img" aria-label="Preview of the Nudge dashboard: five habits for today, three checked off, a 21-day streak, and a challenge leaderboard with three friends." class="relative">
                            <div aria-hidden="true" class="mx-auto w-full max-w-md">
                                <div class="rounded-2xl border border-zinc-800/80 bg-zinc-900 p-5 shadow-2xl shadow-black/50">
                                    <div class="mb-4 flex items-center justify-between">
                                        <div>
                                            <p class="text-[11px] uppercase tracking-wider text-zinc-500">Today</p>
                                            <p class="font-heading text-lg font-semibold text-zinc-100">Tuesday, 18 Nov</p>
                                        </div>
                                        <span class="rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2.5 py-1 text-xs font-semibold text-emerald-400">3 of 5</span>
                                    </div>

                                    @php
                                        $habits = [
                                            ['Read 20 pages', '14 days', true],
                                            ['Morning run', '9 days', true],
                                            ['Meditate 10 min', '3 days', true],
                                            ['Journal', 'New', false],
                                            ['No phone before 9', '21 days', false],
                                        ];
                                    @endphp

                                    <ul class="space-y-2">
                                        @foreach ($habits as [$label, $streak, $done])
                                            <li class="flex items-center gap-3 rounded-xl border border-zinc-800/60 bg-zinc-950/40 px-3.5 py-3">
                                                <span class="flex size-6 shrink-0 items-center justify-center rounded-full border {{ $done ? 'border-emerald-500 bg-emerald-500 text-zinc-950' : 'border-zinc-700' }}">
                                                    @if ($done)
                                                        <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                                            <path d="M4.5 12.75l6 6 9-13.5" />
                                                        </svg>
                                                    @endif
                                                </span>
                                                <span class="flex-1 truncate text-sm {{ $done ? 'text-zinc-400' : 'text-zinc-200' }}">{{ $label }}</span>
                                                <span class="shrink-0 text-[11px] text-zinc-600">{{ $streak }}</span>
                                            </li>
                                        @endforeach
                                    </ul>

                                    <div class="mt-4 flex items-center justify-between rounded-xl border border-orange-500/20 bg-orange-500/10 px-3.5 py-2.5">
                                        <span class="flex items-center gap-2 text-xs font-medium text-orange-400">
                                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M12 12c2-2.96 0-7-1-8 0 3.038-1.773 4.741-3 6-1.226 1.26-2 3.24-2 5a6 6 0 1 0 12 0c0-1.532-1.056-3.94-2-5-1.786 3-2.791 3-4 2z" />
                                            </svg>
                                            21-day streak
                                        </span>
                                        <span class="text-[11px] text-zinc-500">Best ever</span>
                                    </div>
                                </div>

                                {{-- Challenge leaderboard --}}
                                <div class="relative z-10 mx-auto -mt-4 w-[85%] rounded-2xl border border-zinc-700/60 bg-zinc-900/95 p-4 shadow-2xl shadow-black/60 backdrop-blur-xl sm:-mt-6 sm:w-[78%] sm:translate-x-6">
                                    <div class="mb-3 flex items-center justify-between">
                                        <p class="font-heading text-sm font-semibold text-zinc-100">Morning Club</p>
                                        <span class="rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-emerald-400">Active</span>
                                    </div>

                                    @php
                                        $board = [
                                            ['M', 'Maya', 260, false, 'bg-emerald-300 text-emerald-900'],
                                            ['Y', 'You', 240, true, 'bg-blue-300 text-blue-900'],
                                            ['S', 'Sam', 190, false, 'bg-rose-300 text-rose-900'],
                                        ];
                                    @endphp

                                    <ul class="space-y-1.5">
                                        @foreach ($board as $index => [$initial, $name, $points, $isMe, $avatar])
                                            <li class="flex items-center gap-2.5 rounded-lg px-2 py-1.5 {{ $isMe ? 'bg-emerald-500/10 ring-1 ring-emerald-500/25' : '' }}">
                                                <span class="w-3 text-[10px] font-semibold text-zinc-600">{{ $index + 1 }}</span>
                                                <span class="flex size-6 items-center justify-center rounded-full font-heading text-[11px] font-bold {{ $avatar }}">{{ $initial }}</span>
                                                <span class="flex-1 truncate text-xs {{ $isMe ? 'font-semibold text-zinc-100' : 'text-zinc-400' }}">{{ $name }}</span>
                                                <span class="text-[11px] tabular-nums {{ $isMe ? 'text-emerald-400' : 'text-zinc-500' }}">{{ $points }} pts</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </figure>
                    </div>
                </div>

                {{-- Value band --}}
                <div class="relative border-y border-zinc-800/60 bg-zinc-900/30">
                    <dl class="mx-auto grid w-full max-w-6xl grid-cols-1 divide-y divide-zinc-800/60 px-6 sm:grid-cols-3 sm:divide-x sm:divide-y-0 lg:px-8">
                        @php
                            $values = [
                                ['Two taps to check in', 'The whole day, done in seconds.'],
                                ['Streaks you can freeze', 'Miss a day without losing progress.'],
                                ['Friends on the board', 'Accountability, with names on it.'],
                            ];
                        @endphp

                        @foreach ($values as [$title, $description])
                            <div class="py-6 sm:px-6 sm:first:pl-0 sm:last:pr-0">
                                <dt class="font-heading text-[15px] font-semibold text-zinc-100">{{ $title }}</dt>
                                <dd class="mt-1 text-sm text-zinc-500">{{ $description }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            </section>

            {{-- ===================== --}}
            {{-- How it works --}}
            {{-- ===================== --}}
            <section id="how" class="scroll-mt-24 border-b border-zinc-800/60" aria-labelledby="how-title">
                <div class="mx-auto w-full max-w-6xl px-6 py-20 lg:px-8 lg:py-28">
                    <div class="max-w-2xl">
                        <p class="text-xs font-medium uppercase tracking-[0.18em] text-emerald-400">How it works</p>
                        <h2 id="how-title" class="mt-3 font-heading text-3xl font-semibold tracking-tight text-balance text-zinc-50 sm:text-4xl">
                            Three small steps. Then just show up.
                        </h2>
                        <p class="mt-4 text-[17px] leading-7 text-zinc-400">
                            No elaborate setup, no dashboards to configure. Pick what matters, invite someone, and let the days add up.
                        </p>
                    </div>

                    <ol class="mt-14 grid gap-10 sm:grid-cols-3 sm:gap-8">
                        @php
                            $steps = [
                                ['01', 'Name your habits', 'Daily or weekly, with a target you can actually hit. One or two is plenty to start.'],
                                ['02', 'Open a challenge', 'Set a window, put points on each check-in, and share the code with a few friends.'],
                                ['03', 'Check in together', 'Everyone sees the same numbers. The board sorts itself out every evening.'],
                            ];
                        @endphp

                        @foreach ($steps as [$number, $title, $description])
                            <li class="border-t border-zinc-800 pt-5">
                                <span class="font-heading text-sm font-semibold tabular-nums text-emerald-400">{{ $number }}</span>
                                <h3 class="mt-2 font-heading text-lg font-semibold text-zinc-100">{{ $title }}</h3>
                                <p class="mt-2 max-w-sm text-[15px] leading-relaxed text-zinc-400">{{ $description }}</p>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </section>

            {{-- ===================== --}}
            {{-- Features --}}
            {{-- ===================== --}}
            <section id="features" class="scroll-mt-24 border-b border-zinc-800/60" aria-labelledby="features-title">
                <div class="mx-auto w-full max-w-6xl px-6 py-20 lg:px-8 lg:py-28">
                    <div class="max-w-2xl">
                        <p class="text-xs font-medium uppercase tracking-[0.18em] text-emerald-400">Features</p>
                        <h2 id="features-title" class="mt-3 font-heading text-3xl font-semibold tracking-tight text-balance text-zinc-50 sm:text-4xl">
                            Enough detail to be useful. Nothing more.
                        </h2>
                    </div>

                    <div class="mt-14 grid gap-4 lg:grid-cols-3">
                        {{-- Consistency --}}
                        <div class="rounded-2xl border border-zinc-800/80 bg-zinc-900 p-6 lg:col-span-2 lg:p-8">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h3 class="font-heading text-lg font-semibold text-zinc-100">Momentum you can see</h3>
                                    <p class="mt-2 max-w-md text-[15px] leading-relaxed text-zinc-400">
                                        Sixteen weeks of consistency at a glance. Not a score to chase — just an honest picture of how the year is going.
                                    </p>
                                </div>
                                <span class="hidden shrink-0 items-center gap-1.5 rounded-full border border-zinc-800 bg-zinc-950 px-2.5 py-1 text-[11px] text-zinc-400 sm:inline-flex">
                                    <span class="size-1.5 rounded-full bg-emerald-400"></span>
                                    112 days
                                </span>
                            </div>

                            @php
                                $levels = ['bg-zinc-800', 'bg-emerald-900', 'bg-emerald-700', 'bg-emerald-500', 'bg-emerald-400'];
                                $heatmap = [];
                                for ($i = 0; $i < 112; $i++) {
                                    $heatmap[] = match (true) {
                                        $i > 104 => -1,
                                        $i % 13 === 5 => 0,
                                        $i % 7 === 0 => 1,
                                        $i % 4 === 0 => 2,
                                        $i % 3 === 0 => 3,
                                        default => 4,
                                    };
                                }
                            @endphp

                            <div aria-hidden="true" class="mt-8 grid gap-8 sm:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)] sm:items-end">
                                {{-- 16 weeks of consistency --}}
                                <div>
                                <div class="grid max-w-md grid-flow-col grid-rows-7 gap-[3px]" style="grid-template-columns: repeat(16, minmax(0, 1fr));">
                                    @foreach ($heatmap as $level)
                                        <div class="aspect-square rounded-[3px] {{ $level < 0 ? 'bg-transparent' : $levels[$level] }}"></div>
                                    @endforeach
                                </div>

                                <div class="mt-4 flex max-w-md items-center justify-between">
                                    <p class="text-[11px] text-zinc-600">16 weeks ago</p>
                                    <div class="flex items-center gap-1.5 text-[10px] text-zinc-600">
                                        <span>Less</span>
                                        @foreach ($levels as $level)
                                            <span class="size-2.5 rounded-[3px] {{ $level }}"></span>
                                        @endforeach
                                        <span>More</span>
                                    </div>
                                </div>
                                </div>

                                <dl class="space-y-3">
                                    @foreach ([['Active days', '84 of 112'], ['Current streak', '21 days'], ['Best week', '6 of 7']] as [$label, $value])
                                        <div class="flex items-baseline justify-between gap-4 border-b border-zinc-800/60 pb-3 last:border-0 last:pb-0">
                                            <dt class="text-xs text-zinc-500">{{ $label }}</dt>
                                            <dd class="font-heading text-sm font-semibold text-zinc-100">{{ $value }}</dd>
                                        </div>
                                    @endforeach
                                </dl>
                            </div>
                        </div>

                        {{-- Freeze --}}
                        <div class="flex flex-col rounded-2xl border border-zinc-800/80 bg-zinc-900 p-6 lg:p-8">
                            <span class="flex size-10 items-center justify-center rounded-xl border border-sky-500/20 bg-sky-500/10">
                                <svg class="size-5 text-sky-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M12 3v18M4.2 7.5l15.6 9M19.8 7.5l-15.6 9" />
                                </svg>
                            </span>
                            <h3 class="mt-5 font-heading text-lg font-semibold text-zinc-100">Streaks that bend</h3>
                            <p class="mt-2 text-[15px] leading-relaxed text-zinc-400">
                                Life happens. Spend a freeze instead of starting from zero — three a month, already yours.
                            </p>

                            <div class="mt-auto pt-8" aria-hidden="true">
                                <div class="flex gap-1.5">
                                    @foreach (['M', 'T', 'W', 'T', 'F', 'S', 'S'] as $index => $initial)
                                        <div class="flex flex-1 flex-col items-center gap-2">
                                            <span class="flex h-9 w-full items-center justify-center rounded-lg text-[10px] font-semibold
                                                {{ $index < 2 ? 'bg-emerald-500/15 text-emerald-400' : '' }}
                                                {{ $index === 2 ? 'bg-sky-500/70 text-zinc-950' : '' }}
                                                {{ $index > 2 ? 'bg-zinc-800 text-zinc-600' : '' }}">
                                                @if ($index === 2)
                                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M12 3v18M4.2 7.5l15.6 9M19.8 7.5l-15.6 9" />
                                                    </svg>
                                                @else
                                                    <span class="size-1.5 rounded-full {{ $index < 2 ? 'bg-emerald-400' : 'bg-zinc-700' }}"></span>
                                                @endif
                                            </span>
                                            <span class="text-[10px] text-zinc-600">{{ $initial }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        {{-- Leaderboard --}}
                        <div class="flex flex-col rounded-2xl border border-zinc-800/80 bg-zinc-900 p-6 lg:p-8">
                            <span class="flex size-10 items-center justify-center rounded-xl border border-amber-500/20 bg-amber-500/10">
                                <svg class="size-5 text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M8 21h8M12 17v4M7 4h10v5a5 5 0 0 1-10 0V4Z" />
                                    <path d="M17 5h2.5a2.5 2.5 0 0 1 0 5H17M7 5H4.5a2.5 2.5 0 0 0 0 5H7" />
                                </svg>
                            </span>
                            <h3 class="mt-5 font-heading text-lg font-semibold text-zinc-100">Points with names on them</h3>
                            <p class="mt-2 text-[15px] leading-relaxed text-zinc-400">
                                Check-ins earn points inside each challenge, ranked among people you know. No strangers, no noise.
                            </p>

                            <ul class="mt-auto space-y-1.5 pt-8" aria-hidden="true">
                                @foreach ($board as $index => [$initial, $name, $points, $isMe, $avatar])
                                    <li class="flex items-center gap-2.5 rounded-lg px-2 py-1.5 {{ $isMe ? 'bg-emerald-500/10 ring-1 ring-emerald-500/25' : '' }}">
                                        <span class="w-3 text-[10px] font-semibold text-zinc-600">{{ $index + 1 }}</span>
                                        <span class="flex size-6 items-center justify-center rounded-full font-heading text-[11px] font-bold {{ $avatar }}">{{ $initial }}</span>
                                        <span class="flex-1 truncate text-xs {{ $isMe ? 'font-semibold text-zinc-100' : 'text-zinc-400' }}">{{ $name }}</span>
                                        <span class="text-[11px] tabular-nums {{ $isMe ? 'text-emerald-400' : 'text-zinc-500' }}">{{ $points }} pts</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        {{-- Calendar --}}
                        <div class="rounded-2xl border border-zinc-800/80 bg-zinc-900 p-6 lg:col-span-2 lg:p-8">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <h3 class="font-heading text-lg font-semibold text-zinc-100">Every habit keeps its own record</h3>
                                    <p class="mt-2 max-w-md text-[15px] leading-relaxed text-zinc-400">
                                        A month view for each habit — completed, frozen, or still ahead of you. Tap a day to see what happened.
                                    </p>
                                </div>

                                <div aria-hidden="true" class="flex items-center gap-2 rounded-xl border border-zinc-800 bg-zinc-950 px-3 py-2">
                                    <span class="flex size-4 items-center justify-center rounded-full bg-emerald-500"></span>
                                    <span class="text-[11px] text-zinc-400">Completed</span>
                                    <span class="ml-2 flex size-4 items-center justify-center rounded-full bg-sky-500/70"></span>
                                    <span class="text-[11px] text-zinc-400">Frozen</span>
                                </div>
                            </div>

                            <div class="mt-6 grid gap-8 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] sm:items-center lg:mt-8">
                            <div aria-hidden="true" class="grid max-w-[19rem] grid-cols-7 gap-y-2 text-center">
                                @foreach (['S', 'M', 'T', 'W', 'T', 'F', 'S'] as $day)
                                    <p class="text-[10px] font-medium text-zinc-600">{{ $day }}</p>
                                @endforeach

                                @for ($day = 1; $day <= 28; $day++)
                                    @php
                                        $state = match (true) {
                                            in_array($day, [26, 27, 28], true) => 'future',
                                            in_array($day, [4, 11, 19], true) => 'frozen',
                                            in_array($day, [1, 2, 3, 5, 6, 7, 8, 10, 12, 13, 14, 15, 17, 18], true) => 'done',
                                            default => 'open',
                                        };
                                    @endphp

                                    <div class="flex justify-center">
                                        <span class="flex size-7 items-center justify-center rounded-full text-[11px] tabular-nums
                                            {{ $state === 'done' ? 'bg-emerald-500 font-semibold text-zinc-950' : '' }}
                                            {{ $state === 'frozen' ? 'bg-sky-500/70 font-semibold text-zinc-950' : '' }}
                                            {{ $state === 'open' ? 'text-zinc-600' : '' }}
                                            {{ $state === 'future' ? 'text-zinc-700' : '' }}">
                                            {{ $day }}
                                        </span>
                                    </div>
                                @endfor
                            </div>

                            <dl class="space-y-3" aria-hidden="true">
                                @foreach ([['This month', '14 of 18'], ['Longest streak', '21 days'], ['Freezes used', '2 of 3'], ['Challenges won', '3']] as [$label, $value])
                                    <div class="flex items-baseline justify-between gap-4 border-b border-zinc-800/60 pb-3 last:border-0 last:pb-0">
                                        <dt class="text-xs text-zinc-500">{{ $label }}</dt>
                                        <dd class="font-heading text-sm font-semibold text-zinc-100">{{ $value }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- ===================== --}}
            {{-- Together / CTA --}}
            {{-- ===================== --}}
            <section id="together" class="scroll-mt-24" aria-labelledby="cta-title">
                <div class="mx-auto w-full max-w-6xl px-6 py-20 lg:px-8 lg:py-28">
                    <div class="relative overflow-hidden rounded-3xl border border-zinc-800/80 bg-zinc-900 px-6 py-16 text-center sm:px-14">
                        <div class="pointer-events-none absolute inset-x-0 -top-24 mx-auto h-64 max-w-xl bg-[radial-gradient(ellipse_at_center,rgba(16,185,129,0.18),transparent_70%)]" aria-hidden="true"></div>

                        <div class="relative">
                            <div class="flex items-center justify-center" aria-hidden="true">
                                <div class="flex -space-x-2">
                                    @foreach ([['M', 'bg-emerald-300 text-emerald-900'], ['A', 'bg-blue-300 text-blue-900'], ['R', 'bg-purple-300 text-purple-900'], ['+', 'bg-zinc-800 text-zinc-300']] as [$initial, $classes])
                                        <span class="flex size-9 items-center justify-center rounded-full border-2 border-zinc-900 font-heading text-xs font-bold {{ $classes }}">{{ $initial }}</span>
                                    @endforeach
                                </div>
                            </div>

                            <h2 id="cta-title" class="mt-6 font-heading text-3xl font-semibold tracking-tight text-balance text-zinc-50 sm:text-4xl">
                                Start with one habit. Add one friend.
                            </h2>
                            <p class="mx-auto mt-4 max-w-md text-pretty text-[17px] leading-7 text-zinc-400">
                                Keep a challenge small and a little public. That's usually enough.
                            </p>

                            <div class="mt-9 flex flex-col items-center justify-center gap-3 sm:flex-row">
                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}" class="inline-flex h-12 w-full items-center justify-center rounded-xl bg-emerald-500 px-6 text-[15px] font-semibold text-zinc-950 transition hover:bg-emerald-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-300 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-900 sm:w-auto">
                                        Create your account
                                    </a>
                                @endif

                                <a href="{{ route('login') }}" class="inline-flex h-12 w-full items-center justify-center rounded-xl border border-zinc-800 bg-zinc-950/60 px-6 text-[15px] font-medium text-zinc-200 transition hover:border-zinc-700 hover:bg-zinc-800/70 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-900 sm:w-auto">
                                    Log in
                                </a>
                            </div>

                            <p class="mt-5 text-[13px] text-zinc-500">Free while in beta</p>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        {{-- ===================== --}}
        {{-- Footer --}}
        {{-- ===================== --}}
        <footer class="border-t border-zinc-800/60">
            <div class="mx-auto flex w-full max-w-6xl flex-col gap-8 px-6 py-12 sm:flex-row sm:items-center sm:justify-between lg:px-8">
                <div class="flex items-center gap-2.5">
                    @if (file_exists(public_path('images/nudge-mark.png')))
                        <img src="{{ asset('images/nudge-mark.png') }}" alt="" class="h-5 w-auto" />
                    @else
                        <span class="flex size-8 items-center justify-center rounded-lg border border-zinc-800 bg-zinc-900">
                            <span class="font-heading text-xs font-bold leading-none text-emerald-400">N</span>
                        </span>
                    @endif
                    <span class="font-heading text-sm font-semibold tracking-tight text-zinc-200">Nudge</span>
                </div>

                <nav class="flex flex-wrap items-center gap-x-1 gap-y-2" aria-label="Footer">
                    <a href="#how" class="inline-flex min-h-11 items-center rounded-lg px-2.5 text-sm text-zinc-500 transition hover:text-zinc-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-950">How it works</a>
                    <a href="#features" class="inline-flex min-h-11 items-center rounded-lg px-2.5 text-sm text-zinc-500 transition hover:text-zinc-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-950">Features</a>
                    @auth
                        <a href="{{ route('dashboard') }}" class="inline-flex min-h-11 items-center rounded-lg px-2.5 text-sm text-zinc-500 transition hover:text-zinc-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-950">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center rounded-lg px-2.5 text-sm text-zinc-500 transition hover:text-zinc-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-950">Log in</a>
                    @endauth
                </nav>

                <p class="text-[13px] text-zinc-600">&copy; {{ date('Y') }} Nudge</p>
            </div>
        </footer>
    </body>
</html>
