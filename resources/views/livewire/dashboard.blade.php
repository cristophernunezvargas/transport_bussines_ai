@php
    $period = $this->period();
    $monthPeriod = $this->monthPeriod();
    $yearPeriod = $this->yearPeriod();
    $alerts = $this->maintenanceAlerts;
    $months = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
@endphp

<section class="w-full">
    <div>
        <flux:heading size="xl" level="1">{{ __('Resumen de la flota') }}</flux:heading>
        <flux:subheading>{{ __('Los mismos indicadores por semana (jueves a miércoles), por mes y por año.') }}</flux:subheading>
    </div>

    <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_auto]">
        <a href="{{ route('maintenance-schedules.index') }}" wire:navigate class="flex items-center gap-4 rounded-xl border border-l-4 border-zinc-200 border-l-brick bg-white p-4 shadow-[0_1px_2px_rgb(36_28_23/0.05)] hover:border-zinc-300 hover:border-l-brick dark:border-white/10 dark:bg-zinc-900 dark:border-l-brick">
            <span class="font-display text-4xl font-semibold leading-none text-brick tabular-nums">{{ $alerts['overdue'] }}</span>
            <span>
                <span class="block font-medium text-zinc-900 dark:text-white">{{ __('Mantenimientos vencidos') }}</span>
                <span class="block text-sm text-zinc-500">{{ __('Requieren atención ya') }}</span>
            </span>
        </a>
        <a href="{{ route('maintenance-schedules.index') }}" wire:navigate class="flex items-center gap-4 rounded-xl border border-l-4 border-zinc-200 border-l-accent bg-white p-4 shadow-[0_1px_2px_rgb(36_28_23/0.05)] hover:border-zinc-300 hover:border-l-accent dark:border-white/10 dark:bg-zinc-900 dark:border-l-accent">
            <span class="font-display text-4xl font-semibold leading-none text-accent-content tabular-nums">{{ $alerts['upcoming'] }}</span>
            <span>
                <span class="block font-medium text-zinc-900 dark:text-white">{{ __('Próximos a vencer') }}</span>
                <span class="block text-sm text-zinc-500">{{ __('Dentro del margen de aviso') }}</span>
            </span>
        </a>
        <p class="flex items-start gap-2 self-center text-sm text-zinc-500 sm:col-span-2 lg:col-span-1 lg:max-w-xs">
            <flux:icon.information-circle class="mt-0.5 size-4 shrink-0" />
            {{ __('Los km y las métricas por km son un estimado: salen de los km estándar de cada ruta, no de un odómetro por viaje.') }}
        </p>
    </div>

    <div class="mt-6 space-y-6">
        <x-dashboard-section
            :label="__('Semanal')"
            color="amber"
            :period="$period"
            :range-text="$period->start->format('d/m/Y').' — '.$period->end->format('d/m/Y')"
            :totals="$this->fleetTotals"
            :rows="$this->fleetRows"
            previous="previousWeek"
            next="nextWeek"
            current="thisWeek"
            :current-label="__('Esta semana')"
            :pdf-url="route('dashboard.pdf', ['period' => 'weekly', 'date' => $period->start->toDateString()])"
        />

        <x-dashboard-section
            :label="__('Mensual')"
            color="red"
            :period="$monthPeriod"
            :range-text="ucfirst($months[$monthPeriod->start->month - 1]).' '.$monthPeriod->start->year"
            :totals="$this->monthTotals"
            :rows="$this->monthRows"
            previous="previousMonth"
            next="nextMonth"
            current="thisMonth"
            :current-label="__('Este mes')"
            :pdf-url="route('dashboard.pdf', ['period' => 'monthly', 'date' => $monthPeriod->start->toDateString()])"
        />

        <x-dashboard-section
            :label="__('Anual')"
            color="lime"
            :period="$yearPeriod"
            :range-text="(string) $yearPeriod->start->year"
            :totals="$this->yearTotals"
            :rows="$this->yearRows"
            previous="previousYear"
            next="nextYear"
            current="thisYear"
            :current-label="__('Este año')"
        />
    </div>
</section>
