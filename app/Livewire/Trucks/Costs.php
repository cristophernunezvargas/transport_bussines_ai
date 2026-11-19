<?php

namespace App\Livewire\Trucks;

use App\Models\FuelRecord;
use App\Models\Maintenance;
use App\Models\TollRecord;
use App\Models\Truck;
use App\Models\TruckExpense;
use App\Models\TruckFixedCost;
use App\Services\Financial\CostCalculator;
use App\Services\Financial\Period;
use App\Services\Financial\ProrationCalculator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Drill-down from the dashboard's per-truck "Costo total": the cost
 * breakdown of one truck over the dashboard's period, with the source
 * records behind every line (the project's traceability rule — every KPI
 * down to the records that produced it).
 *
 * Read-only; every authenticated role may view it, like the dashboard. The
 * period comes from `?from=&to=` (ISO dates); anything invalid falls back to
 * the current Thu→Wed week. Record filters mirror `CostCalculator::forTruck()`
 * so the listed records always add up to the breakdown.
 *
 * @phpstan-import-type CostBreakdown from CostCalculator
 *
 * @property-read Period $range
 * @property-read CostBreakdown $breakdown
 */
#[Title('Costos del camión')]
class Costs extends Component
{
    public Truck $truck;

    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    public function mount(Truck $truck): void
    {
        $this->authorize('view', $truck);

        $this->truck = $truck;
    }

    #[Computed]
    public function range(): Period
    {
        $from = $this->parseDate($this->from);
        $to = $this->parseDate($this->to);

        if ($from === null || $to === null || $from->gt($to)) {
            return Period::weekContaining(now());
        }

        return new Period($from, $to);
    }

    /**
     * @return CostBreakdown
     */
    #[Computed]
    public function breakdown(): array
    {
        return app(CostCalculator::class)->forTruck($this->truck, $this->range);
    }

    /**
     * @return Collection<int, FuelRecord>
     */
    #[Computed]
    public function fuelRecords(): Collection
    {
        return FuelRecord::query()
            ->with('fuelStation')
            ->where('truck_id', $this->truck->id)
            ->whereBetween('occurred_at', [$this->range->start->copy()->startOfDay(), $this->range->end->copy()->endOfDay()])
            ->orderBy('occurred_at')
            ->get();
    }

    /**
     * @return Collection<int, TollRecord>
     */
    #[Computed]
    public function tollRecords(): Collection
    {
        return TollRecord::query()
            ->where('truck_id', $this->truck->id)
            ->whereBetween('occurred_at', [$this->range->start->copy()->startOfDay(), $this->range->end->copy()->endOfDay()])
            ->orderBy('occurred_at')
            ->get();
    }

    /**
     * @return Collection<int, Maintenance>
     */
    #[Computed]
    public function maintenances(): Collection
    {
        return Maintenance::query()
            ->with('provider')
            ->where('truck_id', $this->truck->id)
            ->whereBetween('completion_date', [$this->range->start->toDateString(), $this->range->end->toDateString()])
            ->orderBy('completion_date')
            ->get();
    }

    /**
     * @return Collection<int, TruckExpense>
     */
    #[Computed]
    public function expenses(): Collection
    {
        return TruckExpense::query()
            ->with('costType')
            ->where('truck_id', $this->truck->id)
            ->whereBetween('expense_date', [$this->range->start->toDateString(), $this->range->end->toDateString()])
            ->orderBy('expense_date')
            ->get();
    }

    /**
     * Fixed costs in force during the period, each with the share
     * prorated to it.
     *
     * @return list<array{cost: TruckFixedCost, prorated: float}>
     */
    #[Computed]
    public function fixedCosts(): array
    {
        $proration = app(ProrationCalculator::class);

        $costs = TruckFixedCost::query()
            ->with('costType')
            ->where('truck_id', $this->truck->id)
            ->where('effective_from', '<=', $this->range->end->toDateString())
            ->where(function ($query) {
                $query->whereNull('effective_to')->orWhere('effective_to', '>=', $this->range->start->toDateString());
            })
            ->get()
            ->all();

        return array_values(array_map(fn (TruckFixedCost $cost) => [
            'cost' => $cost,
            'prorated' => round($proration->toPeriod((float) $cost->amount, $cost->billing_cycle, $this->range), 2),
        ], $costs));
    }

    private function parseDate(string $value): ?Carbon
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }

        try {
            $date = Carbon::createFromFormat('Y-m-d', $value)?->startOfDay();
        } catch (\Throwable) {
            return null;
        }

        // `2026-02-31` would silently overflow into March.
        return $date?->format('Y-m-d') === $value ? $date : null;
    }
}
