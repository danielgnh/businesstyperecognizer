<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;

final readonly class WebsiteContentCacheService
{
    private const CACHE_PREFIX = 'website_content:';

    private const META_PREFIX = 'website_meta:';

    private const DEFAULT_TTL_MINUTES = 30;

    private const META_TTL_HOURS = 2;

    /**
     * Get the cache key for website content
     */
    public function getCacheKey(string $url): string
    {
        return self::CACHE_PREFIX.md5($url);
    }

    /**
     * Get the metadata cache key for website content
     */
    public function getMetaCacheKey(string $url): string
    {
        return self::META_PREFIX.md5($url);
    }

    /**
     * Check if website content is cached
     */
    public function isContentCached(string $url): bool
    {
        return Cache::has($this->getCacheKey($url));
    }

    /**
     * Get cached website content
     */
    public function getCachedContent(string $url): ?string
    {
        $cacheKey = $this->getCacheKey($url);
        $content = Cache::get($cacheKey);

        if ($content) {
            // Extend cache life when accessed
            $this->extendCacheLife($url, $content);
        }

        return $content;
    }

    /**
     * Store website content in cache
     */
    public function storeContent(string $url, string $content, ?int $ttlMinutes = null): void
    {
        $ttl = $ttlMinutes ?? self::DEFAULT_TTL_MINUTES;
        $cacheKey = $this->getCacheKey($url);

        Cache::put($cacheKey, $content, now()->addMinutes($ttl));
    }

    /**
     * Store metadata about the scraping
     */
    public function storeMetadata(string $url, array $metadata): void
    {
        $metaCacheKey = $this->getMetaCacheKey($url);

        $defaultMetadata = [
            'scraped_at' => now()->toISOString(),
            'content_size' => strlen($metadata['content'] ?? ''),
            'url' => $url,
        ];

        $finalMetadata = array_merge($defaultMetadata, $metadata);

        Cache::put($metaCacheKey, $finalMetadata, now()->addHours(self::META_TTL_HOURS));
    }

    /**
     * Extend cache life when content is accessed
     */
    public function extendCacheLife(string $url, string $content): void
    {
        $cacheKey = $this->getCacheKey($url);
        Cache::put($cacheKey, $content, now()->addMinutes(self::DEFAULT_TTL_MINUTES));
    }

    /**
     * Clear cached content for a URL
     */
    public function clearCache(string $url): void
    {
        $cacheKey = $this->getCacheKey($url);
        $metaCacheKey = $this->getMetaCacheKey($url);

        Cache::forget($cacheKey);
        Cache::forget($metaCacheKey);
    }

    /**
     * Get metadata for cached content
     */
    public function getMetadata(string $url): ?array
    {
        $metaCacheKey = $this->getMetaCacheKey($url);

        return Cache::get($metaCacheKey);
    }

    /**
     * Check if content is already cached and extend TTL if it exists
     */
    public function checkAndExtendIfCached(string $url): bool
    {
        if ($this->isContentCached($url)) {
            $content = Cache::get($this->getCacheKey($url));
            if ($content) {
                $this->extendCacheLife($url, $content);

                return true;
            }
        }

        return false;
    }
}
