<?php

namespace Tests\Feature\Livewire\Profitability;

use App\Enums\TripStatus;
use App\Livewire\Profitability\Index;
use App\Models\Route;
use App\Models\Trip;
use App\Models\Truck;
use App\Models\User;
use App\Services\Financial\Period;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('profitability.index'))->assertRedirect(route('login'));
    }

    public function test_a_viewer_sees_routes_with_completed_trips_this_week(): void
    {
        $route = Route::factory()->create(['name' => 'Ruta del Sur']);
        Trip::factory()->create([
            'truck_id' => Truck::factory()->create()->id,
            'route_id' => $route->id,
            'status' => TripStatus::Completed,
            'actual_end' => Period::weekContaining(now())->start->copy()->addDay(),
            'distance' => 100,
            'price' => 75000,
        ]);

        $this->actingAs(User::factory()->viewer()->create());

        Livewire::test(Index::class)
            ->assertOk()
            ->assertSee('Ruta del Sur')
            ->assertSee('₡75,000')
            ->assertSee('Sin cliente asignado');
    }

    public function test_the_period_can_be_switched_and_navigated(): void
    {
        $this->actingAs(User::factory()->viewer()->create());

        $component = Livewire::test(Index::class);
        $this->assertSame(7, $component->instance()->period->days());

        $component->call('setMode', 'month');
        $this->assertSame(now()->startOfMonth()->toDateString(), $component->instance()->period->start->toDateString());

        $component->call('setMode', 'year')->call('previous');
        $this->assertSame((string) (now()->year - 1), $component->instance()->periodLabel);

        $component->call('today');
        $this->assertSame((string) now()->year, $component->instance()->periodLabel);
    }

    public function test_an_unknown_period_mode_is_ignored(): void
    {
        $this->actingAs(User::factory()->viewer()->create());

        Livewire::test(Index::class)->call('setMode', 'decade')->assertSet('mode', 'week');
    }
}
