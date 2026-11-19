@props([
    'label',
    'color' => 'amber',
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
        'red' => 'border-t-brick',
        'lime' => 'border-t-olive',
        default => 'border-t-accent',
    };
@endphp

<section {{ $attributes->class("rounded-2xl border border-t-4 border-zinc-200 bg-white p-5 shadow-[0_1px_3px_rgb(36_28_23/0.06)] dark:border-white/10 dark:bg-zinc-900 sm:p-6 $bar") }}>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
            <flux:badge size="lg" :color="$color">{{ $label }}</flux:badge>
            <span class="font-display text-2xl font-semibold leading-none text-zinc-900 dark:text-white">{{ $rangeText }}</span>
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

    <div class="mt-5 grid grid-cols-2 gap-3 lg:grid-cols-6">
        <x-kpi-card
            hero
            class="col-span-2"
            :label="__('Utilidad neta')"
            :tone="$totals['profit_net'] < 0 ? 'bad' : 'good'"
            :note="$totals['margin_pct'] !== null ? __('Margen de :pct sobre los ingresos', ['pct' => number_format($totals['margin_pct'], 1).'%']) : __('Sin ingresos en el período')"
        >₡{{ number_format($totals['profit_net'], 0) }}</x-kpi-card>
        <x-kpi-card :label="__('Ingresos')">₡{{ number_format($totals['revenue'], 0) }}</x-kpi-card>
        <x-kpi-card :label="__('Costo total')">₡{{ number_format($totals['total_cost'], 0) }}</x-kpi-card>
        <x-kpi-card :label="__('Costo / km (est.)')">{{ $totals['cost_per_km'] !== null ? '₡'.number_format($totals['cost_per_km'], 0) : '—' }}</x-kpi-card>
        <x-kpi-card :label="__('Ingreso / km (est.)')">{{ $totals['revenue_per_km'] !== null ? '₡'.number_format($totals['revenue_per_km'], 0) : '—' }}</x-kpi-card>
    </div>

    <div class="table-bare mt-5 border-t border-zinc-200 dark:border-white/10">
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
                        <flux:table.cell align="end">
                            <a href="{{ route('trucks.costs', ['truck' => $row['truck']->id, 'from' => $period->start->toDateString(), 'to' => $period->end->toDateString()]) }}" wire:navigate class="underline decoration-dotted underline-offset-4 hover:text-accent-content" title="{{ __('Ver el detalle de costos de :plate', ['plate' => $row['truck']->plate]) }}">
                                ₡{{ number_format($row['total_cost'], 0) }}
                            </a>
                        </flux:table.cell>
                        <flux:table.cell align="end" :class="$row['profit_net'] < 0 ? 'text-brick' : ''">
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
