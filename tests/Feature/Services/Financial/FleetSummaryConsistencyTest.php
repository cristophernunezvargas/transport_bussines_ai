<?php

namespace Tests\Feature\Services\Financial;

use App\Enums\BillingCycle;
use App\Enums\MaintenanceType;
use App\Enums\TripStatus;
use App\Models\CostType;
use App\Models\FuelRecord;
use App\Models\FuelStation;
use App\Models\Maintenance;
use App\Models\MaintenanceProvider;
use App\Models\OverheadCost;
use App\Models\Trip;
use App\Models\Truck;
use App\Models\TruckExpense;
use App\Models\TruckFixedCost;
use App\Services\Financial\FinancialCalculator;
use App\Services\Financial\Period;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * `fleetSummary()` reads each cost source once for the whole fleet instead of
 * per truck. It must still produce exactly what `truckSummary()` does.
 */
class FleetSummaryConsistencyTest extends TestCase
{
    use RefreshDatabase;

    private function seedFleet(int $trucks): Period
    {
        $period = new Period(Carbon::parse('2026-09-10'), Carbon::parse('2026-09-16'));
        $station = FuelStation::factory()->create();
        $provider = MaintenanceProvider::factory()->create();
        $costType = CostType::factory()->create();

        OverheadCost::factory()->create(['amount' => 70000, 'billing_cycle' => BillingCycle::Monthly, 'effective_from' => '2026-01-01', 'effective_to' => null]);

        Truck::factory()->count($trucks)->create()->each(function (Truck $truck, int $index) use ($period, $station, $provider, $costType) {
            Trip::factory()->count($index + 1)->create([
                'truck_id' => $truck->id,
                'status' => TripStatus::Completed,
                'actual_end' => $period->start->copy()->addDays($index),
                'distance' => 100,
                'price' => 50000,
            ]);
            FuelRecord::factory()->for($truck)->for($station, 'fuelStation')->create(['occurred_at' => '2026-09-11 08:00:00', 'total' => 10000 * ($index + 1)]);
            TruckExpense::factory()->for($truck)->for($costType, 'costType')->create(['expense_date' => '2026-09-12', 'amount' => 2500]);
            TruckFixedCost::factory()->for($truck)->for($costType, 'costType')->create(['amount' => 30000, 'billing_cycle' => BillingCycle::Monthly, 'effective_from' => '2026-01-01', 'effective_to' => null]);

            if ($index === 1) {
                Maintenance::factory()->for($truck)->for($provider, 'provider')->create([
                    'type' => MaintenanceType::Corrective,
                    'completion_date' => '2026-09-13',
                    'total' => 40000,
                    'downtime_days' => 2,
                ]);
            }
        });

        return $period;
    }

    public function test_fleet_rows_match_the_per_truck_summaries(): void
    {
        $period = $this->seedFleet(3);
        $calculator = app(FinancialCalculator::class);

        foreach ($calculator->fleetSummary($period) as $row) {
            $single = $calculator->truckSummary($row['truck'], $period);

            foreach ($single as $key => $value) {
                $this->assertEqualsWithDelta($value, $row[$key], 0.01, "{$key} differs for truck {$row['truck']->id}");
            }
        }
    }

    public function test_fleet_summary_queries_do_not_grow_with_the_square_of_the_fleet(): void
    {
        $period = $this->seedFleet(8);

        DB::enableQueryLog();
        app(FinancialCalculator::class)->fleetSummary($period);

        // Allocating once per truck made this ~N² (hundreds of queries for 8
        // trucks); batched, only the per-truck tire estimate scales with N.
        $this->assertLessThan(60, count(DB::getQueryLog()));
    }
}
