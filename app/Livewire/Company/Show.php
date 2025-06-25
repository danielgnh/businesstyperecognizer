<?php

declare(strict_types=1);

namespace App\Livewire\Company;

use App\Models\Company;
use App\Services\AnalysisService;
use Illuminate\Database\Eloquent\Collection;
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
            'classificationResults',
        ]);
    }

    public function refreshCompany(): void
    {
        $this->company->refresh();
        $this->company->load([
            'analyses',
            'scrapingJobs',
            'classificationResults',
        ]);
    }

    public function analyzeCompany(AnalysisService $analysisService): void
    {
        if ($this->company->status->value === 'processing') {
            session()->flash('error', 'Company is already being analyzed.');

            return;
        }

        $analysisService->startAnalysis($this->company, true);

        session()->flash('message', 'Analysis started for '.$this->company->name);
        $this->refreshCompany();
    }

    public function scheduleReanalysis(AnalysisService $analysisService): void
    {
        $analysisService->scheduleReanalysis($this->company);

        session()->flash('message', 'Company scheduled for re-analysis.');
        $this->refreshCompany();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    #[Computed]
    public function analysisSummary(): array
    {
        return app(AnalysisService::class)->getAnalysisSummary($this->company);
    }

    #[Computed]
    public function latestAnalysis()
    {
        return $this->company->analyses()
            ->latest('scraped_at')
            ->first();
    }

    #[Computed]
    public function classificationHistory(): Collection
    {
        return $this->company->classificationResults()
            ->latest()
            ->take(10)
            ->get();
    }

    #[Computed]
    public function recentScrapingJobs(): Collection
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
    public function confidenceColor(): string
    {
        return match ($this->confidenceLevel()) {
            'high' => 'green',
            'medium' => 'yellow',
            'low' => 'red',
            default => 'gray'
        };
    }

    #[Computed]
    public function indicators(): array
    {
        return [
            'b2b' => app(AnalysisService::class)->getIndicatorsByType($this->company, 'b2b_indicators'),
            'b2c' => app(AnalysisService::class)->getIndicatorsByType($this->company, 'b2c_indicators'),
            'technical' => app(AnalysisService::class)->getIndicatorsByType($this->company, 'technical_indicators'),
            'content' => app(AnalysisService::class)->getIndicatorsByType($this->company, 'content_indicators'),
        ];
    }

    #[Computed]
    public function isAnalysisComplete(): bool
    {
        return app(AnalysisService::class)->isAnalysisComplete($this->company);
    }

    #[Computed]
    public function overallConfidence(): float
    {
        return app(AnalysisService::class)->calculateOverallConfidence($this->company);
    }

    public function render(): View
    {
        return view('livewire.company.show');
    }
}
