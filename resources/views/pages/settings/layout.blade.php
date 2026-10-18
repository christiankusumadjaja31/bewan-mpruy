@php $active = $active ?? 'profile'; @endphp

<div class="fixed top-0 bottom-0 right-0 left-0 lg:left-16 z-40 bg-zinc-950 overflow-y-auto">
    <div class="max-w-2xl mx-auto px-4 py-6 lg:px-8 lg:py-8 pb-32 lg:pb-16">

        {{-- Header Settings --}}
        <div class="mb-6 lg:mb-8">
            <h1 class="font-heading text-2xl font-bold text-zinc-100">Settings</h1>
            <p class="text-sm text-zinc-500 mt-0.5">Manage your account preferences</p>
        </div>

        {{-- Tab Navigasi Garis Bawah --}}
        <div class="flex gap-2 mb-6 border-b border-zinc-800">
            @foreach (['profile' => ['Profile', 'profile.edit'], 'security' => ['Security', 'security.edit']] as $key => [$label, $route])
                <a href="{{ route($route) }}" wire:navigate
                   class="px-4 py-2.5 lg:py-2 text-sm font-medium border-b-2 transition -mb-px
                          {{ $active === $key ? 'border-emerald-500 text-emerald-500' : 'border-transparent text-zinc-500 hover:text-zinc-300' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        {{-- Sub-header Judul Aktif --}}
        <div class="mb-6">
            <h2 class="font-heading text-lg font-bold text-zinc-100">{{ $heading ?? '' }}</h2>
            @if (! empty($subheading))
                <p class="text-sm text-zinc-500 mt-1">{{ $subheading }}</p>
            @endif
        </div>

        {{-- Konten Utama --}}
        {{ $slot }}
    </div>
</div>