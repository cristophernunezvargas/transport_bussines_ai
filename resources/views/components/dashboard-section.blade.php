@props([
    'label',
    'color' => 'blue',
    'period',
    'rangeText',
    'totals',
    'rows',
    'previous',
    'next',
    'current',
    'currentLabel',
    'pdfUrl' => null,
])

@php
    $bar = match ($color) {
        'violet' => 'border-t-violet-500',
        'emerald' => 'border-t-emerald-500',
        default => 'border-t-blue-500',
    };
@endphp

<section {{ $attributes->class("rounded-2xl border border-t-4 border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 $bar") }}>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <flux:badge size="lg" :color="$color">{{ $label }}</flux:badge>
            <flux:text class="whitespace-nowrap font-medium">{{ $rangeText }}</flux:text>
        </div>

        <div class="flex items-center gap-1">
            <flux:button size="sm" variant="ghost" icon="chevron-left" wire:click="{{ $previous }}" aria-label="{{ __('Anterior') }}" />
            <flux:button size="sm" variant="ghost" wire:click="{{ $current }}">{{ $currentLabel }}</flux:button>
            <flux:button size="sm" variant="ghost" icon="chevron-right" wire:click="{{ $next }}" aria-label="{{ __('Siguiente') }}" />

            @if ($pdfUrl)
                <flux:button size="sm" variant="outline" icon="arrow-down-tray" :href="$pdfUrl" class="ms-2">{{ __('PDF') }}</flux:button>
            @endif
        </div>
    </div>

    <div class="mt-5 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
        <x-kpi-card :label="__('Ingresos')" icon="banknotes">₡{{ number_format($totals['revenue'], 0) }}</x-kpi-card>
        <x-kpi-card :label="__('Costo total')" icon="receipt-percent">₡{{ number_format($totals['total_cost'], 0) }}</x-kpi-card>
        <x-kpi-card :label="__('Utilidad neta')" icon="arrow-trending-up" :tone="$totals['profit_net'] < 0 ? 'bad' : 'good'">₡{{ number_format($totals['profit_net'], 0) }}</x-kpi-card>
        <x-kpi-card :label="__('Margen')" icon="chart-pie">{{ $totals['margin_pct'] !== null ? number_format($totals['margin_pct'], 1).'%' : '—' }}</x-kpi-card>
        <x-kpi-card :label="__('Costo / km (est.)')" icon="map">{{ $totals['cost_per_km'] !== null ? '₡'.number_format($totals['cost_per_km'], 0) : '—' }}</x-kpi-card>
        <x-kpi-card :label="__('Ingreso / km (est.)')" icon="map-pin">{{ $totals['revenue_per_km'] !== null ? '₡'.number_format($totals['revenue_per_km'], 0) : '—' }}</x-kpi-card>
    </div>

    <div class="mt-5 overflow-x-auto">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Camión') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Ingresos') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Km (est.)') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Costo total') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Utilidad') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Margen') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Costo/km') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Disponibilidad') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Utilización') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($rows as $row)
                    <flux:table.row :key="$row['truck']->id">
                        <flux:table.cell variant="strong">{{ $row['truck']->plate }}</flux:table.cell>
                        <flux:table.cell align="end">₡{{ number_format($row['revenue'], 0) }}</flux:table.cell>
                        <flux:table.cell align="end">{{ number_format($row['km'], 0) }}</flux:table.cell>
                        <flux:table.cell align="end">₡{{ number_format($row['total_cost'], 0) }}</flux:table.cell>
                        <flux:table.cell align="end" :class="$row['profit_net'] < 0 ? 'text-red-600 dark:text-red-400' : ''">
                            ₡{{ number_format($row['profit_net'], 0) }}
                        </flux:table.cell>
                        <flux:table.cell align="end">{{ $row['margin_pct'] !== null ? number_format($row['margin_pct'], 1).'%' : '—' }}</flux:table.cell>
                        <flux:table.cell align="end">{{ $row['cost_per_km'] !== null ? '₡'.number_format($row['cost_per_km'], 0) : '—' }}</flux:table.cell>
                        <flux:table.cell align="end">{{ number_format($row['availability_pct'], 0) }}%</flux:table.cell>
                        <flux:table.cell align="end">{{ $row['utilization_pct'] !== null ? number_format($row['utilization_pct'], 0).'%' : '—' }}</flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="9" class="text-center text-zinc-500">
                            {{ __('No hay camiones registrados todavía.') }}
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>
</section>
