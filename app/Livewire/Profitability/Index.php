<?php

namespace App\Livewire\Profitability;

use App\Services\Financial\Period;
use App\Services\Financial\ProfitabilityReport;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Profitability by route and by customer (docs/finance/financial-model.md
 * §J.8) for a week (Thu→Wed, ADR 0003), a calendar month or a calendar year.
 * Read-only; every authenticated role may view it, like the dashboard.
 *
 * @phpstan-import-type ProfitabilityBreakdown from ProfitabilityReport
 *
 * @property-read Period $period
 * @property-read ProfitabilityBreakdown $report
 */
#[Title('Rentabilidad')]
class Index extends Component
{
    private const MODES = ['week', 'month', 'year'];

    private const MONTHS = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

    public string $mode = 'week';

    public string $anchor = '';

    public function mount(): void
    {
        $this->anchor = now()->toDateString();
    }

    public function setMode(string $mode): void
    {
        if (in_array($mode, self::MODES, true)) {
            $this->mode = $mode;
        }
    }

    public function previous(): void
    {
        $this->shift(-1);
    }

    public function next(): void
    {
        $this->shift(1);
    }

    public function today(): void
    {
        $this->anchor = now()->toDateString();
    }

    #[Computed]
    public function period(): Period
    {
        $anchor = Carbon::parse($this->anchor);

        return match ($this->mode) {
            'month' => Period::monthContaining($anchor),
            'year' => Period::yearContaining($anchor),
            default => Period::weekContaining($anchor),
        };
    }

    /**
     * @return ProfitabilityBreakdown
     */
    #[Computed]
    public function report(): array
    {
        return app(ProfitabilityReport::class)->breakdown($this->period);
    }

    #[Computed]
    public function periodLabel(): string
    {
        $period = $this->period;

        return match ($this->mode) {
            'month' => ucfirst(self::MONTHS[$period->start->month - 1]).' '.$period->start->year,
            'year' => (string) $period->start->year,
            default => $period->start->format('d/m/Y').' — '.$period->end->format('d/m/Y'),
        };
    }

    private function shift(int $direction): void
    {
        $anchor = Carbon::parse($this->anchor);

        $this->anchor = match ($this->mode) {
            'month' => $anchor->startOfMonth()->addMonths($direction)->toDateString(),
            'year' => $anchor->startOfYear()->addYears($direction)->toDateString(),
            default => $anchor->addWeeks($direction)->toDateString(),
        };
    }
}
