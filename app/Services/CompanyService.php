<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Company;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Log\Logger;

final readonly class CompanyService
{
    public function __construct(
        private WebsiteParsingService $websiteParsingService,
        private Logger $logger
    ) {}

    /**
     * Create a new company with the provided data
     */
    public function createCompany(array $data): Company
    {
        $website = $data['website'];
        $domain = $this->websiteParsingService->extractDomain($website);

        // Auto-generate company name if not provided
        if (empty($data['name'])) {
            $data['name'] = $this->websiteParsingService->extractCompanyNameFromWebsite($website);
        }

        return Company::query()->create([
            'name' => $data['name'],
            'website' => $website,
            'domain' => $domain,
        ]);
    }

    /**
     * Update an existing company
     */
    public function updateCompany(Company $company, array $data): Company
    {
        if (isset($data['website'])) {
            $data['domain'] = $this->websiteParsingService->extractDomain($data['website']);
        }

        $company->update($data);

        return $company->fresh();
    }

    /**
     * Get companies by classification
     */
    public function getCompaniesByClassification(string $classification): Collection
    {
        return Company::query()
            ->where('classification', $classification)
            ->with(['analyses', 'latestClassificationResult'])
            ->get();
    }

    /**
     * Get companies that need analysis
     */
    public function getCompaniesNeedingAnalysis(): Collection
    {
        return Company::query()
            ->where('status', 'pending')
            ->orWhere(function ($query) {
                $query->where('last_analyzed_at', '<', now()->subDays(30))
                    ->orWhereNull('last_analyzed_at');
            })
            ->get();
    }

    /**
     * Mark company as processing
     */
    public function markAsProcessing(Company $company): void
    {
        $company->update([
            'status' => 'processing',
        ]);
    }

    /**
     * Mark company analysis as completed
     */
    public function markAsCompleted(Company $company, string $classification, float $confidenceScore): void
    {
        $company->update([
            'status' => 'completed',
            'classification' => $classification,
            'confidence_score' => $confidenceScore,
            'last_analyzed_at' => now(),
        ]);
    }

    /**
     * Mark company analysis as failed
     */
    public function markAsFailed(Company $company, ?string $errorMessage = null): void
    {
        $company->update([
            'status' => 'failed',
        ]);

        if ($errorMessage) {
            // TODO: Log error or store in separate error tracking table
            $this->logger->error('Company analysis failed', [
                'company_id' => $company->id,
                'error' => $errorMessage,
            ]);
        }
    }

    /**
     * Check if company already exists by website
     */
    public function existsByWebsite(string $website): bool
    {
        $domain = $this->websiteParsingService->extractDomain($website);

        return Company::query()
            ->where('website', $website)
            ->orWhere('domain', $domain)
            ->exists();
    }

    /**
     * Get company statistics
     */
    public function getStatistics(): array
    {
        return [
            'total' => \App\Models\Company::query()->count(),
            'classified' => \App\Models\Company::query()->whereNotNull('classification')->count(),
            'processing' => \App\Models\Company::query()->where('status', 'processing')->count(),
            'failed' => \App\Models\Company::query()->where('status', 'failed')->count(),
            'pending' => \App\Models\Company::query()->where('status', 'pending')->count(),
            'b2b_count' => \App\Models\Company::query()->where('classification', 'b2b')->count(),
            'b2c_count' => \App\Models\Company::query()->where('classification', 'b2c')->count(),
            'hybrid_count' => \App\Models\Company::query()->where('classification', 'hybrid')->count(),
            'average_confidence' => \App\Models\Company::query()->whereNotNull('confidence_score')->avg('confidence_score'),
        ];
    }

    /**
     * Bulk update companies
     */
    public function bulkUpdateClassification(array $companyIds, string $classification, float $confidenceScore): int
    {
        return Company::query()
            ->whereIn('id', $companyIds)
            ->update([
                'classification' => $classification,
                'confidence_score' => $confidenceScore,
                'status' => 'completed',
                'last_analyzed_at' => now(),
            ]);
    }

    /**
     * Delete a company and all related data
     */
    public function deleteCompany(Company $company): void
    {
        // Relationships will be cascade deleted due to foreign key constraints
        $company->delete();
    }

    /**
     * Schedule company for re-analysis
     */
    public function scheduleForReanalysis(Company $company): void
    {
        $company->update([
            'status' => 'pending',
            'classification' => null,
            'confidence_score' => null,
        ]);

        // TODO: Dispatch analysis job
        // AnalyzeCompanyJob::dispatch($company);
    }
}
