<?php

namespace App\Http\Controllers;

use App\Http\Requests\DashboardPdfRequest;
use App\Models\MaintenanceSchedule;
use App\Services\Financial\FinancialCalculator;
use App\Services\Financial\Period;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

/**
 * Downloads the weekly (Thu→Wed, ADR 0003) or calendar-month dashboard
 * summary as a PDF. Same figures as the dashboard — computed by the same
 * `FinancialCalculator` — for the week/month containing `?date=` (today by
 * default).
 */
class DashboardPdfController extends Controller
{
    private const MONTHS = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

    public function __invoke(DashboardPdfRequest $request, string $period, FinancialCalculator $calculator): Response
    {
        $anchor = Carbon::parse($request->validated('date') ?? now()->toDateString());
        $weekly = $period === 'weekly';

        $range = $weekly ? Period::weekContaining($anchor) : Period::monthContaining($anchor);
        $rows = $calculator->fleetSummary($range);

        $rangeText = $weekly
            ? $range->start->format('d/m/Y').' — '.$range->end->format('d/m/Y')
            : ucfirst(self::MONTHS[$range->start->month - 1]).' '.$range->start->year;

        $filename = $weekly
            ? 'resumen-semanal-'.$range->start->format('Y-m-d').'_'.$range->end->format('Y-m-d').'.pdf'
            : 'resumen-mensual-'.$range->start->format('Y-m').'.pdf';

        $title = $weekly ? __('Resumen semanal') : __('Resumen mensual');

        return Pdf::loadView('reports.dashboard-pdf', [
            'title' => $title,
            'rangeText' => $rangeText,
            'totals' => $calculator->fleetTotals($rows),
            'rows' => $rows,
            'alerts' => MaintenanceSchedule::alertCounts(),
            'generatedAt' => now(),
        ])->setPaper('letter', 'landscape')
            ->setOption(['enable_font_subsetting' => true])
            ->addInfo(['Author' => config('app.name'), 'Creator' => config('app.name'), 'Title' => $title.' — '.$rangeText])
            ->download($filename);
    }
}
