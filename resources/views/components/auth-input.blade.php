@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'placeholder' => null,
    'autocomplete' => null,
    'required' => false,
    'autofocus' => false,
    'passwordrules' => null,
    'hint' => null,
])

@php
    $hasError = $errors->has($name);
@endphp

<div>
    <label for="{{ $name }}" class="block text-xs font-medium uppercase tracking-wider text-zinc-500">
        {{ $label }}
    </label>

    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        @if ($type !== 'password') value="{{ old($name, $value) }}" @endif
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        @if ($passwordrules) passwordrules="{{ $passwordrules }}" @endif
        @if ($required) required @endif
        @if ($autofocus) autofocus @endif
        @if ($hasError) aria-invalid="true" aria-describedby="{{ $name }}-error" @endif
        {{ $attributes->merge([
            'class' => 'mt-2 h-12 w-full rounded-xl border bg-zinc-950/60 px-4 text-[15px] text-zinc-100 placeholder:text-zinc-600 transition outline-none focus:border-emerald-500/60 focus:ring-2 focus:ring-emerald-500/25 aria-invalid:border-red-500/70 aria-invalid:focus:border-red-500 aria-invalid:focus:ring-red-500/25 '
                . ($hasError ? 'border-red-500/70' : 'border-zinc-800 hover:border-zinc-700'),
        ]) }}
    />

    @error($name)
        <p id="{{ $name }}-error" class="mt-2 text-[13px] text-red-400" role="alert">{{ $message }}</p>
    @else
        @if ($hint)
            <p class="mt-2 text-[13px] text-zinc-600">{{ $hint }}</p>
        @endif
    @enderror
</div>
