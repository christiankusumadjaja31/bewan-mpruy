<x-layouts::auth :title="__('Log in')">
    <div class="flex flex-col gap-7">
        <x-auth-header
            :title="__('Welcome back')"
            :description="__('Log in to pick up your streaks where you left off.')"
        />

        <x-auth-session-status :status="session('status')" />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-5">
            @csrf

            <x-auth-input
                name="email"
                :label="__('Email')"
                type="email"
                :value="old('email')"
                autocomplete="email"
                placeholder="you@example.com"
                required
                autofocus
            />

            <div>
                <x-auth-input
                    name="password"
                    :label="__('Password')"
                    type="password"
                    autocomplete="current-password"
                    :placeholder="__('Your password')"
                    required
                />

                @if (Route::has('password.request'))
                    <div class="mt-2 flex justify-end">
                        <a
                            href="{{ route('password.request') }}"
                            class="inline-flex min-h-9 items-center rounded-lg text-[13px] text-zinc-500 transition hover:text-emerald-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-900"
                        >
                            {{ __('Forgot password?') }}
                        </a>
                    </div>
                @endif
            </div>

            <label class="flex min-h-11 cursor-pointer items-center gap-3">
                <span class="relative flex size-4.5 items-center justify-center">
                    <input
                        type="checkbox"
                        name="remember"
                        @checked(old('remember'))
                        class="peer size-4.5 appearance-none rounded-md border border-zinc-700 bg-zinc-950 transition checked:border-emerald-500 checked:bg-emerald-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-900"
                    >
                    <svg
                        class="pointer-events-none absolute size-3 text-zinc-950 opacity-0 transition peer-checked:opacity-100"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"
                    >
                        <path d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                </span>
                <span class="text-sm text-zinc-400">{{ __('Keep me signed in') }}</span>
            </label>

            <button
                type="submit"
                data-test="login-button"
                class="inline-flex h-12 w-full items-center justify-center rounded-xl bg-emerald-500 text-[15px] font-semibold text-zinc-950 transition hover:bg-emerald-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-300 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-900"
            >
                {{ __('Log in') }}
            </button>
        </form>

        @if (Route::has('register'))
            <div class="border-t border-zinc-800/80 pt-6">
                <p class="text-center text-sm text-zinc-500">
                    {{ __('New to') }} {{ config('app.name') }}?
                    <a
                        href="{{ route('register') }}"
                        class="font-medium text-emerald-400 transition hover:text-emerald-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-900"
                    >
                        {{ __('Create an account') }}
                    </a>
                </p>
            </div>
        @endif
    </div>
</x-layouts::auth>
