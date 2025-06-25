<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CompanyClassification;
use App\Enums\CompanyStatus;
use App\Models\Company;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class CompanyListService
{
    public function __construct(
        private CompanyService $companyService,
        private AnalysisService $analysisService
    ) {}

    /**
     * Get paginated companies with filters and search
     */
    public function getCompanies(
        string $search = '',
        string $statusFilter = '',
        string $classificationFilter = '',
        string $sortBy = 'created_at',
        string $sortDirection = 'desc',
        int $perPage = 15
    ): LengthAwarePaginator {
        return Company::query()
            ->when($search, function (Builder $query) use ($search) {
                $query->where(function (Builder $q) use ($search) {
                    $q->where('name', 'like', '%'.$search.'%')
                        ->orWhere('website', 'like', '%'.$search.'%')
                        ->orWhere('domain', 'like', '%'.$search.'%');
                });
            })
            ->when($statusFilter, function (Builder $query) use ($statusFilter) {
                $query->where('status', CompanyStatus::from($statusFilter));
            })
            ->when($classificationFilter, function (Builder $query) use ($classificationFilter) {
                if ($classificationFilter === 'unclassified') {
                    $query->whereNull('classification');
                } else {
                    $query->where('classification', CompanyClassification::from($classificationFilter));
                }
            })
            ->orderBy($sortBy, $sortDirection)
            ->paginate($perPage);
    }

    /**
     * Get all companies matching filters (for bulk operations)
     */
    public function getFilteredCompanies(
        string $search = '',
        string $statusFilter = '',
        string $classificationFilter = ''
    ): Collection {
        return Company::query()
            ->when($search, function (Builder $query) use ($search) {
                $query->where(function (Builder $q) use ($search) {
                    $q->where('name', 'like', '%'.$search.'%')
                        ->orWhere('website', 'like', '%'.$search.'%')
                        ->orWhere('domain', 'like', '%'.$search.'%');
                });
            })
            ->when($statusFilter, function (Builder $query) use ($statusFilter) {
                $query->where('status', CompanyStatus::from($statusFilter));
            })
            ->when($classificationFilter, function (Builder $query) use ($classificationFilter) {
                if ($classificationFilter === 'unclassified') {
                    $query->whereNull('classification');
                } else {
                    $query->where('classification', CompanyClassification::from($classificationFilter));
                }
            })
            ->get();
    }

    /**
     * Analyze selected companies
     */
    public function analyzeSelectedCompanies(array $companyIds): int
    {
        if (empty($companyIds)) {
            return 0;
        }

        $companies = Company::query()->whereIn('id', $companyIds)->get();
        $processedCount = 0;

        foreach ($companies as $company) {
            $this->analysisService->startAnalysis($company, true);
            $processedCount++;
        }

        return $processedCount;
    }

    /**
     * Delete selected companies
     */
    public function deleteSelectedCompanies(array $companyIds): int
    {
        if (empty($companyIds)) {
            return 0;
        }

        return Company::query()->whereIn('id', $companyIds)->delete();
    }

    /**
     * Bulk update classification for selected companies
     */
    public function bulkUpdateClassification(
        array $companyIds,
        string $classification,
        float $confidenceScore = 1.0
    ): int {
        if (empty($companyIds)) {
            return 0;
        }

        return $this->companyService->bulkUpdateClassification(
            $companyIds,
            $classification,
            $confidenceScore
        );
    }

    /**
     * Bulk update status for selected companies
     */
    public function bulkUpdateStatus(array $companyIds, string $status): int
    {
        if (empty($companyIds)) {
            return 0;
        }

        return Company::query()
            ->whereIn('id', $companyIds)
            ->update(['status' => $status]);
    }

    /**
     * Get status filter options
     */
    public function getStatusOptions(): array
    {
        return (new \Illuminate\Support\Collection(CompanyStatus::cases()))
            ->mapWithKeys(fn (CompanyStatus $status) => [
                $status->value => $status->label(),
            ])
            ->toArray();
    }

    /**
     * Get classification filter options
     */
    public function getClassificationOptions(): array
    {
        $options = (new \Illuminate\Support\Collection(CompanyClassification::cases()))
            ->mapWithKeys(fn (CompanyClassification $classification) => [
                $classification->value => $classification->label(),
            ])
            ->toArray();

        $options['unclassified'] = 'Unclassified';

        return $options;
    }

    /**
     * Get sortable columns
     */
    public function getSortableColumns(): array
    {
        return [
            'name' => 'Company Name',
            'website' => 'Website',
            'domain' => 'Domain',
            'status' => 'Status',
            'classification' => 'Classification',
            'confidence_score' => 'Confidence Score',
            'created_at' => 'Created Date',
            'last_analyzed_at' => 'Last Analyzed',
        ];
    }

    /**
     * Export companies to CSV format
     */
    public function exportToArray(
        string $search = '',
        string $statusFilter = '',
        string $classificationFilter = ''
    ): array {
        $companies = $this->getFilteredCompanies($search, $statusFilter, $classificationFilter);

        /** @var \Illuminate\Database\Eloquent\Collection<int, Company> $companies */
        return $companies->map(function (Company $company) {
            return [
                'id' => $company->id,
                'name' => $company->name,
                'website' => $company->website,
                'domain' => $company->domain,
                'status' => $company->status->label(),
                'classification' => $company->classification?->label(),
                'confidence_score' => $company->confidence_score,
                'created_at' => $company->created_at->format('Y-m-d H:i:s'),
                'last_analyzed_at' => $company->last_analyzed_at?->format('Y-m-d H:i:s'),
            ];
        })->toArray();
    }

    /**
     * Get companies summary for current filters
     */
    public function getFilterSummary(
        string $search = '',
        string $statusFilter = '',
        string $classificationFilter = ''
    ): array {
        $companies = $this->getFilteredCompanies($search, $statusFilter, $classificationFilter);

        return [
            'total' => $companies->count(),
            'by_status' => $companies->groupBy('status')->map->count()->toArray(),
            'by_classification' => $companies->groupBy('classification')->map->count()->toArray(),
            'avg_confidence' => $companies->whereNotNull('confidence_score')->avg('confidence_score'),
            'needs_analysis' => $companies->where('status', CompanyStatus::PENDING)->count(),
        ];
    }

    /**
     * Get duplicate companies based on domain
     */
    public function getDuplicateCompanies(): Collection
    {
        $duplicateDomains = Company::query()
            ->selectRaw('domain, COUNT(*) as count')
            ->whereNotNull('domain')
            ->groupBy('domain')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('domain');

        return Company::query()
            ->whereIn('domain', $duplicateDomains)
            ->orderBy('domain')
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Merge duplicate companies
     */
    public function mergeDuplicateCompanies(Company $keepCompany, array $duplicateIds): void
    {
        $duplicates = Company::query()->whereIn('id', $duplicateIds)->get();

        foreach ($duplicates as $duplicate) {
            // Transfer relationships to the company we're keeping
            $duplicate->analyses()->update(['company_id' => $keepCompany->id]);
            $duplicate->classificationResults()->update(['company_id' => $keepCompany->id]);
            $duplicate->scrapingJobs()->update(['company_id' => $keepCompany->id]);

            // Delete the duplicate
            $duplicate->delete();
        }

        // Update the kept company with best available data
        $this->consolidateCompanyData($keepCompany);
    }

    /**
     * Consolidate company data after merging
     */
    private function consolidateCompanyData(Company $company): void
    {
        // If company doesn't have classification but has analyses, try to determine classification
        if (! $company->classification && $company->analyses()->exists()) {
            $confidence = $this->analysisService->calculateOverallConfidence($company);

            if ($confidence > 0.7) {
                // Logic to determine classification from analyses would go here
                // For now, we'll leave it as is
            }
        }

        // Update last analyzed date to the most recent analysis
        /** @var \App\Models\CompanyAnalysis|null $lastAnalysis */
        $lastAnalysis = $company->analyses()->latest('scraped_at')->first();
        if ($lastAnalysis) {
            $company->update(['last_analyzed_at' => $lastAnalysis->scraped_at]);
        }
    }

    /**
     * Get companies that haven't been analyzed recently
     */
    public function getStaleCompanies(int $daysOld = 30): Collection
    {
        return Company::query()
            ->where(function (Builder $query) use ($daysOld) {
                $query->whereNull('last_analyzed_at')
                    ->orWhere('last_analyzed_at', '<', now()->subDays($daysOld));
            })
            ->orderBy('last_analyzed_at', 'asc')
            ->get();
    }
}
