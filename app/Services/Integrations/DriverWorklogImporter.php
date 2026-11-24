<?php

namespace App\Services\Integrations;

use App\Models\Driver;
use App\Models\DriverWorklog;
use App\Models\User;
use App\Services\Financial\Period;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Turns the daily hours n8n records into the app's weekly `driver_worklogs`
 * (one total per driver per Thu→Wed week, ADR 0003) so nobody keys hours twice.
 *
 * Idempotent by design: the weekly total is *set* from the days received,
 * never added to, so re-sending the same week is a no-op. That makes it
 * essential that the caller sends every day of a week it touches — a partial
 * week would replace the total with a partial sum. A week already entered by
 * hand is overwritten by the import (n8n's sheet is then the source of the
 * hours); hand-written notes are kept.
 *
 * @phpstan-type Rejection array{driver_name: string, week_start: string, reason: string}
 * @phpstan-type ImportResult array{created: int, updated: int, unchanged: int, rejected: list<Rejection>}
 */
class DriverWorklogImporter
{
    /** Marks notes this importer wrote, so it never overwrites a person's note. */
    private const NOTE_PREFIX = 'Importado de n8n';

    /** A week can't hold more than 7 × 24 hours. */
    private const MAX_WEEK_HOURS = 168;

    /**
     * @param  list<array{driver_name: string, work_date: string, hours: int|float|string}>  $entries
     * @return ImportResult
     */
    public function import(array $entries, User $author): array
    {
        $drivers = Driver::query()->get()->groupBy(fn (Driver $driver) => $this->nameKey($driver->name));

        /** @var array<string, array{driver: Driver, week_start: string, hours: float, days: array<string, true>}> $weeks */
        $weeks = [];
        /** @var array<string, Rejection> $rejected */
        $rejected = [];

        foreach ($entries as $entry) {
            $weekStart = Period::weekContaining(Carbon::parse($entry['work_date']))->start->toDateString();
            $matches = $drivers->get($this->nameKey($entry['driver_name']), collect());

            if ($matches->count() !== 1) {
                $reason = $matches->isEmpty() ? 'No hay un conductor con ese nombre.' : 'Hay más de un conductor con ese nombre.';
                $rejected[$this->nameKey($entry['driver_name']).'|'.$weekStart] = [
                    'driver_name' => $entry['driver_name'],
                    'week_start' => $weekStart,
                    'reason' => $reason,
                ];

                continue;
            }

            $driver = $matches->first();
            $key = $driver->id.'|'.$weekStart;
            $weeks[$key] ??= ['driver' => $driver, 'week_start' => $weekStart, 'hours' => 0.0, 'days' => []];
            $weeks[$key]['hours'] += (float) $entry['hours'];
            $weeks[$key]['days'][$entry['work_date']] = true;
        }

        $created = $updated = $unchanged = 0;

        DB::transaction(function () use ($weeks, $author, &$rejected, &$created, &$updated, &$unchanged) {
            foreach ($weeks as $week) {
                $hours = round($week['hours'], 2);
                $driver = $week['driver'];

                if ($hours < 0.01 || $hours > self::MAX_WEEK_HOURS) {
                    $rejected[$driver->id.'|'.$week['week_start']] = [
                        'driver_name' => $driver->name,
                        'week_start' => $week['week_start'],
                        'reason' => $hours < 0.01 ? 'La semana no tiene horas.' : 'La semana suma más de '.self::MAX_WEEK_HOURS.' horas.',
                    ];

                    continue;
                }

                $note = self::NOTE_PREFIX.' ('.count($week['days']).' '.(count($week['days']) === 1 ? 'día' : 'días').')';
                $worklog = DriverWorklog::query()
                    ->where('driver_id', $driver->id)
                    ->whereDate('work_date', $week['week_start'])
                    ->first();

                if ($worklog === null) {
                    $rate = (float) $driver->hourly_rate;
                    $worklog = new DriverWorklog([
                        'driver_id' => $driver->id,
                        'work_date' => $week['week_start'],
                        'hours' => $hours,
                        'hourly_rate_snapshot' => $rate,
                        'computed_pay' => round($hours * $rate, 2),
                        'notes' => $note,
                    ]);
                    $worklog->created_by = $author->id;
                    $worklog->save();
                    $created++;

                    continue;
                }

                // Same hours: leave the stored rate snapshot and pay untouched.
                if (abs((float) $worklog->hours - $hours) < 0.005) {
                    $unchanged++;

                    continue;
                }

                $rate = (float) $driver->hourly_rate;
                $attributes = [
                    'hours' => $hours,
                    'hourly_rate_snapshot' => $rate,
                    'computed_pay' => round($hours * $rate, 2),
                ];

                if ($worklog->notes === null || str_starts_with($worklog->notes, self::NOTE_PREFIX)) {
                    $attributes['notes'] = $note;
                }

                $worklog->update($attributes);
                $updated++;
            }
        });

        return [
            'created' => $created,
            'updated' => $updated,
            'unchanged' => $unchanged,
            'rejected' => array_values($rejected),
        ];
    }

    /**
     * Names match ignoring case, accents and extra spaces ("iván" = "Ivan").
     */
    private function nameKey(string $name): string
    {
        return Str::lower(Str::ascii(trim((string) preg_replace('/\s+/', ' ', $name))));
    }
}
