<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\Scrape\CompanyAIAnalysisJob;
use App\Models\Company;
use App\Services\CompanyAIAnalysisService;
use Illuminate\Console\Command;

class AnalyzeCompaniesWithAI extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'companies:analyze-ai 
                            {--company= : Specific company ID to analyze}
                            {--limit=10 : Number of companies to analyze}
                            {--queue : Dispatch jobs to queue instead of running directly}
                            {--force : Force re-analysis even if already analyzed}';

    /**
     * The console command description.
     */
    protected $description = 'Analyze companies with AI to extract branch, scope, and keywords';

    /**
     * Execute the console command.
     */
    public function handle(CompanyAIAnalysisService $aiService): int
    {
        $companyId = $this->option('company');
        $limit = (int) $this->option('limit');
        $useQueue = $this->option('queue');
        $force = $this->option('force');

        if ($companyId) {
            return $this->analyzeSingleCompany($companyId, $aiService, $useQueue);
        }

        return $this->analyzeMultipleCompanies($limit, $force, $aiService, $useQueue);
    }

    private function analyzeSingleCompany(string $companyId, CompanyAIAnalysisService $aiService, bool $useQueue): int
    {
        $company = Company::find($companyId);

        if (!$company) {
            $this->error("Company with ID {$companyId} not found.");
            return 1;
        }

        if (!$company->website) {
            $this->error("Company {$company->name} has no website.");
            return 1;
        }

        $this->info("Analyzing company: {$company->name} ({$company->website})");

        if ($useQueue) {
            CompanyAIAnalysisJob::dispatch($company);
            $this->info('AI analysis job dispatched to queue.');
        } else {
            $success = $aiService->analyzeCompany($company);
            
            if ($success) {
                $company->refresh();
                $this->info('Analysis completed successfully!');
                $this->displayCompanyAnalysis($company);
            } else {
                $this->error('Analysis failed. Check logs for details.');
                return 1;
            }
        }

        return 0;
    }

    private function analyzeMultipleCompanies(int $limit, bool $force, CompanyAIAnalysisService $aiService, bool $useQueue): int
    {
        $companies = $force 
            ? Company::query()->whereNotNull('website')->limit($limit)->get()
            : $aiService->getCompaniesNeedingAnalysis($limit);

        if ($companies->isEmpty()) {
            $this->info('No companies need AI analysis.');
            return 0;
        }

        $this->info("Found {$companies->count()} companies to analyze.");

        $bar = $this->output->createProgressBar($companies->count());
        $bar->start();

        $successful = 0;
        $failed = 0;

        foreach ($companies as $company) {
            /** @var Company $company */
            if ($useQueue) {
                CompanyAIAnalysisJob::dispatch($company);
                $successful++;
            } else {
                $success = $aiService->analyzeCompany($company);
                
                if ($success) {
                    $successful++;
                } else {
                    $failed++;
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        if ($useQueue) {
            $this->info("Dispatched {$successful} AI analysis jobs to queue.");
        } else {
            $this->info("Analysis completed: {$successful} successful, {$failed} failed.");
        }

        return 0;
    }

    private function displayCompanyAnalysis(Company $company): void
    {
        $this->newLine();
        $this->line('<fg=cyan>AI Analysis Results:</>');
        $this->line("Branch: <fg=green>{$company->branch}</>");
        $this->line("Scope: <fg=green>{$company->scope}</>");
        $this->line("Keywords: <fg=green>{$company->getKeywordsString()}</>");
        $this->line("Summary: <fg=yellow>{$company->summary}</>");
        $this->newLine();
    }
}
