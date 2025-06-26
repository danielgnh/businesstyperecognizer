<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyAnalysis;
use App\Models\ScrapingJob;
use Illuminate\Support\Collection;

final readonly class AnalysisService
{
    public function __construct(
        private CompanyService $companyService,
        private ScrapingOrchestrationService $scrapingService
    ) {}

    /**
     * Start an analysis process for a company
     */
    public function startAnalysis(Company $company, bool $autoAnalyze = true): void
    {
        if (! $autoAnalyze) {
            return;
        }

        // Mark company as processing
        $this->companyService->markAsProcessing($company);

        // Use the scraping orchestration service to manage the analysis workflow
        $this->scrapingService->startCompanyAnalysis($company);

        logger()->info('Analysis started for company', [
            'company_id' => $company->id,
            'company_name' => $company->name,
            'website' => $company->website,
        ]);
    }

    /**
     * Create a scraping job for a company
     */
    public function createScrapingJob(Company $company, string $jobType, int $priority = 0): ScrapingJob
    {
        return ScrapingJob::query()->create([
            'company_id' => $company->id,
            'job_type' => $jobType,
            'priority' => $priority,
            'status' => 'queued',
        ]);
    }

    /**
     * Get analysis summary for a company
     */
    public function getAnalysisSummary(Company $company): array
    {
        $analyses = $company->analyses;
        $scrapingJobs = $company->scrapingJobs;

        return [
            'total_analyses' => $analyses->count(),
            'data_sources' => $analyses->pluck('data_source')->unique()->values()->toArray(),
            'last_analysis' => $analyses->sortByDesc('scraped_at')->first()?->scraped_at,
            'scraping_jobs' => [
                'total' => $scrapingJobs->count(),
                'completed' => $scrapingJobs->where('status', 'completed')->count(),
                'failed' => $scrapingJobs->where('status', 'failed')->count(),
                'processing' => $scrapingJobs->where('status', 'processing')->count(),
                'queued' => $scrapingJobs->where('status', 'queued')->count(),
            ],
            'confidence_breakdown' => $this->getConfidenceBreakdown($analyses),
        ];
    }

    /**
     * Get confidence breakdown from analyses
     */
    private function getConfidenceBreakdown(Collection $analyses): array
    {
        if ($analyses->isEmpty()) {
            return [];
        }

        return $analyses->groupBy('data_source')->map(function ($sourceAnalyses) {
            return [
                'count' => $sourceAnalyses->count(),
                'avg_confidence' => $sourceAnalyses->avg('source_confidence'),
                'max_confidence' => $sourceAnalyses->max('source_confidence'),
                'weight' => $sourceAnalyses->avg('source_weight'),
            ];
        })->toArray();
    }

    /**
     * Create company analysis record
     */
    public function createAnalysis(
        Company $company,
        string $dataSource,
        array $rawData,
        array $processedData,
        array $indicators,
        float $sourceWeight,
        float $sourceConfidence
    ): CompanyAnalysis {
        return CompanyAnalysis::query()->create([
            'company_id' => $company->id,
            'data_source' => $dataSource,
            'raw_data' => $rawData,
            'processed_data' => $processedData,
            'indicators' => $indicators,
            'source_weight' => $sourceWeight,
            'source_confidence' => $sourceConfidence,
        ]);
    }

    /**
     * Update scraping job status
     */
    public function updateScrapingJobStatus(
        ScrapingJob $job,
        string $status,
        ?string $errorMessage = null
    ): void {
        $updates = ['status' => $status];

        if ($status === 'processing') {
            $updates['started_at'] = now();
        }

        if ($status === 'completed' || $status === 'failed') {
            $updates['completed_at'] = now();
        }

        if ($errorMessage) {
            $updates['error_message'] = $errorMessage;
        }

        $job->update($updates);
    }

    /**
     * Get companies ready for analysis
     */
    public function getCompaniesReadyForAnalysis(): Collection
    {
        return Company::query()
            ->where('status', 'pending')
            ->with(['scrapingJobs'])
            ->get()
            ->filter(function (Company $company) {
                // Check if company has no active scraping jobs
                return ! $company->scrapingJobs()
                    ->whereIn('status', ['queued', 'processing'])
                    ->exists();
            });
    }

    /**
     * Check if company analysis is complete
     */
    public function isAnalysisComplete(Company $company): bool
    {
        $requiredSources = ['website', 'social_media', 'google_business'];
        $completedSources = $company->analyses->pluck('data_source')->toArray();

        return empty(array_diff($requiredSources, $completedSources));
    }

    /**
     * Calculate overall confidence score
     */
    public function calculateOverallConfidence(Company $company): float
    {
        $analyses = $company->analyses;

        if ($analyses->isEmpty()) {
            return 0.0;
        }

        $weightedSum = 0.0;
        $totalWeight = 0.0;

        foreach ($analyses as $analysis) {
            $weightedSum += $analysis->source_confidence * $analysis->source_weight;
            $totalWeight += $analysis->source_weight;
        }

        return $totalWeight > 0 ? $weightedSum / $totalWeight : 0.0;
    }

    /**
     * Get analysis indicators by type
     */
    public function getIndicatorsByType(Company $company, string $indicatorType): array
    {
        return $company->analyses
            ->flatMap(function (CompanyAnalysis $analysis) use ($indicatorType) {
                return $analysis->indicators[$indicatorType] ?? [];
            })
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Schedule company for re-analysis
     */
    public function scheduleReanalysis(Company $company): void
    {
        // Clear existing incomplete jobs
        $company->scrapingJobs()
            ->whereIn('status', ['queued', 'processing'])
            ->delete();

        // Reset company status
        $this->companyService->scheduleForReanalysis($company);

        // Start new analysis
        $this->startAnalysis($company, true);
    }

    /**
     * Get analysis performance metrics
     */
    public function getPerformanceMetrics(): array
    {
        return [
            'total_analyses' => \App\Models\CompanyAnalysis::query()->count(),
            'analyses_today' => \App\Models\CompanyAnalysis::query()->whereDate('created_at', today())->count(),
            'avg_processing_time' => $this->getAverageProcessingTime(),
            'success_rate' => $this->getAnalysisSuccessRate(),
            'source_breakdown' => \App\Models\CompanyAnalysis::query()->selectRaw('data_source, COUNT(*) as count')
                ->groupBy('data_source')
                ->pluck('count', 'data_source')
                ->toArray(),
        ];
    }

    /**
     * Get average processing time for completed jobs
     */
    private function getAverageProcessingTime(): ?float
    {
        return ScrapingJob::query()
            ->where('status', 'completed')
            ->whereNotNull('started_at')
            ->whereNotNull('completed_at')
            ->get()
            ->map(function (ScrapingJob $job) {
                return $job->completed_at->diffInSeconds($job->started_at);
            })
            ->avg();
    }

    /**
     * Get analysis success rate
     */
    private function getAnalysisSuccessRate(): float
    {
        $total = \App\Models\ScrapingJob::query()->count();
        if ($total === 0) {
            return 0.0;
        }

        $successful = \App\Models\ScrapingJob::query()->where('status', 'completed')->count();

        return ($successful / $total) * 100;
    }
}
