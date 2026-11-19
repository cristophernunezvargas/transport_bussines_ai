<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\TripStatus;
use App\Models\Route;
use App\Models\Trip;
use App\Models\Truck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardPdfControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('dashboard.pdf', ['period' => 'weekly']))->assertRedirect(route('login'));
    }

    public function test_a_viewer_can_download_the_weekly_pdf_for_the_week_containing_the_date(): void
    {
        $this->actingAs(User::factory()->viewer()->create());

        // 2026-11-12 is a Thursday: the Thu→Wed week runs 2026-11-12 → 2026-11-18.
        $response = $this->get(route('dashboard.pdf', ['period' => 'weekly', 'date' => '2026-11-14']));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $response->assertDownload('resumen-semanal-2026-11-12_2026-11-18.pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_it_downloads_the_monthly_pdf_for_the_calendar_month(): void
    {
        $this->actingAs(User::factory()->viewer()->create());

        $response = $this->get(route('dashboard.pdf', ['period' => 'monthly', 'date' => '2026-02-17']));

        $response->assertOk();
        $response->assertDownload('resumen-mensual-2026-02.pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_the_date_defaults_to_today(): void
    {
        $this->travelTo(Carbon::parse('2026-11-18'));
        $this->actingAs(User::factory()->viewer()->create());

        $this->get(route('dashboard.pdf', ['period' => 'monthly']))
            ->assertDownload('resumen-mensual-2026-11.pdf');
    }

    public function test_a_report_with_trips_and_trucks_renders(): void
    {
        $truck = Truck::factory()->create();
        Trip::factory()->create([
            'truck_id' => $truck->id,
            'route_id' => Route::factory()->create(['standard_km' => 100])->id,
            'status' => TripStatus::Completed,
            'actual_end' => '2026-11-13',
            'distance' => 100,
            'price' => 60000,
        ]);
        $this->actingAs(User::factory()->viewer()->create());

        $this->get(route('dashboard.pdf', ['period' => 'weekly', 'date' => '2026-11-13']))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_an_unknown_period_is_not_found(): void
    {
        $this->actingAs(User::factory()->viewer()->create());

        $this->get('/dashboard/pdf/yearly')->assertNotFound();
    }

    public function test_an_invalid_date_is_rejected(): void
    {
        $this->actingAs(User::factory()->viewer()->create());

        $this->get(route('dashboard.pdf', ['period' => 'weekly', 'date' => 'not-a-date']))
            ->assertSessionHasErrors('date');
    }
}
