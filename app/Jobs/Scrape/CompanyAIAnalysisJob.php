<?php

declare(strict_types=1);

namespace App\Jobs\Scrape;

use App\Actions\AnalyzeCompanyWithAIAction;
use App\Jobs\Scrape\Exceptions\ScrapingJobException;
use App\Models\Company;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class CompanyAIAnalysisJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $maxExceptions = 2;

    public int $timeout = 180; // 3 minutes for AI analysis

    public function __construct(
        public Company $company
    ) {}

    /**
     * @throws Throwable
     * @throws ScrapingJobException
     */
    public function handle(AnalyzeCompanyWithAIAction $analyzeAction): void
    {
        try {
            throw_if(
                !$this->company->website,
                new ScrapingJobException('Company has no website for AI analysis', 0, $this->company, 'ai_analysis')
            );

            // Execute AI analysis
            $analyzeAction->execute($this->company);

            logger()->info('Company AI analysis job completed successfully', [
                'company_id' => $this->company->id,
                'company_name' => $this->company->name,
            ]);

        } catch (ScrapingJobException $e) {
            logger()->error('Company AI analysis job failed', $e->getContext());
            throw $e;
        } catch (Exception $e) {
            $jobException = new ScrapingJobException(
                "Unexpected error during company AI analysis job: {$e->getMessage()}",
                0,
                $this->company,
                'ai_analysis',
                $e
            );

            logger()->error('Company AI analysis job failed', $jobException->getContext());
            throw $jobException;
        }
    }
} 