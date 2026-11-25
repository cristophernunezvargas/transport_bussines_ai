<?php

namespace Tests\Feature\Services\Financial;

use App\Enums\TripStatus;
use App\Models\Customer;
use App\Models\FuelRecord;
use App\Models\FuelStation;
use App\Models\RateAgreement;
use App\Models\Route;
use App\Models\Trip;
use App\Models\Truck;
use App\Services\Financial\FinancialCalculator;
use App\Services\Financial\Period;
use App\Services\Financial\ProfitabilityReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ProfitabilityReportTest extends TestCase
{
    use RefreshDatabase;

    private Period $period;

    protected function setUp(): void
    {
        parent::setUp();

        $this->period = new Period(Carbon::parse('2026-09-10'), Carbon::parse('2026-09-16'));
    }

    private function fuel(Truck $truck, float $total): void
    {
        FuelRecord::factory()->for($truck)->for(FuelStation::factory()->create(), 'fuelStation')->create([
            'occurred_at' => '2026-09-11 08:00:00',
            'total' => $total,
        ]);
    }

    private function trip(Truck $truck, Route $route, float $km, float $price, ?RateAgreement $agreement = null): Trip
    {
        return Trip::factory()->create([
            'truck_id' => $truck->id,
            'route_id' => $route->id,
            'rate_agreement_id' => $agreement?->id,
            'status' => TripStatus::Completed,
            'actual_end' => '2026-09-12',
            'distance' => $km,
            'price' => $price,
        ]);
    }

    public function test_a_trucks_cost_is_split_among_its_trips_by_km_and_adds_up_to_the_fleet(): void
    {
        $truckA = Truck::factory()->create();
        $truckB = Truck::factory()->create();
        $near = Route::factory()->create(['name' => 'San José - Cartago']);
        $far = Route::factory()->create(['name' => 'San José - Limón']);

        $this->fuel($truckA, 40000);
        $this->fuel($truckB, 10000); // a truck with costs but no trips
        $this->trip($truckA, $near, 100, 80000);
        $this->trip($truckA, $far, 300, 150000);

        $report = app(ProfitabilityReport::class)->breakdown($this->period);

        // Truck A's 40,000 splits 100:300 -> 10,000 and 30,000.
        $this->assertSame(['San José - Limón', 'San José - Cartago'], array_column($report['routes'], 'name'));
        $this->assertSame(120000.0, $report['routes'][0]['profit']);
        $this->assertSame(30000.0, $report['routes'][0]['cost']);
        $this->assertSame(70000.0, $report['routes'][1]['profit']);
        $this->assertSame(10000.0, $report['routes'][1]['cost']);

        $this->assertSame(230000.0, $report['revenue']);
        $this->assertSame(50000.0, $report['total_cost']);
        $this->assertSame(40000.0, $report['allocated_cost']);
        $this->assertSame(10000.0, $report['unallocated_cost']);

        // It reconciles with the dashboard's fleet net profit.
        $calculator = app(FinancialCalculator::class);
        $fleet = $calculator->fleetTotals($calculator->fleetSummary($this->period));
        $this->assertSame($fleet['profit_net'], $report['profit_net']);
        $this->assertSame($fleet['total_cost'], $report['total_cost']);
    }

    public function test_trips_are_attributed_to_customers_and_unattributed_ones_are_not_dropped(): void
    {
        $truck = Truck::factory()->create();
        $route = Route::factory()->create();
        $customer = Customer::factory()->create(['name' => 'Bananera del Sur']);
        $agreement = RateAgreement::factory()->create(['customer_id' => $customer->id, 'route_id' => $route->id]);

        $this->fuel($truck, 20000);
        $this->trip($truck, $route, 100, 90000, $agreement);
        $this->trip($truck, $route, 100, 60000);

        $customers = app(ProfitabilityReport::class)->breakdown($this->period)['customers'];

        $named = collect($customers)->firstWhere('key', $customer->id);
        $this->assertSame('Bananera del Sur', $named['name']);
        $this->assertSame(90000.0, $named['revenue']);
        $this->assertSame(10000.0, $named['cost']);
        $this->assertSame(80000.0, $named['profit']);
        $this->assertSame(88.89, $named['margin_pct']);

        $none = collect($customers)->firstWhere('key', null);
        $this->assertSame('Sin cliente asignado', $none['name']);
        $this->assertSame(60000.0, $none['revenue']);
    }

    public function test_trips_outside_the_period_and_not_completed_are_ignored(): void
    {
        $truck = Truck::factory()->create();
        $route = Route::factory()->create();

        Trip::factory()->create(['truck_id' => $truck->id, 'route_id' => $route->id, 'status' => TripStatus::Completed, 'actual_end' => '2026-09-01', 'price' => 99999, 'distance' => 100]);
        Trip::factory()->create(['truck_id' => $truck->id, 'route_id' => $route->id, 'status' => TripStatus::Planned, 'actual_end' => '2026-09-12', 'price' => 88888, 'distance' => 100]);

        $report = app(ProfitabilityReport::class)->breakdown($this->period);

        $this->assertSame(0, $report['trips']);
        $this->assertSame([], $report['routes']);
        $this->assertSame(0.0, $report['revenue']);
    }
}
