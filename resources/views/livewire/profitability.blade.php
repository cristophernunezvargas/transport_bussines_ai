@php
    $report = $this->report;
    $money = fn (float|int $value): string => ($value < 0 ? '-' : '').'₡'.number_format(abs($value), 0);
    $modes = ['week' => __('Semana'), 'month' => __('Mes'), 'year' => __('Año')];
@endphp

<section class="w-full">
    <div>
        <flux:heading size="xl" level="1">{{ __('Rentabilidad') }}</flux:heading>
        <flux:subheading>{{ __('Cuánto deja cada ruta y cada cliente. El costo total de cada camión se reparte entre sus viajes según los km.') }}</flux:subheading>
    </div>

    <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-1 rounded-lg border border-zinc-200 bg-white p-1 dark:border-white/10 dark:bg-zinc-900" role="group" aria-label="{{ __('Período') }}">
            @foreach ($modes as $value => $label)
                <flux:button size="sm" :variant="$mode === $value ? 'primary' : 'ghost'" wire:click="setMode('{{ $value }}')" aria-pressed="{{ $mode === $value ? 'true' : 'false' }}">{{ $label }}</flux:button>
            @endforeach
        </div>

        <div class="flex items-center gap-1">
            <flux:button size="sm" variant="ghost" icon="chevron-left" wire:click="previous" aria-label="{{ __('Anterior') }}" />
            <span class="min-w-40 text-center font-display text-xl font-semibold text-zinc-900 dark:text-white">{{ $this->periodLabel }}</span>
            <flux:button size="sm" variant="ghost" icon="chevron-right" wire:click="next" aria-label="{{ __('Siguiente') }}" />
            <flux:button size="sm" variant="ghost" wire:click="today">{{ __('Hoy') }}</flux:button>
        </div>
    </div>

    <div class="mt-5 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-kpi-card :label="__('Viajes completados')">{{ number_format($report['trips']) }}</x-kpi-card>
        <x-kpi-card :label="__('Ingresos')">{{ $money($report['revenue']) }}</x-kpi-card>
        <x-kpi-card :label="__('Costo total de la flota')">{{ $money($report['total_cost']) }}</x-kpi-card>
        <x-kpi-card :label="__('Utilidad neta')" :tone="$report['profit_net'] < 0 ? 'bad' : 'good'">{{ $money($report['profit_net']) }}</x-kpi-card>
    </div>

    <flux:text size="sm" class="mt-3 text-zinc-500">
        @if ($report['unallocated_cost'] > 0.5)
            {{ __(':amount del costo total corresponde a camiones sin viajes en el período, así que no se le carga a ninguna ruta ni cliente. Por eso las tablas suman menos costo que el total de la flota.', ['amount' => $money($report['unallocated_cost'])]) }}
        @else
            {{ __('Estas cifras coinciden con el Resumen: todo el costo de la flota está repartido entre los viajes.') }}
        @endif
    </flux:text>

    <div class="mt-6 space-y-6">
        <x-profit-table :title="__('Por ruta')" :name-label="__('Ruta')" :rows="$report['routes']" />

        <x-profit-table
            :title="__('Por cliente')"
            :name-label="__('Cliente')"
            :rows="$report['customers']"
            :note="__('Un viaje se asigna a un cliente por su acuerdo de tarifa o por la factura en que se cobró. Si no tiene ninguno, queda en «Sin cliente asignado».')"
        />
    </div>
</section>
