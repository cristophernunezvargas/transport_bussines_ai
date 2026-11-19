<?php

namespace Tests\Feature\Livewire\Trucks;

use App\Enums\BillingCycle;
use App\Enums\MaintenanceType;
use App\Livewire\Trucks\Costs;
use App\Models\CostType;
use App\Models\FuelRecord;
use App\Models\FuelStation;
use App\Models\Maintenance;
use App\Models\MaintenanceProvider;
use App\Models\Truck;
use App\Models\TruckExpense;
use App\Models\TruckFixedCost;
use App\Models\User;
use App\Services\Financial\Period;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CostsTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{from: string, to: string} */
    private function week(): array
    {
        return ['from' => '2026-09-10', 'to' => '2026-09-16'];
    }

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $truck = Truck::factory()->create();

        $this->get(route('trucks.costs', ['truck' => $truck->id] + $this->week()))
            ->assertRedirect(route('login'));
    }

    public function test_a_viewer_sees_the_breakdown_and_the_source_records_of_that_truck(): void
    {
        $truck = Truck::factory()->create(['plate' => 'CL-4321']);
        $other = Truck::factory()->create();
        $station = FuelStation::factory()->create(['name' => 'Gasolinera Central']);
        $provider = MaintenanceProvider::factory()->create(['name' => 'Taller Los Pinos']);
        $costType = CostType::factory()->create(['name' => 'Lavado']);

        FuelRecord::factory()->for($truck)->for($station, 'fuelStation')->create(['occurred_at' => '2026-09-11 08:00:00', 'total' => 50000]);
        FuelRecord::factory()->for($other)->for($station, 'fuelStation')->create(['occurred_at' => '2026-09-11 08:00:00', 'total' => 77777]);
        Maintenance::factory()->for($truck)->for($provider, 'provider')->create([
            'type' => MaintenanceType::Preventive,
            'completion_date' => '2026-09-12',
            'total' => 15000,
        ]);
        TruckExpense::factory()->for($truck)->for($costType, 'costType')->create(['expense_date' => '2026-09-13', 'amount' => 2000]);

        $this->actingAs(User::factory()->viewer()->create());

        $component = Livewire::withQueryParams($this->week())->test(Costs::class, ['truck' => $truck]);

        $component
            ->assertOk()
            ->assertSee('CL-4321')
            ->assertSee('10/09/2026')
            ->assertSee('Gasolinera Central')
            ->assertSee('Taller Los Pinos')
            ->assertSee('Lavado')
            ->assertSee('₡50,000')
            ->assertDontSee('77,777');

        $this->assertSame(67000.0, $component->instance()->breakdown['direct_cost']);
    }

    public function test_listed_records_add_up_to_the_breakdown_lines(): void
    {
        $truck = Truck::factory()->create();
        $station = FuelStation::factory()->create();

        FuelRecord::factory()->for($truck)->for($station, 'fuelStation')->count(2)->create(['occurred_at' => '2026-09-12 08:00:00', 'total' => 10000]);

        $this->actingAs(User::factory()->viewer()->create());
        $instance = Livewire::withQueryParams($this->week())->test(Costs::class, ['truck' => $truck])->instance();

        $this->assertSame($instance->breakdown['fuel'], round((float) $instance->fuelRecords->sum('total'), 2));
    }

    public function test_records_outside_the_period_are_excluded(): void
    {
        $truck = Truck::factory()->create();
        $station = FuelStation::factory()->create();

        FuelRecord::factory()->for($truck)->for($station, 'fuelStation')->create(['occurred_at' => '2026-09-01 08:00:00', 'total' => 99999]);

        $this->actingAs(User::factory()->viewer()->create());
        $instance = Livewire::withQueryParams($this->week())->test(Costs::class, ['truck' => $truck])->instance();

        $this->assertCount(0, $instance->fuelRecords);
        $this->assertSame(0.0, $instance->breakdown['fuel']);
    }

    public function test_fixed_costs_show_their_prorated_share(): void
    {
        $truck = Truck::factory()->create();
        $costType = CostType::factory()->create(['name' => 'Seguro']);
        TruckFixedCost::factory()->for($truck)->for($costType, 'costType')->create([
            'amount' => 30000,
            'billing_cycle' => BillingCycle::Monthly,
            'effective_from' => '2026-01-01',
            'effective_to' => null,
        ]);

        $this->actingAs(User::factory()->viewer()->create());
        $component = Livewire::withQueryParams($this->week())->test(Costs::class, ['truck' => $truck]);

        $component->assertSee('Seguro');
        $this->assertCount(1, $component->instance()->fixedCosts);
        $this->assertSame($component->instance()->breakdown['truck_fixed_costs'], $component->instance()->fixedCosts[0]['prorated']);
    }

    public function test_an_invalid_period_falls_back_to_the_current_week(): void
    {
        $truck = Truck::factory()->create();
        $this->actingAs(User::factory()->viewer()->create());

        foreach ([['from' => 'abc', 'to' => 'xyz'], ['from' => '2026-02-31', 'to' => '2026-03-05'], ['from' => '2026-09-16', 'to' => '2026-09-10'], []] as $params) {
            $range = Livewire::withQueryParams($params)->test(Costs::class, ['truck' => $truck])->instance()->range;

            $this->assertEquals(Period::weekContaining(now())->start->toDateString(), $range->start->toDateString());
        }
    }
}
