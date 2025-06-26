<?php

declare(strict_types=1);

namespace App\Jobs\Scrape;

use App\Jobs\Scrape\Exceptions\SocialMediaJobException;
use App\Models\Company;
use App\Models\CompanyAnalysis;
use App\Services\SocialMediaExtractionService;
use App\Services\WebsiteContentCacheService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SocialMediaScraperJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $maxExceptions = 2;

    public int $timeout = 120;

    public function __construct(
        public Company $company
    ) {}

    /**
     * @throws Throwable
     * @throws SocialMediaJobException
     */
    public function handle(
        WebsiteContentCacheService $cacheService,
        SocialMediaExtractionService $extractionService
    ): void {
        try {
            throw_if(
                !$this->company->website,
                new SocialMediaJobException('Company has no website for social media scraping', 0, $this->company)
            );

            $websiteContent = $cacheService->getCachedContent($this->company->website);

            if (! $websiteContent) {
                WebsiteContentScraperJob::dispatch($this->company)
                    ->delay(now()->addMinutes(2));

                static::dispatch($this->company)
                    ->delay(now()->addMinutes(3));

                logger()->info('Website content not cached, dispatching content job and retrying', [
                    'company_id' => $this->company->id,
                    'website' => $this->company->website,
                ]);

                return;
            }

            $socialMediaLinks = $extractionService->extractSocialMediaLinks($websiteContent);
            $categorizedLinks = $extractionService->categorizeLinks($socialMediaLinks);
            $engagementIndicators = $extractionService->detectEngagementIndicators($websiteContent);
            $confidence = $extractionService->calculateConfidence($socialMediaLinks);

            CompanyAnalysis::query()->create([
                'company_id' => $this->company->id,
                'data_source' => 'social_media_discovery',
                'raw_data' => [
                    'discovered_links' => $socialMediaLinks,
                    'scraped_url' => $this->company->website,
                    'content_length' => strlen($websiteContent),
                ],
                'processed_data' => [
                    'social_platforms' => $categorizedLinks,
                    'link_count' => count($socialMediaLinks),
                ],
                'indicators' => [
                    'has_social_presence' => ! empty($socialMediaLinks),
                    'platform_diversity' => count($categorizedLinks),
                    'social_engagement_indicators' => $engagementIndicators,
                ],
                'source_weight' => 0.3, // Social media discovery has moderate weight
                'source_confidence' => $confidence,
                'scraped_at' => now(),
            ]);

            logger()->info('Social media links extracted successfully', [
                'company_id' => $this->company->id,
                'links_found' => count($socialMediaLinks),
                'platforms' => array_keys($categorizedLinks),
            ]);

        } catch (SocialMediaJobException $e) {
            logger()->error('Social media scraping job failed', $e->getContext());
            throw $e;
        } catch (Exception $e) {
            $jobException = new SocialMediaJobException("Unexpected error during social media scraping: {$e->getMessage()}", 0, $this->company, $e);

            logger()->error('Social media scraping job failed', $jobException->getContext());
            throw $jobException;
        }
    }
}
