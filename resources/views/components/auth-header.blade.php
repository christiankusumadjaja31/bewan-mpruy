@props([
    'title',
    'description' => null,
])

<div class="flex w-full flex-col">
    <h1 class="font-heading text-[26px] font-semibold leading-tight tracking-tight text-zinc-50">
        {{ $title }}
    </h1>

    @if (filled($description))
        <p class="mt-2 text-[15px] leading-relaxed text-pretty text-zinc-400">
            {{ $description }}
        </p>
    @endif
</div>
