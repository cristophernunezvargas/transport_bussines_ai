<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main>
        <div class="mx-auto w-full max-w-7xl">
            {{ $slot }}
        </div>
    </flux:main>
</x-layouts::app.sidebar>
