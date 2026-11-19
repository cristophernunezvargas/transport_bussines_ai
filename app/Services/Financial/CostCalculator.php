<?php

namespace App\Services\Financial;

use App\Models\FixedCostAllocation;
use App\Models\FuelRecord;
use App\Models\Maintenance;
use App\Models\TollRecord;
use App\Models\Truck;
use App\Models\TruckExpense;
use App\Models\TruckFixedCost;
use Illuminate\Support\Collection;

/**
 * Direct and indirect cost breakdown for a truck over a period, per
 * docs/finance/financial-model.md §J.1. There is no capital-cost
 * (depreciation / financing) line — the owner explicitly omitted it.
 *
 * @phpstan-type CostBreakdown array{
 *     fuel: float, tolls: float, maintenance: float, tires: float,
 *     truck_expenses: float, direct_cost: float,
 *     truck_fixed_costs: float, allocated_overhead: float, indirect_cost: float,
 *     total_cost: float,
 * }
 * @phpstan-type CostInputs array{
 *     fuel: Collection<int, mixed>, tolls: Collection<int, mixed>,
 *     maintenance: Collection<int, mixed>, truck_expenses: Collection<int, mixed>,
 *     fixed_costs: array<int, list<TruckFixedCost>>,
 * }
 */
class CostCalculator
{
    public function __construct(
        private readonly TireCostEstimator $tireCostEstimator,
        private readonly OverheadAllocationService $overheadAllocation,
        private readonly ProrationCalculator $proration,
    ) {}

    /**
     * @return CostBreakdown
     */
    public function forTruck(Truck $truck, Period $period): array
    {
        return $this->breakdown(
            $truck,
            $period,
            $this->inputs($period, $truck->id),
            $this->overheadAllocation->allocatedAmount($truck, $period),
        );
    }

    /**
     * The breakdown of every given truck for the period. Reads each cost
     * source once, grouped by truck, and allocates overhead once, instead of
     * querying per truck — what the dashboard needs for the whole fleet.
     *
     * @param  Collection<int, Truck>  $trucks
     * @return array<int, CostBreakdown> keyed by truck id
     */
    public function forFleet(Collection $trucks, Period $period): array
    {
        $inputs = $this->inputs($period, null);
        $overhead = $this->overheadAllocation->allocate($period)
            ->mapWithKeys(fn (FixedCostAllocation $allocation) => [$allocation->truck_id => (float) $allocation->amount]);

        $breakdowns = [];

        foreach ($trucks as $truck) {
            $breakdowns[$truck->id] = $this->breakdown($truck, $period, $inputs, (float) ($overhead[$truck->id] ?? 0));
        }

        return $breakdowns;
    }

    /**
     * Cost sources for the period, grouped by truck (or for one truck).
     *
     * @return CostInputs
     */
    private function inputs(Period $period, ?int $truckId): array
    {
        $start = $period->start->copy()->startOfDay();
        $end = $period->end->copy()->endOfDay();
        $from = $period->start->toDateString();
        $to = $period->end->toDateString();

        $fixedCosts = [];

        $costs = TruckFixedCost::query()
            ->when($truckId, fn ($query) => $query->where('truck_id', $truckId))
            ->where('effective_from', '<=', $to)
            ->where(function ($query) use ($from) {
                $query->whereNull('effective_to')->orWhere('effective_to', '>=', $from);
            })
            ->get();

        foreach ($costs as $cost) {
            $fixedCosts[$cost->truck_id][] = $cost;
        }

        return [
            'fuel' => FuelRecord::query()
                ->when($truckId, fn ($query) => $query->where('truck_id', $truckId))
                ->whereBetween('occurred_at', [$start, $end])
                ->selectRaw('truck_id, sum(total) as amount')->groupBy('truck_id')->pluck('amount', 'truck_id'),
            'tolls' => TollRecord::query()
                ->when($truckId, fn ($query) => $query->where('truck_id', $truckId))
                ->whereBetween('occurred_at', [$start, $end])
                ->selectRaw('truck_id, sum(amount) as amount')->groupBy('truck_id')->pluck('amount', 'truck_id'),
            'maintenance' => Maintenance::query()
                ->when($truckId, fn ($query) => $query->where('truck_id', $truckId))
                ->whereBetween('completion_date', [$from, $to])
                ->selectRaw('truck_id, sum(total) as amount')->groupBy('truck_id')->pluck('amount', 'truck_id'),
            'truck_expenses' => TruckExpense::query()
                ->when($truckId, fn ($query) => $query->where('truck_id', $truckId))
                ->whereBetween('expense_date', [$from, $to])
                ->selectRaw('truck_id, sum(amount) as amount')->groupBy('truck_id')->pluck('amount', 'truck_id'),
            'fixed_costs' => $fixedCosts,
        ];
    }

    /**
     * @param  CostInputs  $inputs
     * @return CostBreakdown
     */
    private function breakdown(Truck $truck, Period $period, array $inputs, float $allocatedOverhead): array
    {
        $fuel = (float) ($inputs['fuel'][$truck->id] ?? 0);
        $tolls = (float) ($inputs['tolls'][$truck->id] ?? 0);
        $maintenance = (float) ($inputs['maintenance'][$truck->id] ?? 0);
        $truckExpenses = (float) ($inputs['truck_expenses'][$truck->id] ?? 0);
        $tires = $this->tireCostEstimator->costFor($truck, $period);

        $directCost = round($fuel + $tolls + $maintenance + $tires + $truckExpenses, 2);

        $truckFixedCosts = 0.0;

        foreach ($inputs['fixed_costs'][$truck->id] ?? [] as $cost) {
            $truckFixedCosts += $this->proration->toPeriod((float) $cost->amount, $cost->billing_cycle, $period);
        }

        $indirectCost = round($truckFixedCosts + $allocatedOverhead, 2);

        return [
            'fuel' => round($fuel, 2),
            'tolls' => round($tolls, 2),
            'maintenance' => round($maintenance, 2),
            'tires' => round($tires, 2),
            'truck_expenses' => round($truckExpenses, 2),
            'direct_cost' => $directCost,
            'truck_fixed_costs' => round($truckFixedCosts, 2),
            'allocated_overhead' => round($allocatedOverhead, 2),
            'indirect_cost' => $indirectCost,
            'total_cost' => round($directCost + $indirectCost, 2),
        ];
    }
}
