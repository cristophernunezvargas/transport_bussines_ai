@props([
    'title',
    'nameLabel',
    'rows',
    'note' => null,
])

@php
    $money = fn (float|int $value): string => ($value < 0 ? '-' : '').'₡'.number_format(abs($value), 0);
@endphp

<section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-[0_1px_3px_rgb(36_28_23/0.06)] sm:p-6 dark:border-white/10 dark:bg-zinc-900">
    <flux:heading size="lg">{{ $title }}</flux:heading>

    @if ($note)
        <flux:text size="sm" class="mt-1 text-zinc-500">{{ $note }}</flux:text>
    @endif

    <div class="table-bare mt-4 border-t border-zinc-200 dark:border-white/10">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ $nameLabel }}</flux:table.column>
                <flux:table.column align="end">{{ __('Viajes') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Km (est.)') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Ingresos') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Costo asignado') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Utilidad') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Margen') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Utilidad / km') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($rows as $row)
                    <flux:table.row :key="$row['key'] ?? 'none'">
                        <flux:table.cell variant="strong" @class(['italic' => $row['key'] === null])>{{ $row['name'] }}</flux:table.cell>
                        <flux:table.cell align="end">{{ number_format($row['trips']) }}</flux:table.cell>
                        <flux:table.cell align="end">{{ number_format($row['km'], 0) }}</flux:table.cell>
                        <flux:table.cell align="end">{{ $money($row['revenue']) }}</flux:table.cell>
                        <flux:table.cell align="end">{{ $money($row['cost']) }}</flux:table.cell>
                        <flux:table.cell align="end" variant="strong" :class="$row['profit'] < 0 ? 'text-brick' : ''">{{ $money($row['profit']) }}</flux:table.cell>
                        <flux:table.cell align="end">{{ $row['margin_pct'] !== null ? number_format($row['margin_pct'], 1).'%' : '—' }}</flux:table.cell>
                        <flux:table.cell align="end">{{ $row['profit_per_km'] !== null ? $money($row['profit_per_km']) : '—' }}</flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="8" class="text-center text-zinc-500">{{ __('No hay viajes completados en este período.') }}</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>
</section>
