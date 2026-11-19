<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $title }} — {{ $rangeText }}</title>
    <style>
        @page { margin: 34px 38px 54px 38px; }

        * { box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9.5px;
            line-height: 1.4;
            color: #241c17;
        }

        table { width: 100%; border-collapse: collapse; }
        .muted { color: #7b6e60; }
        .right { text-align: right; }
        .neg { color: #b3321f; }

        /* Header */
        .brand { font-size: 15px; font-weight: bold; color: #241c17; }
        .brand-sub { font-size: 9px; color: #7b6e60; }
        .logo { width: 34px; height: 34px; }
        .title { font-size: 20px; font-weight: bold; text-align: right; color: #241c17; }
        .range { font-size: 12px; text-align: right; color: #b45309; font-weight: bold; }
        .rule { height: 3px; background: #b45309; margin: 12px 0 16px 0; }

        /* KPI cards */
        .kpis { border-collapse: separate; border-spacing: 7px 0; margin-left: -7px; width: 100%; }
        .kpi {
            width: 16.66%;
            border: 1px solid #e8dfd0;
            border-top: 3px solid #b45309;
            padding: 8px 9px;
            background: #fbf8f3;
        }
        .kpi-label { font-size: 8px; color: #7b6e60; text-transform: uppercase; letter-spacing: 0.4px; }
        .kpi-value { font-size: 15px; font-weight: bold; margin-top: 4px; }
        .kpi.good { border-top-color: #4d7c0f; }
        .kpi.bad { border-top-color: #b3321f; }

        /* Alerts */
        .alerts { margin-top: 14px; }
        .pill { padding: 3px 9px; font-weight: bold; font-size: 9px; }
        .pill-red { background: #f8dcd6; color: #b3321f; }
        .pill-amber { background: #fef3c7; color: #b45309; }

        /* Table */
        h2 { font-size: 11px; margin: 18px 0 6px 0; color: #241c17; }
        .data th {
            background: #241c17;
            color: #ffffff;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            padding: 6px 7px;
            text-align: right;
        }
        .data th.left, .data td.left { text-align: left; }
        .data td { padding: 6px 7px; border-bottom: 1px solid #e8dfd0; text-align: right; }
        .data tr.alt td { background: #f4eee4; }
        .data td.plate { font-weight: bold; }
        .data tfoot td {
            font-weight: bold;
            background: #e8dfd0;
            border-top: 2px solid #241c17;
            border-bottom: none;
        }
        .data tr { page-break-inside: avoid; }

        .note { margin-top: 14px; padding: 7px 10px; background: #fdf3e3; border-left: 3px solid #b45309; color: #78350f; font-size: 8.5px; }

        /* Footer */
        .footer { position: fixed; left: 0; right: 0; bottom: -34px; font-size: 8px; color: #7b6e60; border-top: 1px solid #e8dfd0; padding-top: 6px; }
        .pagenum:before { content: counter(page); }
    </style>
</head>
<body>
    @php
        $money = fn (float|int $value): string => ($value < 0 ? '-' : '').'₡'.number_format(abs($value), 0);
        $pct = fn (?float $value, int $decimals = 1): string => $value === null ? '—' : number_format($value, $decimals).'%';
        $perKm = fn (?float $value): string => $value === null ? '—' : $money($value);
    @endphp

    <div class="footer">
        <table>
            <tr>
                <td>{{ config('app.name') }} · {{ $title }} · {{ $rangeText }}</td>
                <td class="right">{{ __('Generado el') }} {{ $generatedAt->format('d/m/Y H:i') }} · {{ __('Página') }} <span class="pagenum"></span></td>
            </tr>
        </table>
    </div>

    <table>
        <tr>
            <td style="width: 46px; vertical-align: middle;">
                <img class="logo" src="{{ public_path('apple-touch-icon.png') }}" alt="">
            </td>
            <td style="vertical-align: middle;">
                <div class="brand">{{ config('app.name') }}</div>
                <div class="brand-sub">{{ __('Costo y rentabilidad de la flota') }}</div>
            </td>
            <td style="vertical-align: middle;">
                <div class="title">{{ $title }}</div>
                <div class="range">{{ $rangeText }}</div>
            </td>
        </tr>
    </table>

    <div class="rule"></div>

    <table class="kpis">
        <tr>
            <td class="kpi">
                <div class="kpi-label">{{ __('Ingresos') }}</div>
                <div class="kpi-value">{{ $money($totals['revenue']) }}</div>
            </td>
            <td class="kpi">
                <div class="kpi-label">{{ __('Costo total') }}</div>
                <div class="kpi-value">{{ $money($totals['total_cost']) }}</div>
            </td>
            <td class="kpi {{ $totals['profit_net'] < 0 ? 'bad' : 'good' }}">
                <div class="kpi-label">{{ __('Utilidad neta') }}</div>
                <div class="kpi-value {{ $totals['profit_net'] < 0 ? 'neg' : '' }}">{{ $money($totals['profit_net']) }}</div>
            </td>
            <td class="kpi">
                <div class="kpi-label">{{ __('Margen') }}</div>
                <div class="kpi-value">{{ $pct($totals['margin_pct']) }}</div>
            </td>
            <td class="kpi">
                <div class="kpi-label">{{ __('Costo / km (est.)') }}</div>
                <div class="kpi-value">{{ $perKm($totals['cost_per_km']) }}</div>
            </td>
            <td class="kpi">
                <div class="kpi-label">{{ __('Ingreso / km (est.)') }}</div>
                <div class="kpi-value">{{ $perKm($totals['revenue_per_km']) }}</div>
            </td>
        </tr>
    </table>

    <table class="alerts">
        <tr>
            <td style="width: 1%; white-space: nowrap;"><span class="pill pill-red">{{ $alerts['overdue'] }} {{ __('mantenimientos vencidos') }}</span></td>
            <td style="width: 1%; white-space: nowrap; padding-left: 8px;"><span class="pill pill-amber">{{ $alerts['upcoming'] }} {{ __('próximos a vencer') }}</span></td>
            <td></td>
        </tr>
    </table>

    <h2>{{ __('Detalle por camión') }}</h2>

    <table class="data">
        <thead>
            <tr>
                <th class="left">{{ __('Camión') }}</th>
                <th>{{ __('Ingresos') }}</th>
                <th>{{ __('Km (est.)') }}</th>
                <th>{{ __('Costo total') }}</th>
                <th>{{ __('Utilidad') }}</th>
                <th>{{ __('Margen') }}</th>
                <th>{{ __('Costo/km') }}</th>
                <th>{{ __('Disponib.') }}</th>
                <th>{{ __('Utiliz.') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr class="{{ $loop->even ? 'alt' : '' }}">
                    <td class="left plate">{{ $row['truck']->plate }}</td>
                    <td>{{ $money($row['revenue']) }}</td>
                    <td>{{ number_format($row['km'], 0) }}</td>
                    <td>{{ $money($row['total_cost']) }}</td>
                    <td class="{{ $row['profit_net'] < 0 ? 'neg' : '' }}">{{ $money($row['profit_net']) }}</td>
                    <td>{{ $pct($row['margin_pct']) }}</td>
                    <td>{{ $perKm($row['cost_per_km']) }}</td>
                    <td>{{ number_format($row['availability_pct'], 0) }}%</td>
                    <td>{{ $pct($row['utilization_pct'], 0) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="muted" style="text-align: center;">{{ __('No hay camiones registrados todavía.') }}</td>
                </tr>
            @endforelse
        </tbody>
        @if (count($rows) > 0)
            <tfoot>
                <tr>
                    <td class="left">{{ __('Flota') }}</td>
                    <td>{{ $money($totals['revenue']) }}</td>
                    <td>{{ number_format($totals['km'], 0) }}</td>
                    <td>{{ $money($totals['total_cost']) }}</td>
                    <td class="{{ $totals['profit_net'] < 0 ? 'neg' : '' }}">{{ $money($totals['profit_net']) }}</td>
                    <td>{{ $pct($totals['margin_pct']) }}</td>
                    <td>{{ $perKm($totals['cost_per_km']) }}</td>
                    <td>—</td>
                    <td>—</td>
                </tr>
            </tfoot>
        @endif
    </table>

    <div class="note">
        {{ __('Los km y las métricas por km son un estimado mientras no se capture el odómetro por viaje (ver Rutas). No son valores exactos.') }}
    </div>
</body>
</html>
