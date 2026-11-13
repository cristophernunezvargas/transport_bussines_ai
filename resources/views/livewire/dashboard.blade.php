@php
    $period = $this->period();
    $monthPeriod = $this->monthPeriod();
    $yearPeriod = $this->yearPeriod();
    $alerts = $this->maintenanceAlerts;
    $months = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
@endphp

<section class="w-full">
    <div>
        <flux:heading size="xl">{{ __('Resumen de la flota') }}</flux:heading>
        <flux:subheading>{{ __('Los mismos indicadores por semana (jueves a miércoles), por mes y por año.') }}</flux:subheading>
    </div>

    <flux:callout icon="information-circle" color="blue" class="mt-6">
        {{ __('Los km y las métricas por km son un estimado mientras no se capture el odómetro por viaje (ver Rutas). No son valores exactos.') }}
    </flux:callout>

    <div class="mt-6 flex flex-wrap gap-4">
        <flux:card size="sm" class="flex items-center gap-3">
            <flux:badge size="lg" color="red">{{ $alerts['overdue'] }}</flux:badge>
            <div>
                <flux:text class="font-medium">{{ __('Mantenimientos vencidos') }}</flux:text>
                <flux:text size="sm" class="text-zinc-500">{{ __('Requieren atención ya') }}</flux:text>
            </div>
        </flux:card>
        <flux:card size="sm" class="flex items-center gap-3">
            <flux:badge size="lg" color="amber">{{ $alerts['upcoming'] }}</flux:badge>
            <div>
                <flux:text class="font-medium">{{ __('Próximos a vencer') }}</flux:text>
                <flux:text size="sm" class="text-zinc-500">{{ __('Dentro del margen de aviso') }}</flux:text>
            </div>
        </flux:card>
        <flux:button variant="ghost" icon="calendar-days" :href="route('maintenance-schedules.index')" wire:navigate>
            {{ __('Ver programas') }}
        </flux:button>
    </div>

    <div class="mt-6 space-y-6">
        <x-dashboard-section
            :label="__('Semanal')"
            color="blue"
            :range-text="$period->start->format('d/m/Y').' — '.$period->end->format('d/m/Y')"
            :totals="$this->fleetTotals"
            :rows="$this->fleetRows"
            previous="previousWeek"
            next="nextWeek"
            current="thisWeek"
            :current-label="__('Esta semana')"
        />

        <x-dashboard-section
            :label="__('Mensual')"
            color="violet"
            :range-text="ucfirst($months[$monthPeriod->start->month - 1]).' '.$monthPeriod->start->year"
            :totals="$this->monthTotals"
            :rows="$this->monthRows"
            previous="previousMonth"
            next="nextMonth"
            current="thisMonth"
            :current-label="__('Este mes')"
        />

        <x-dashboard-section
            :label="__('Anual')"
            color="emerald"
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
