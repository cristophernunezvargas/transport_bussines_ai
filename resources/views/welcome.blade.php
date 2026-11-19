<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => __('Bienvenido')])
    </head>
    <body class="min-h-screen bg-zinc-100 antialiased dark:bg-zinc-950">
        <div class="mx-auto flex min-h-svh max-w-6xl flex-col px-6">
            <header class="flex items-center justify-between py-6">
                <x-app-logo :href="route('home')" />

                @auth
                    <flux:button variant="primary" :href="route('dashboard')">{{ __('Ir al panel') }}</flux:button>
                @else
                    <flux:button variant="primary" :href="route('login')">{{ __('Iniciar sesión') }}</flux:button>
                @endauth
            </header>

            <main class="flex flex-1 flex-col justify-center py-10">
                <h1 class="max-w-3xl font-display text-5xl font-semibold leading-[1.05] text-balance text-zinc-900 sm:text-7xl dark:text-white">
                    {{ __('Cuánto cuesta y cuánto deja cada camión.') }}
                </h1>
                <p class="mt-6 max-w-xl text-lg text-zinc-600 dark:text-zinc-300">
                    {{ __('Control de costos, mantenimiento y rentabilidad de la flota de :name, con datos que puedes rastrear hasta su origen.', ['name' => config('app.name')]) }}
                </p>

                <div class="mt-8">
                    @auth
                        <flux:button variant="primary" size="base" :href="route('dashboard')">{{ __('Ir al panel') }}</flux:button>
                    @else
                        <flux:button variant="primary" size="base" :href="route('login')">{{ __('Iniciar sesión') }}</flux:button>
                    @endauth
                </div>

                <div class="mt-14">
                    <x-truck-illustration class="ms-auto w-full max-w-2xl" />
                    <div class="road-line"></div>
                </div>

                <dl class="mt-14 grid gap-8 border-t border-zinc-300 pt-8 sm:grid-cols-3 dark:border-white/15">
                    <div>
                        <dt class="font-display text-2xl font-semibold text-zinc-900 dark:text-white">{{ __('Costo real por camión') }}</dt>
                        <dd class="mt-2 text-zinc-600 dark:text-zinc-300">{{ __('Combustible, mantenimiento, llantas y gastos en un solo lugar.') }}</dd>
                    </div>
                    <div>
                        <dt class="font-display text-2xl font-semibold text-zinc-900 dark:text-white">{{ __('Mantenimiento al día') }}</dt>
                        <dd class="mt-2 text-zinc-600 dark:text-zinc-300">{{ __('Cambios de aceite y servicios con alertas de vencidos y próximos.') }}</dd>
                    </div>
                    <div>
                        <dt class="font-display text-2xl font-semibold text-zinc-900 dark:text-white">{{ __('Rentabilidad clara') }}</dt>
                        <dd class="mt-2 text-zinc-600 dark:text-zinc-300">{{ __('Utilidad y margen por camión, viaje, ruta y cliente.') }}</dd>
                    </div>
                </dl>
            </main>

            <footer class="py-6 text-sm text-zinc-500">
                &copy; {{ date('Y') }} {{ config('app.name') }}
            </footer>
        </div>

        @fluxScripts
    </body>
</html>
