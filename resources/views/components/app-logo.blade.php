@props([
    'sidebar' => false,
])

{{-- Wordmark: "Transportes" over the condensed "M.N.S.T". `sidebar` is kept for call-site compatibility. --}}
<a {{ $attributes->class('flex items-center gap-3 rounded-lg outline-offset-4') }}>
    <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-accent text-accent-foreground">
        <x-app-logo-icon class="size-7 text-accent-foreground [--logo-gap:var(--color-accent)]" />
    </span>
    <span class="grid leading-none">
        <span class="text-sm font-medium text-zinc-500 dark:text-zinc-300">Transportes</span>
        <span class="font-display text-2xl font-semibold tracking-wide text-zinc-900 dark:text-white">M.N.S.T</span>
    </span>
</a>
