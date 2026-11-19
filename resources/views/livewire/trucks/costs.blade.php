@php
    $range = $this->range;
    $b = $this->breakdown;
    $money = fn (float|int $value): string => ($value < 0 ? '-' : '').'₡'.number_format(abs($value), 0);
    $share = fn (float $value): string => $b['total_cost'] > 0 ? number_format($value / $b['total_cost'] * 100, 1).'%' : '—';
@endphp

<section class="w-full">
    <div>
        <flux:button variant="ghost" size="sm" icon="arrow-left" :href="route('dashboard')" wire:navigate class="-ms-2">{{ __('Volver al resumen') }}</flux:button>

        <div class="mt-2 flex flex-wrap items-baseline gap-x-4 gap-y-1">
            <flux:heading size="xl">{{ __('Costos de :plate', ['plate' => $truck->plate]) }}</flux:heading>
            <flux:text class="font-medium">{{ $range->start->format('d/m/Y') }} — {{ $range->end->format('d/m/Y') }}</flux:text>
        </div>
        <flux:subheading>{{ __('Cómo se compone el costo total del período y los registros que lo originan.') }}</flux:subheading>
    </div>

    {{-- Breakdown --}}
    <div class="mt-6 rounded-2xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Concepto') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Monto') }}</flux:table.column>
                <flux:table.column align="end">{{ __('% del total') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                <flux:table.row>
                    <flux:table.cell colspan="3" variant="strong" class="bg-zinc-50 dark:bg-zinc-800/50">{{ __('Costos directos') }}</flux:table.cell>
                </flux:table.row>
                @foreach ([
                    __('Combustible') => $b['fuel'],
                    __('Mantenimiento') => $b['maintenance'],
                    __('Gastos del camión') => $b['truck_expenses'],
                    __('Llantas (estimado)') => $b['tires'],
                    __('Peajes') => $b['tolls'],
                ] as $label => $amount)
                    <flux:table.row>
                        <flux:table.cell class="ps-6">{{ $label }}</flux:table.cell>
                        <flux:table.cell align="end">{{ $money($amount) }}</flux:table.cell>
                        <flux:table.cell align="end" class="text-zinc-500">{{ $share($amount) }}</flux:table.cell>
                    </flux:table.row>
                @endforeach
                <flux:table.row>
                    <flux:table.cell variant="strong">{{ __('Subtotal directo') }}</flux:table.cell>
                    <flux:table.cell align="end" variant="strong">{{ $money($b['direct_cost']) }}</flux:table.cell>
                    <flux:table.cell align="end" class="text-zinc-500">{{ $share($b['direct_cost']) }}</flux:table.cell>
                </flux:table.row>

                <flux:table.row>
                    <flux:table.cell colspan="3" variant="strong" class="bg-zinc-50 dark:bg-zinc-800/50">{{ __('Costos indirectos') }}</flux:table.cell>
                </flux:table.row>
                <flux:table.row>
                    <flux:table.cell class="ps-6">{{ __('Costos fijos del camión (prorrateados)') }}</flux:table.cell>
                    <flux:table.cell align="end">{{ $money($b['truck_fixed_costs']) }}</flux:table.cell>
                    <flux:table.cell align="end" class="text-zinc-500">{{ $share($b['truck_fixed_costs']) }}</flux:table.cell>
                </flux:table.row>
                <flux:table.row>
                    <flux:table.cell class="ps-6">{{ __('Costos generales asignados') }}</flux:table.cell>
                    <flux:table.cell align="end">{{ $money($b['allocated_overhead']) }}</flux:table.cell>
                    <flux:table.cell align="end" class="text-zinc-500">{{ $share($b['allocated_overhead']) }}</flux:table.cell>
                </flux:table.row>
                <flux:table.row>
                    <flux:table.cell variant="strong">{{ __('Subtotal indirecto') }}</flux:table.cell>
                    <flux:table.cell align="end" variant="strong">{{ $money($b['indirect_cost']) }}</flux:table.cell>
                    <flux:table.cell align="end" class="text-zinc-500">{{ $share($b['indirect_cost']) }}</flux:table.cell>
                </flux:table.row>

                <flux:table.row>
                    <flux:table.cell variant="strong" class="text-base">{{ __('Costo total') }}</flux:table.cell>
                    <flux:table.cell align="end" variant="strong" class="text-base">{{ $money($b['total_cost']) }}</flux:table.cell>
                    <flux:table.cell align="end" class="text-zinc-500">{{ $b['total_cost'] > 0 ? '100%' : '—' }}</flux:table.cell>
                </flux:table.row>
            </flux:table.rows>
        </flux:table>

        <flux:text size="sm" class="mt-4 text-zinc-500">
            {{ __('Las llantas son una estimación lineal según las semanas montadas, y los costos generales se reparten entre camiones según los días trabajados; ninguno tiene un registro individual.') }}
        </flux:text>
    </div>

    <div class="mt-6 space-y-6">
        {{-- Fuel --}}
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex items-center justify-between gap-3">
                <flux:heading size="lg">{{ __('Combustible') }}</flux:heading>
                <flux:button size="sm" variant="ghost" icon-trailing="arrow-right" :href="route('fuel-records.index')" wire:navigate>{{ __('Ver registros') }}</flux:button>
            </div>
            <div class="mt-3 overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('Fecha') }}</flux:table.column>
                        <flux:table.column>{{ __('Estación') }}</flux:table.column>
                        <flux:table.column align="end">{{ __('Litros') }}</flux:table.column>
                        <flux:table.column align="end">{{ __('Precio / litro') }}</flux:table.column>
                        <flux:table.column align="end">{{ __('Total') }}</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @forelse ($this->fuelRecords as $record)
                            <flux:table.row :key="'fuel-'.$record->id">
                                <flux:table.cell>{{ $record->occurred_at->format('d/m/Y') }}</flux:table.cell>
                                <flux:table.cell>{{ $record->fuelStation->name }}</flux:table.cell>
                                <flux:table.cell align="end">{{ number_format((float) $record->liters, 2) }}</flux:table.cell>
                                <flux:table.cell align="end">{{ $money((float) $record->unit_price) }}</flux:table.cell>
                                <flux:table.cell align="end" variant="strong">{{ $money((float) $record->total) }}</flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="5" class="text-center text-zinc-500">{{ __('Sin registros en este período.') }}</flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>
        </div>

        {{-- Maintenance --}}
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex items-center justify-between gap-3">
                <flux:heading size="lg">{{ __('Mantenimiento') }}</flux:heading>
                <flux:button size="sm" variant="ghost" icon-trailing="arrow-right" :href="route('maintenances.index')" wire:navigate>{{ __('Ver historial') }}</flux:button>
            </div>
            <div class="mt-3 overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('Fecha') }}</flux:table.column>
                        <flux:table.column>{{ __('Tipo') }}</flux:table.column>
                        <flux:table.column>{{ __('Taller') }}</flux:table.column>
                        <flux:table.column>{{ __('Descripción') }}</flux:table.column>
                        <flux:table.column align="end">{{ __('Total') }}</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @forelse ($this->maintenances as $maintenance)
                            <flux:table.row :key="'maintenance-'.$maintenance->id">
                                <flux:table.cell>{{ $maintenance->completion_date?->format('d/m/Y') }}</flux:table.cell>
                                <flux:table.cell>{{ $maintenance->type->label() }}</flux:table.cell>
                                <flux:table.cell>{{ $maintenance->provider->name }}</flux:table.cell>
                                <flux:table.cell>{{ $maintenance->description ?? '—' }}</flux:table.cell>
                                <flux:table.cell align="end" variant="strong">{{ $money((float) $maintenance->total) }}</flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="5" class="text-center text-zinc-500">{{ __('Sin registros en este período.') }}</flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>
        </div>

        {{-- Truck expenses --}}
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex items-center justify-between gap-3">
                <flux:heading size="lg">{{ __('Gastos del camión') }}</flux:heading>
                <flux:button size="sm" variant="ghost" icon-trailing="arrow-right" :href="route('truck-expenses.index')" wire:navigate>{{ __('Ver gastos') }}</flux:button>
            </div>
            <div class="mt-3 overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('Fecha') }}</flux:table.column>
                        <flux:table.column>{{ __('Categoría') }}</flux:table.column>
                        <flux:table.column>{{ __('Descripción') }}</flux:table.column>
                        <flux:table.column align="end">{{ __('Monto') }}</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @forelse ($this->expenses as $expense)
                            <flux:table.row :key="'expense-'.$expense->id">
                                <flux:table.cell>{{ $expense->expense_date->format('d/m/Y') }}</flux:table.cell>
                                <flux:table.cell>{{ $expense->costType->name }}</flux:table.cell>
                                <flux:table.cell>{{ $expense->description ?? '—' }}</flux:table.cell>
                                <flux:table.cell align="end" variant="strong">{{ $money((float) $expense->amount) }}</flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="4" class="text-center text-zinc-500">{{ __('Sin registros en este período.') }}</flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>
        </div>

        {{-- Fixed costs --}}
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex items-center justify-between gap-3">
                <flux:heading size="lg">{{ __('Costos fijos del camión') }}</flux:heading>
                <flux:button size="sm" variant="ghost" icon-trailing="arrow-right" :href="route('truck-fixed-costs.index')" wire:navigate>{{ __('Ver costos fijos') }}</flux:button>
            </div>
            <div class="mt-3 overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('Categoría') }}</flux:table.column>
                        <flux:table.column>{{ __('Ciclo de cobro') }}</flux:table.column>
                        <flux:table.column align="end">{{ __('Monto del ciclo') }}</flux:table.column>
                        <flux:table.column align="end">{{ __('Parte del período') }}</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @forelse ($this->fixedCosts as $item)
                            <flux:table.row :key="'fixed-'.$item['cost']->id">
                                <flux:table.cell>{{ $item['cost']->costType->name }}</flux:table.cell>
                                <flux:table.cell>{{ $item['cost']->billing_cycle->label() }}</flux:table.cell>
                                <flux:table.cell align="end">{{ $money((float) $item['cost']->amount) }}</flux:table.cell>
                                <flux:table.cell align="end" variant="strong">{{ $money($item['prorated']) }}</flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="4" class="text-center text-zinc-500">{{ __('Sin costos fijos vigentes en este período.') }}</flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>
        </div>

        {{-- Tolls (screen currently disabled, but data may exist) --}}
        @if ($this->tollRecords->isNotEmpty())
            <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                <flux:heading size="lg">{{ __('Peajes') }}</flux:heading>
                <div class="mt-3 overflow-x-auto">
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>{{ __('Fecha') }}</flux:table.column>
                            <flux:table.column align="end">{{ __('Monto') }}</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach ($this->tollRecords as $toll)
                                <flux:table.row :key="'toll-'.$toll->id">
                                    <flux:table.cell>{{ $toll->occurred_at->format('d/m/Y') }}</flux:table.cell>
                                    <flux:table.cell align="end" variant="strong">{{ $money((float) $toll->amount) }}</flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>
            </div>
        @endif
    </div>
</section>
