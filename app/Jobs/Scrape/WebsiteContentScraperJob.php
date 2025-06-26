<?php

declare(strict_types=1);

namespace App\Jobs\Scrape;

use App\Jobs\Scrape\Exceptions\WebsiteContentJobException;
use App\Models\Company;
use App\Services\WebsiteContentCacheService;
use App\Services\WebsiteContentFetchService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class WebsiteContentScraperJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $maxExceptions = 2;

    public int $timeout = 60;

    public function __construct(
        public Company $company
    ) {}

    /**
     * @throws Throwable
     * @throws WebsiteContentJobException
     */
    public function handle(
        WebsiteContentCacheService $cacheService,
        WebsiteContentFetchService $fetchService
    ): void {
        try {
            throw_if(
                !$this->company->website,
                new WebsiteContentJobException(
                    'Company has no website for content scraping',
                    0,
                    $this->company
                )
            );

            // Check if content is already cached and extend TTL if it exists
            if ($cacheService->checkAndExtendIfCached($this->company->website)) {
                logger()->info('Website content already cached, extending TTL', [
                    'company_id' => $this->company->id,
                    'website' => $this->company->website,
                ]);

                return;
            }

            // Fetch website content
            $content = $fetchService->fetchContent($this->company->website);

            // Cache the content
            $cacheService->storeContent($this->company->website, $content);

            // Store metadata about the scraping
            $cacheService->storeMetadata($this->company->website, [
                'company_id' => $this->company->id,
                'content' => $content,
            ]);

            logger()->info('Website content cached successfully', [
                'company_id' => $this->company->id,
                'website' => $this->company->website,
                'content_size' => strlen($content),
            ]);

        } catch (WebsiteContentJobException $e) {
            logger()->error('Website content scraping job failed', $e->getContext());
            throw $e;
        } catch (\Exception $e) {
            $jobException = new WebsiteContentJobException(
                "Unexpected error during website content scraping: {$e->getMessage()}",
                0,
                $this->company,
                $e
            );

            logger()->error('Website content scraping job failed', $jobException->getContext());
            throw $jobException;
        }
    }

    /**
     * Check if website content is cached
     */
    public static function isContentCached(string $url): bool
    {
        return app(WebsiteContentCacheService::class)->isContentCached($url);
    }

    /**
     * Get cached website content
     */
    public static function getCachedContent(string $url): ?string
    {
        return app(WebsiteContentCacheService::class)->getCachedContent($url);
    }

    /**
     * Clear cached content for a URL
     */
    public static function clearCache(string $url): void
    {
        app(WebsiteContentCacheService::class)->clearCache($url);
    }
}
