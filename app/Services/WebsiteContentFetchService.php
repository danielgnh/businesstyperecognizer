<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\WebsiteContentException;
use Illuminate\Support\Facades\Http;

final readonly class WebsiteContentFetchService
{
    private const TIMEOUT_SECONDS = 30;

    private const RETRY_ATTEMPTS = 2;

    private const RETRY_DELAY_MS = 1000;

    private const MIN_CONTENT_LENGTH = 100;

    /**
     * Fetch website content from URL
     */
    public function fetchContent(string $url): string
    {
        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->retry(self::RETRY_ATTEMPTS, self::RETRY_DELAY_MS)
                ->withHeaders($this->getDefaultHeaders())
                ->get($url);

            throw_unless($response->successful(), new WebsiteContentException(
                "HTTP request failed with status {$response->status()}",
                $response->status(),
                $url
            ));

            $content = $response->body();

            $this->validateContent($content, $url);

            return $content;

        } catch (WebsiteContentException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new WebsiteContentException(
                "Failed to fetch website content: {$e->getMessage()}",
                0,
                $url,
                $e
            );
        }
    }

    /**
     * Validate fetched content
     */
    private function validateContent(string $content, string $url): void
    {
        throw_if(empty($content) || strlen($content) < self::MIN_CONTENT_LENGTH, new WebsiteContentException(
            'Website content too short or empty (length: '.strlen($content).')',
            0,
            $url
        ));

        throw_unless($this->isHtmlContent($content), new WebsiteContentException(
            'Content does not appear to be HTML',
            0,
            $url
        ));
    }

    /**
     * Check if content appears to be HTML
     */
    private function isHtmlContent(string $content): bool
    {
        $lowerContent = strtolower($content);

        return str_contains($lowerContent, '<html') ||
               str_contains($lowerContent, '<!doctype') ||
               str_contains($lowerContent, '<head') ||
               str_contains($lowerContent, '<body');
    }

    /**
     * Get default HTTP headers for requests
     */
    private function getDefaultHeaders(): array
    {
        return [
            'User-Agent' => 'Mozilla/5.0 (compatible; BusinessAnalyzer/1.0)',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'en-US,en;q=0.5',
            'Accept-Encoding' => 'gzip, deflate',
            'Connection' => 'keep-alive',
        ];
    }
}
