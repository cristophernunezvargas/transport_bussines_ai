<?php

namespace App\Livewire\Dashboard;

use App\Models\MaintenanceSchedule;
use App\Services\Financial\FinancialCalculator;
use App\Services\Financial\Period;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * The KPI dashboard from docs/finance/financial-model.md, shown for three
 * independent periods side by side — weekly (Thu→Wed, ADR 0003), calendar
 * month and calendar year, each with its own navigator: fleet
 * revenue/cost/profit/margin, cost & revenue per km, availability &
 * utilization, and preventive-maintenance alerts. Every per-km figure is an
 * estimate while trip distance still defaults from route.standard_km
 * (docs/decisions/0002-trip-distance-source.md) — labelled as such, never
 * presented as exact.
 *
 * Read-only: every authenticated role may view it, matching ModelPolicy's
 * "everyone reads" rule for the rest of the app's data.
 *
 * @phpstan-import-type FleetRow from FinancialCalculator
 *
 * @property-read list<FleetRow> $fleetRows Livewire's #[Computed] magic property — see fleetRows() below.
 * @property-read list<FleetRow> $monthRows
 * @property-read list<FleetRow> $yearRows
 */
#[Title('Resumen')]
class Index extends Component
{
    /** Anchor of the weekly summary. */
    public string $anchorDate = '';

    public string $monthAnchor = '';

    public string $yearAnchor = '';

    public function mount(): void
    {
        $this->anchorDate = now()->toDateString();
        $this->monthAnchor = now()->toDateString();
        $this->yearAnchor = now()->toDateString();
    }

    public function previousMonth(): void
    {
        $this->monthAnchor = Carbon::parse($this->monthAnchor)->startOfMonth()->subMonth()->toDateString();
    }

    public function nextMonth(): void
    {
        $this->monthAnchor = Carbon::parse($this->monthAnchor)->startOfMonth()->addMonth()->toDateString();
    }

    public function thisMonth(): void
    {
        $this->monthAnchor = now()->toDateString();
    }

    public function previousYear(): void
    {
        $this->yearAnchor = Carbon::parse($this->yearAnchor)->startOfYear()->subYear()->toDateString();
    }

    public function nextYear(): void
    {
        $this->yearAnchor = Carbon::parse($this->yearAnchor)->startOfYear()->addYear()->toDateString();
    }

    public function thisYear(): void
    {
        $this->yearAnchor = now()->toDateString();
    }

    public function previousWeek(): void
    {
        $this->anchorDate = Carbon::parse($this->anchorDate)->subWeek()->toDateString();
    }

    public function nextWeek(): void
    {
        $this->anchorDate = Carbon::parse($this->anchorDate)->addWeek()->toDateString();
    }

    public function thisWeek(): void
    {
        $this->anchorDate = now()->toDateString();
    }

    /**
     * @return list<FleetRow>
     */
    #[Computed]
    public function fleetRows(): array
    {
        return app(FinancialCalculator::class)->fleetSummary($this->period());
    }

    /**
     * @return array{
     *     revenue: float, total_cost: float, profit_net: float, km: float,
     *     margin_pct: ?float, cost_per_km: ?float, revenue_per_km: ?float,
     * }
     */
    #[Computed]
    public function fleetTotals(): array
    {
        return $this->totalsOf($this->fleetRows);
    }

    /**
     * @return list<FleetRow>
     */
    #[Computed]
    public function monthRows(): array
    {
        return app(FinancialCalculator::class)->fleetSummary($this->monthPeriod());
    }

    /**
     * @return array{
     *     revenue: float, total_cost: float, profit_net: float, km: float,
     *     margin_pct: ?float, cost_per_km: ?float, revenue_per_km: ?float,
     * }
     */
    #[Computed]
    public function monthTotals(): array
    {
        return $this->totalsOf($this->monthRows);
    }

    /**
     * @return list<FleetRow>
     */
    #[Computed]
    public function yearRows(): array
    {
        return app(FinancialCalculator::class)->fleetSummary($this->yearPeriod());
    }

    /**
     * @return array{
     *     revenue: float, total_cost: float, profit_net: float, km: float,
     *     margin_pct: ?float, cost_per_km: ?float, revenue_per_km: ?float,
     * }
     */
    #[Computed]
    public function yearTotals(): array
    {
        return $this->totalsOf($this->yearRows);
    }

    /**
     * Count of active preventive schedules currently VENCIDO/PROXIMO
     * (docs/business/discovery.md §I "Maintenance status").
     *
     * @return array{overdue: int, upcoming: int}
     */
    #[Computed]
    public function maintenanceAlerts(): array
    {
        return MaintenanceSchedule::alertCounts();
    }

    public function period(): Period
    {
        return Period::weekContaining(Carbon::parse($this->anchorDate));
    }

    public function monthPeriod(): Period
    {
        return Period::monthContaining(Carbon::parse($this->monthAnchor));
    }

    public function yearPeriod(): Period
    {
        return Period::yearContaining(Carbon::parse($this->yearAnchor));
    }

    /**
     * @param  list<FleetRow>  $rows
     * @return array{
     *     revenue: float, total_cost: float, profit_net: float, km: float,
     *     margin_pct: ?float, cost_per_km: ?float, revenue_per_km: ?float,
     * }
     */
    private function totalsOf(array $rows): array
    {
        return app(FinancialCalculator::class)->fleetTotals($rows);
    }
}
