<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\Scrape\SocialMediaScraperJob;
use App\Jobs\Scrape\WebsiteContentScraperJob;
use App\Models\Company;

final readonly class ScrapingOrchestrationService
{
    public function __construct(
        private WebsiteContentCacheService $cacheService
    ) {}

    /**
     * Start the complete scraping process for a company
     */
    public function startCompanyAnalysis(Company $company): void
    {
        if (! $company->website) {
            logger()->warning('Cannot start analysis for company without website', [
                'company_id' => $company->id,
            ]);

            return;
        }

        // Check if website content is already cached
        if ($this->cacheService->isContentCached($company->website)) {
            // Content is cached, directly start dependent jobs
            $this->dispatchDependentJobs($company);
        } else {
            // Content not cached, start with content scraping
            WebsiteContentScraperJob::dispatch($company);

            // Dispatch dependent jobs with delay to allow content scraping to complete
            $this->dispatchDependentJobs($company, 3); // 3 minute delay
        }

        logger()->info('Company analysis started', [
            'company_id' => $company->id,
            'website' => $company->website,
            'content_cached' => $this->cacheService->isContentCached($company->website),
        ]);
    }

    /**
     * Dispatch jobs that depend on website content being cached
     */
    private function dispatchDependentJobs(Company $company, int $delayMinutes = 0): void
    {
        $delay = $delayMinutes > 0 ? now()->addMinutes($delayMinutes) : null;

        // Dispatch social media scraping job
        $job = SocialMediaScraperJob::dispatch($company);
        if ($delay) {
            $job->delay($delay);
        }

        // Future: Add more scraping jobs here
        // WebsiteContentAnalysisJob::dispatch($company)
        //     ->when($delay, fn($job) => $job->delay($delay));

        // SEOAnalysisJob::dispatch($company)
        //     ->when($delay, fn($job) => $job->delay($delay));
    }

    /**
     * Check if website content is available for a company
     */
    public function isContentAvailable(Company $company): bool
    {
        if (! $company->website) {
            return false;
        }

        return $this->cacheService->isContentCached($company->website);
    }

    /**
     * Get cached website content for a company
     */
    public function getWebsiteContent(Company $company): ?string
    {
        if (! $company->website) {
            return null;
        }

        return $this->cacheService->getCachedContent($company->website);
    }

    /**
     * Force refresh website content for a company
     */
    public function refreshWebsiteContent(Company $company): void
    {
        if (! $company->website) {
            return;
        }

        // Clear existing cache
        $this->cacheService->clearCache($company->website);

        // Dispatch fresh content scraping
        WebsiteContentScraperJob::dispatch($company);

        logger()->info('Website content refresh initiated', [
            'company_id' => $company->id,
            'website' => $company->website,
        ]);
    }

    /**
     * Get cache statistics for monitoring
     */
    public function getCacheStatistics(): array
    {
        // For now, return basic stats without Redis-specific operations
        // In production, you'd implement this with proper Redis commands
        $cacheEntries = [];
        $totalSize = 0;

        // This is a simplified implementation
        // In a real scenario, you'd scan Redis keys properly
        return [
            'total_cached_websites' => count($cacheEntries),
            'total_cache_size_bytes' => $totalSize,
            'total_cache_size_mb' => round($totalSize / 1024 / 1024, 2),
            'cache_entries' => $cacheEntries,
        ];
    }

    /**
     * Clean up old cache entries
     */
    public function cleanupOldCache(): int
    {
        $cleaned = 0;

        // For now, return 0 as we'd need proper Redis scanning
        // In production, implement proper cache key scanning and cleanup

        // Note: In production, $cleaned would be set by actual cleanup logic

        return $cleaned;
    }

    /**
     * Batch start analysis for multiple companies
     */
    public function batchStartAnalysis(array $companyIds): array
    {
        $results = [
            'started' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        foreach ($companyIds as $companyId) {
            try {
                $company = Company::find($companyId);

                if (! $company) {
                    $results['errors'][] = "Company {$companyId} not found";

                    continue;
                }

                if (! $company->website) {
                    $results['skipped']++;

                    continue;
                }

                $this->startCompanyAnalysis($company);
                $results['started']++;

            } catch (\Exception $e) {
                $results['errors'][] = "Company {$companyId}: ".$e->getMessage();
            }
        }

        return $results;
    }
}
