<x-layouts::auth :title="__('Forgot password')">
    <div class="flex flex-col gap-7">
        <x-auth-header
            :title="__('Forgot password')"
            :description="__('Enter your email and we\'ll send you a link to reset it.')"
        />

        <x-auth-session-status :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-5">
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

            <button
                type="submit"
                data-test="email-password-reset-link-button"
                class="inline-flex h-12 w-full items-center justify-center rounded-xl bg-emerald-500 text-[15px] font-semibold text-zinc-950 transition hover:bg-emerald-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-300 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-900"
            >
                {{ __('Email password reset link') }}
            </button>
        </form>

        <div class="border-t border-zinc-800/80 pt-6">
            <p class="text-center text-sm text-zinc-500">
                {{ __('Or, return to') }}
                <a
                    href="{{ route('login') }}"
                    class="font-medium text-emerald-400 transition hover:text-emerald-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-900"
                >
                    {{ __('log in') }}
                </a>
            </p>
        </div>
    </div>
</x-layouts::auth>
