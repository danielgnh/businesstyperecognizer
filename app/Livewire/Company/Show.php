<?php

declare(strict_types=1);

namespace App\Livewire\Company;

use App\Enums\CompanyStatus;
use App\Models\Company;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Company Details')]
class Show extends Component
{
    public Company $company;

    public string $activeTab = 'overview';

    public function mount(Company $company): void
    {
        $this->company = $company->load([
            'analyses',
            'scrapingJobs',
        ]);
    }

    public function refreshCompany(): void
    {
        $this->company->refresh();
        $this->company->load([
            'analyses',
            'scrapingJobs',
        ]);
    }

    public function analyzeCompany(): void
    {
        if ($this->company->status === CompanyStatus::PROCESSING) {
            session()->flash('error', 'Company is already being analyzed.');
            return;
        }

        // Update status to pending
        $this->company->update(['status' => CompanyStatus::PENDING]);

        // TODO: Dispatch analysis job
        // AnalyzeCompanyJob::dispatch($this->company);

        session()->flash('message', 'Analysis started for ' . $this->company->name);
        $this->refreshCompany();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    #[Computed]
    public function latestAnalysis()
    {
        return $this->company->analyses()
            ->latest()
            ->first();
    }

    #[Computed]
    public function classificationHistory()
    {
        return $this->company->classificationResults()
            ->latest()
            ->take(10)
            ->get();
    }

    #[Computed]
    public function recentScrapingJobs()
    {
        return $this->company->scrapingJobs()
            ->latest()
            ->take(5)
            ->get();
    }

    #[Computed]
    public function analysisBreakdown(): array
    {
        $analyses = $this->company->analyses;

        return [
            'website' => $analyses->where('data_source', 'website')->first(),
            'social_media' => $analyses->where('data_source', 'social_media')->first(),
            'google_business' => $analyses->where('data_source', 'google_business')->first(),
        ];
    }

    #[Computed]
    public function confidenceLevel(): string
    {
        if (! $this->company->confidence_score) {
            return 'unknown';
        }

        return match (true) {
            $this->company->confidence_score >= 0.8 => 'high',
            $this->company->confidence_score >= 0.6 => 'medium',
            default => 'low'
        };
    }

    #[Computed]
    public function indicators(): array
    {
        $allIndicators = [];

        foreach ($this->company->analyses as $analysis) {
            if (isset($analysis->indicators)) {
                $allIndicators = array_merge($allIndicators, $analysis->indicators);
            }
        }

        return $allIndicators;
    }

    public function render(): View
    {
        return view('livewire.company.show');
    }
}
