<?php

namespace App\Services\Financial;

use App\Enums\TripStatus;
use App\Models\Customer;
use App\Models\Route;
use App\Models\Trip;
use App\Models\Truck;

/**
 * Profitability by route and by customer for a period, built so it adds up
 * to the dashboard (docs/finance/financial-model.md §J.8).
 *
 * Each truck's **total** cost for the period (direct + indirect, exactly the
 * figure `FinancialCalculator::fleetSummary()` uses) is split among that
 * truck's completed trips by their share of the truck's km. Splitting only
 * the trip-linked fuel/toll/expenses would leave out everything not tied to a
 * trip — most fuel, maintenance, tires — and overstate every route's profit.
 *
 * The cost of a truck that did no trips in the period has nobody to be
 * charged to; it is reported as `unallocated_cost`, so
 * `revenue - total_cost` always equals the fleet net profit on the dashboard.
 * Trips with neither a rate agreement nor an invoice cannot be tied to a
 * customer and appear under a "no customer" row (key `null`) instead of being
 * guessed or dropped.
 *
 * @phpstan-type ProfitRow array{
 *     key: int|null, name: string, trips: int, km: float,
 *     revenue: float, cost: float, profit: float,
 *     margin_pct: ?float, profit_per_km: ?float,
 * }
 * @phpstan-type Accumulator array{trips: int, km: float, revenue: float, cost: float}
 * @phpstan-type ProfitabilityBreakdown array{
 *     routes: list<ProfitRow>, customers: list<ProfitRow>,
 *     trips: int, revenue: float, total_cost: float, allocated_cost: float,
 *     unallocated_cost: float, profit_net: float,
 * }
 */
class ProfitabilityReport
{
    public function __construct(
        private readonly CostCalculator $costCalculator,
        private readonly FinancialCalculator $financialCalculator,
    ) {}

    /**
     * @return ProfitabilityBreakdown
     */
    public function breakdown(Period $period): array
    {
        $trucks = Truck::query()->get();
        $costs = $this->costCalculator->forFleet($trucks, $period);

        $trips = Trip::query()
            ->where('status', TripStatus::Completed)
            ->whereIn('truck_id', $trucks->modelKeys())
            ->whereBetween('actual_end', [$period->start->copy()->startOfDay(), $period->end->copy()->endOfDay()])
            ->with(['rateAgreement', 'invoiceTrip.invoice'])
            ->get();

        $kmByTruck = [];

        foreach ($trips as $trip) {
            $kmByTruck[$trip->truck_id] = ($kmByTruck[$trip->truck_id] ?? 0.0) + (float) $trip->distance;
        }

        /** @var array<int, Accumulator> $byRoute */
        $byRoute = [];
        /** @var array<int|string, Accumulator> $byCustomer */
        $byCustomer = [];
        $revenue = 0.0;
        $allocated = 0.0;

        foreach ($trips as $trip) {
            $km = (float) $trip->distance;
            $truckKm = $kmByTruck[$trip->truck_id];
            $cost = $truckKm > 0 ? round($costs[$trip->truck_id]['total_cost'] * $km / $truckKm, 2) : 0.0;
            $price = (float) $trip->price;

            $revenue += $price;
            $allocated += $cost;

            $this->accumulate($byRoute, $trip->route_id, $km, $price, $cost);
            $this->accumulate($byCustomer, $this->financialCalculator->resolveTripCustomerId($trip) ?? 'none', $km, $price, $cost);
        }

        $totalCost = array_sum(array_column($costs, 'total_cost'));

        return [
            'routes' => $this->rows($byRoute, Route::query()->whereIn('id', array_keys($byRoute))->pluck('name', 'id')->all(), 'Ruta eliminada'),
            'customers' => $this->rows(
                $byCustomer,
                Customer::query()->whereIn('id', array_filter(array_keys($byCustomer), 'is_int'))->pluck('name', 'id')->all(),
                'Cliente eliminado',
            ),
            'trips' => $trips->count(),
            'revenue' => round($revenue, 2),
            'total_cost' => round($totalCost, 2),
            'allocated_cost' => round($allocated, 2),
            'unallocated_cost' => round($totalCost - $allocated, 2),
            'profit_net' => round($revenue - $totalCost, 2),
        ];
    }

    /**
     * @param  array<int|string, Accumulator>  $groups
     */
    private function accumulate(array &$groups, int|string $key, float $km, float $price, float $cost): void
    {
        $group = $groups[$key] ?? ['trips' => 0, 'km' => 0.0, 'revenue' => 0.0, 'cost' => 0.0];

        $groups[$key] = [
            'trips' => $group['trips'] + 1,
            'km' => $group['km'] + $km,
            'revenue' => $group['revenue'] + $price,
            'cost' => $group['cost'] + $cost,
        ];
    }

    /**
     * Rows ordered from most to least profitable.
     *
     * @param  array<int|string, Accumulator>  $groups
     * @param  array<int|string, string>  $names
     * @return list<ProfitRow>
     */
    private function rows(array $groups, array $names, string $missingName): array
    {
        $rows = [];

        foreach ($groups as $key => $group) {
            $revenue = round($group['revenue'], 2);
            $cost = round($group['cost'], 2);
            $profit = round($revenue - $cost, 2);
            $km = round($group['km'], 2);

            $rows[] = [
                'key' => is_int($key) ? $key : null,
                'name' => is_int($key) ? ($names[$key] ?? $missingName) : 'Sin cliente asignado',
                'trips' => $group['trips'],
                'km' => $km,
                'revenue' => $revenue,
                'cost' => $cost,
                'profit' => $profit,
                'margin_pct' => $revenue > 0 ? round($profit / $revenue * 100, 2) : null,
                'profit_per_km' => $km > 0 ? round($profit / $km, 2) : null,
            ];
        }

        usort($rows, fn (array $a, array $b) => $b['profit'] <=> $a['profit']);

        return $rows;
    }
}
