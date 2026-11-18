@props([
    'status',
])

@if ($status)
    <div
        role="status"
        {{ $attributes->merge(['class' => 'rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-3.5 py-2.5 text-[13px] font-medium text-emerald-300']) }}
    >
        {{ $status }}
    </div>
@endif
