<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-100 antialiased dark:bg-zinc-950">
        <div class="road-line"></div>

        <div class="flex min-h-svh flex-col items-center justify-center gap-8 p-6 md:p-10">
            <div class="flex w-full max-w-sm flex-col gap-6">
                <x-app-logo :href="route('home')" wire:navigate class="self-center" />

                <div class="flex flex-col gap-6 rounded-2xl border border-zinc-200 bg-white p-6 shadow-[0_1px_3px_rgb(36_28_23/0.06)] sm:p-8 dark:border-white/10 dark:bg-zinc-900">
                    {{ $slot }}
                </div>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
