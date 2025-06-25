<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\DashboardService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Dashboard')]
class Dashboard extends Component
{
    public string $search = '';

    #[Computed]
    public function dashboardStats(): array
    {
        return app(DashboardService::class)->getDashboardStats();
    }

    #[Computed]
    public function totalCompanies(): int
    {
        return $this->dashboardStats()['companies']['total'];
    }

    #[Computed]
    public function classifiedCount(): int
    {
        return $this->dashboardStats()['companies']['classified'];
    }

    #[Computed]
    public function processingCount(): int
    {
        return $this->dashboardStats()['companies']['processing'];
    }

    #[Computed]
    public function pendingCount(): int
    {
        return $this->dashboardStats()['companies']['pending'];
    }

    #[Computed]
    public function accuracyRate(): float
    {
        return $this->dashboardStats()['accuracy_rate'];
    }

    #[Computed]
    public function recentCompanies(): Collection
    {
        return app(DashboardService::class)->getRecentCompanies();
    }

    #[Computed]
    public function classificationBreakdown(): array
    {
        return $this->dashboardStats()['classification_breakdown'];
    }

    #[Computed]
    public function performanceMetrics(): array
    {
        return app(DashboardService::class)->getPerformanceMetrics();
    }

    #[Computed]
    public function companiesNeedingAttention(): Collection
    {
        return app(DashboardService::class)->getCompaniesNeedingAttention();
    }

    public function render(): View
    {
        return view('livewire.dashboard');
    }
}
