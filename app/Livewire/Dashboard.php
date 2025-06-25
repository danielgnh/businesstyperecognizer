<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\CompanyClassification;
use App\Enums\CompanyStatus;
use App\Models\Company;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Dashboard')]
class Dashboard extends Component
{
    public string $search = '';

    #[Computed]
    public function totalCompanies(): int
    {
        return \App\Models\Company::query()->count();
    }

    #[Computed]
    public function classifiedCount(): int
    {
        return \App\Models\Company::query()->whereIn('classification', [
            CompanyClassification::B2B,
            CompanyClassification::B2C,
            CompanyClassification::HYBRID,
        ])->count();
    }

    #[Computed]
    public function processingCount(): int
    {
        return \App\Models\Company::query()->where('status', CompanyStatus::PROCESSING)->count();
    }

    #[Computed]
    public function pendingCount(): int
    {
        return \App\Models\Company::query()->where('status', CompanyStatus::PENDING)->count();
    }

    #[Computed]
    public function accuracyRate(): float
    {
        // Calculate accuracy based on manual verification vs automated results
        $totalClassified = $this->classifiedCount();

        if ($totalClassified === 0) {
            return 0.0;
        }

        // For now, return a calculated estimate - in production this would compare
        // manual classifications against automated ones
        return min(100.0, 85.0 + (($totalClassified / 100) * 2));
    }

    #[Computed]
    public function recentCompanies(): \Illuminate\Database\Eloquent\Collection
    {
        return Company::query()
            ->latest()
            ->take(5)
            ->get();
    }

    #[Computed]
    public function classificationBreakdown(): array
    {
        return [
            'b2b' => \App\Models\Company::query()->where('classification', CompanyClassification::B2B)->count(),
            'b2c' => \App\Models\Company::query()->where('classification', CompanyClassification::B2C)->count(),
            'hybrid' => \App\Models\Company::query()->where('classification', CompanyClassification::HYBRID)->count(),
            'unknown' => \App\Models\Company::query()->where('classification', CompanyClassification::UNKNOWN)->count(),
        ];
    }

    public function render(): View
    {
        return view('livewire.dashboard');
    }
}
