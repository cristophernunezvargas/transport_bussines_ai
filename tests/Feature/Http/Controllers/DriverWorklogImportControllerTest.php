<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Driver;
use App\Models\DriverWorklog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class DriverWorklogImportControllerTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'test-token-0123456789';

    private User $author;

    protected function setUp(): void
    {
        parent::setUp();

        $this->author = User::factory()->admin()->create(['email' => 'n8n@example.test']);

        config([
            'integrations.n8n.token' => self::TOKEN,
            'integrations.n8n.user_email' => 'n8n@example.test',
        ]);
    }

    /**
     * @param  list<array{driver_name: string, work_date: string, hours: int|float}>  $entries
     */
    private function import(array $entries, ?string $token = self::TOKEN): TestResponse
    {
        return $this->withHeaders($token === null ? [] : ['Authorization' => 'Bearer '.$token])
            ->postJson('/api/integrations/driver-worklogs', ['entries' => $entries]);
    }

    public function test_requests_without_a_valid_token_are_refused(): void
    {
        $entry = [['driver_name' => 'Iván', 'work_date' => '2026-11-23', 'hours' => 10.5]];

        $this->import($entry, null)->assertUnauthorized();
        $this->import($entry, 'wrong-token')->assertUnauthorized();
        $this->assertDatabaseCount('driver_worklogs', 0);
    }

    public function test_the_route_is_closed_when_no_token_is_configured(): void
    {
        config(['integrations.n8n.token' => null]);

        $this->import([['driver_name' => 'Iván', 'work_date' => '2026-11-23', 'hours' => 10.5]], 'anything')->assertUnauthorized();
        $this->import([['driver_name' => 'Iván', 'work_date' => '2026-11-23', 'hours' => 10.5]], '')->assertUnauthorized();
    }

    public function test_daily_entries_become_one_weekly_total_per_driver(): void
    {
        $driver = Driver::factory()->create(['name' => 'Iván', 'hourly_rate' => 1800]);

        // 2026-11-23 (Mon) and 2026-11-25 (Wed) are in the Thu 19 -> Wed 25 week; 2026-11-26 starts the next one.
        $this->import([
            ['driver_name' => 'Iván', 'work_date' => '2026-11-23', 'hours' => 10.5],
            ['driver_name' => 'Iván', 'work_date' => '2026-11-25', 'hours' => 8],
            ['driver_name' => 'Iván', 'work_date' => '2026-11-26', 'hours' => 9],
        ])->assertOk()->assertJson(['created' => 2, 'updated' => 0, 'unchanged' => 0, 'rejected' => []]);

        $first = DriverWorklog::query()->where('driver_id', $driver->id)->whereDate('work_date', '2026-11-19')->firstOrFail();
        $this->assertSame('18.50', $first->hours);
        $this->assertSame('1800.0000', $first->hourly_rate_snapshot);
        $this->assertSame('33300.00', $first->computed_pay);
        $this->assertSame($this->author->id, $first->created_by);
        $this->assertStringStartsWith('Importado de n8n', (string) $first->notes);

        $second = DriverWorklog::query()->where('driver_id', $driver->id)->whereDate('work_date', '2026-11-26')->firstOrFail();
        $this->assertSame('9.00', $second->hours);
    }

    public function test_names_match_ignoring_case_accents_and_spaces(): void
    {
        Driver::factory()->create(['name' => 'Iván', 'hourly_rate' => 1800]);

        $this->import([['driver_name' => '  ivan ', 'work_date' => '2026-11-23', 'hours' => 5]])
            ->assertOk()->assertJson(['created' => 1, 'rejected' => []]);
    }

    public function test_resending_the_same_week_changes_nothing(): void
    {
        Driver::factory()->create(['name' => 'Luis', 'hourly_rate' => 1800]);
        $entries = [['driver_name' => 'Luis', 'work_date' => '2026-11-23', 'hours' => 10]];

        $this->import($entries)->assertOk()->assertJson(['created' => 1]);
        $this->import($entries)->assertOk()->assertJson(['created' => 0, 'updated' => 0, 'unchanged' => 1]);

        $this->assertDatabaseCount('driver_worklogs', 1);
    }

    public function test_a_corrected_total_updates_the_week_and_keeps_a_manual_note(): void
    {
        $driver = Driver::factory()->create(['name' => 'Luis', 'hourly_rate' => 2000]);
        DriverWorklog::factory()->create([
            'driver_id' => $driver->id,
            'work_date' => '2026-11-19',
            'hours' => 40,
            'hourly_rate_snapshot' => 1000,
            'computed_pay' => 40000,
            'notes' => 'Semana con feriado',
        ]);

        $this->import([['driver_name' => 'Luis', 'work_date' => '2026-11-23', 'hours' => 12]])
            ->assertOk()->assertJson(['created' => 0, 'updated' => 1]);

        $worklog = DriverWorklog::query()->where('driver_id', $driver->id)->firstOrFail();
        $this->assertSame('12.00', $worklog->hours);
        $this->assertSame('24000.00', $worklog->computed_pay);
        $this->assertSame('Semana con feriado', $worklog->notes);
    }

    public function test_unknown_and_ambiguous_drivers_are_rejected_while_the_rest_are_imported(): void
    {
        Driver::factory()->create(['name' => 'Luis']);
        Driver::factory()->count(2)->create(['name' => 'Carlos']);

        $response = $this->import([
            ['driver_name' => 'Luis', 'work_date' => '2026-11-23', 'hours' => 8],
            ['driver_name' => 'Pedro', 'work_date' => '2026-11-23', 'hours' => 8],
            ['driver_name' => 'Carlos', 'work_date' => '2026-11-23', 'hours' => 8],
        ])->assertOk()->assertJson(['created' => 1]);

        $reasons = collect($response->json('rejected'))->pluck('reason', 'driver_name');
        $this->assertSame('No hay un conductor con ese nombre.', $reasons['Pedro']);
        $this->assertSame('Hay más de un conductor con ese nombre.', $reasons['Carlos']);
        $this->assertDatabaseCount('driver_worklogs', 1);
    }

    public function test_a_week_over_the_physical_limit_is_rejected(): void
    {
        Driver::factory()->create(['name' => 'Luis']);

        // 8 days * 24h can't be in one Thu->Wed week once split, so repeat one day.
        $entries = array_fill(0, 8, ['driver_name' => 'Luis', 'work_date' => '2026-11-23', 'hours' => 24]);

        $this->import($entries)->assertOk()->assertJsonPath('rejected.0.reason', 'La semana suma más de 168 horas.');
        $this->assertDatabaseCount('driver_worklogs', 0);
    }

    public function test_an_invalid_payload_is_a_validation_error(): void
    {
        $this->import([['driver_name' => 'Luis', 'work_date' => '23/11/2026', 'hours' => 30]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['entries.0.work_date', 'entries.0.hours']);

        $this->withHeaders(['Authorization' => 'Bearer '.self::TOKEN])
            ->postJson('/api/integrations/driver-worklogs', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('entries');
    }

    public function test_it_reports_when_the_integration_user_is_missing(): void
    {
        config(['integrations.n8n.user_email' => 'nobody@example.test']);

        $this->import([['driver_name' => 'Luis', 'work_date' => '2026-11-23', 'hours' => 8]])->assertStatus(503);
    }
}
