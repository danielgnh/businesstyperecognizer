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
        return data_get($this->dashboardStats(), 'total_companies', 0);
    }

    #[Computed]
    public function classifiedCount(): int
    {
        return data_get($this->dashboardStats(), 'classified_count', 0);
    }

    #[Computed]
    public function processingCount(): int
    {
        return data_get($this->dashboardStats(), 'processing_count', 0);
    }

    #[Computed]
    public function pendingCount(): int
    {
        return data_get($this->dashboardStats(), 'pending_count', 0);
    }

    #[Computed]
    public function accuracyRate(): float
    {
        return data_get($this->dashboardStats(), 'accuracy_rate', 0);
    }

    #[Computed]
    public function recentCompanies(): Collection
    {
        return app(DashboardService::class)->getRecentCompanies();
    }

    #[Computed]
    public function classificationBreakdown(): array
    {
        // Remove non-integer values from the classification breakdown (We have percentages included in the service)
        return array_filter($this->dashboardStats()['classification_breakdown'], fn ($value) => is_int($value));
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
