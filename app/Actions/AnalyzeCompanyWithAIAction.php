<?php

declare(strict_types=1);

namespace App\Actions;

use App\AI\Services\CompanyAnalysisAIService;
use App\Exceptions\CompanyAnalysisException;
use App\Models\Company;
use App\Services\WebsiteContentCacheService;

final readonly class AnalyzeCompanyWithAIAction
{
    public function __construct(
        private CompanyAnalysisAIService $aiService,
        private WebsiteContentCacheService $cacheService
    ) {}

    /**
     * Execute company AI analysis
     * @throws CompanyAnalysisException
     */
    public function execute(Company $company): bool
    {
        try {
            // Get website content from cache
            $websiteContent = $this->cacheService->getCachedContent($company->website);

            throw_if(
                !$websiteContent,
                 new CompanyAnalysisException(
                    'Website content not available for AI analysis',
                    0,
                    $company
                )
            );

            // Analyze with AI
            $analysis = $this->aiService->analyzeCompany($company, $websiteContent);

            // Update company with AI analysis results
            $company->update([
                'summary' => $analysis->summary,
                'branch' => $analysis->branch,
                'scope' => $analysis->scope,
                'keywords' => $analysis->keywords,
                'ai_analyzed_at' => now(),
            ]);

            logger()->info('Company AI analysis completed successfully', [
                'company_id' => $company->id,
                'branch' => $analysis->branch,
                'scope' => $analysis->scope,
                'confidence' => $analysis->confidence,
                'keywords_count' => count($analysis->keywords),
            ]);

            return true;

        } catch (CompanyAnalysisException $e) {
            logger()->error('Company AI analysis failed', $e->getContext());
            throw $e;
        } catch (\Exception $e) {
            $analysisException = new CompanyAnalysisException(
                "Unexpected error during company AI analysis: {$e->getMessage()}",
                0,
                $company,
                $e
            );

            logger()->error('Company AI analysis failed', $analysisException->getContext());
            throw $analysisException;
        }
    }
}
