@props([
    'label',
    'tone' => 'default',
    'hero' => false,
    'note' => null,
])

@php
    $value = match ($tone) {
        'good' => 'text-olive',
        'bad' => 'text-brick',
        default => 'text-zinc-900 dark:text-white',
    };
@endphp

<div {{ $attributes->class([
    'flex flex-col justify-between rounded-xl border border-zinc-200 bg-white p-4 shadow-[0_1px_2px_rgb(36_28_23/0.05)] dark:border-white/10 dark:bg-zinc-900',
    'border-l-4' => $hero,
    'border-l-olive' => $hero && $tone === 'good',
    'border-l-brick' => $hero && $tone === 'bad',
    'border-l-accent' => $hero && $tone === 'default',
]) }}>
    <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ $label }}</span>
    <div>
        <div @class([
            'font-display font-semibold tabular-nums leading-none',
            'mt-3 text-3xl' => ! $hero,
            'mt-4 text-5xl' => $hero,
            $value,
        ])>{{ $slot }}</div>
        @if ($note)
            <div class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ $note }}</div>
        @endif
    </div>
</div>
