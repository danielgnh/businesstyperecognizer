<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\AnalyzeCompanyWithAIAction;
use App\Models\Company;
use Illuminate\Database\Eloquent\Collection;

final readonly class CompanyAIAnalysisService
{
    public function __construct(
        private AnalyzeCompanyWithAIAction $analyzeAction
    ) {}

    /**
     * Analyze a single company with AI
     */
    public function analyzeCompany(Company $company): bool
    {
        try {
            return $this->analyzeAction->execute($company);
        } catch (\Exception $e) {
            logger()->error('Failed to analyze company with AI', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Get companies that need AI analysis
     */
    public function getCompaniesNeedingAnalysis(int $limit = 50): Collection
    {
        return Company::query()
            ->notAiAnalyzed()
            ->whereNotNull('website')
            ->where('status', '!=', 'failed')
            ->limit($limit)
            ->get();
    }

    /**
     * Get companies that need AI re-analysis
     */
    public function getCompaniesNeedingReanalysis(int $days = 90, int $limit = 50): Collection
    {
        return Company::query()
            ->aiAnalyzed()
            ->whereNotNull('website')
            ->where('ai_analyzed_at', '<', now()->subDays($days))
            ->limit($limit)
            ->get();
    }

    /**
     * Get AI analysis statistics
     */
    public function getAnalysisStatistics(): array
    {
        $total = Company::query()->whereNotNull('website')->count();
        $analyzed = Company::query()->aiAnalyzed()->count();
        $withBranch = Company::query()->whereNotNull('branch')->count();
        $withKeywords = Company::query()->whereNotNull('keywords')->count();

        return [
            'total_companies' => $total,
            'ai_analyzed' => $analyzed,
            'analysis_percentage' => $total > 0 ? round(($analyzed / $total) * 100, 1) : 0,
            'with_branch' => $withBranch,
            'with_keywords' => $withKeywords,
            'pending_analysis' => $total - $analyzed,
        ];
    }

    /**
     * Get branch distribution
     */
    public function getBranchDistribution(): array
    {
        return Company::query()
            ->whereNotNull('branch')
            ->selectRaw('branch, COUNT(*) as count')
            ->groupBy('branch')
            ->orderByDesc('count')
            ->pluck('count', 'branch')
            ->toArray();
    }

    /**
     * Get most common keywords
     */
    public function getMostCommonKeywords(int $limit = 20): array
    {
        $companies = Company::query()
            ->whereNotNull('keywords')
            ->select('keywords')
            ->get();

        $keywordCounts = [];
        
        foreach ($companies as $company) {
            if (is_array($company->keywords)) {
                foreach ($company->keywords as $keyword) {
                    $keyword = strtolower(trim($keyword));
                    $keywordCounts[$keyword] = ($keywordCounts[$keyword] ?? 0) + 1;
                }
            }
        }

        arsort($keywordCounts);
        
        return array_slice($keywordCounts, 0, $limit, true);
    }
} 