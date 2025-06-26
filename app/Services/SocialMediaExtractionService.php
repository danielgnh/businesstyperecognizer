<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\SocialMediaExtractionException;

final readonly class SocialMediaExtractionService
{
    private const SOCIAL_MEDIA_PATTERNS = [
        'facebook' => [
            '/(?:https?:\/\/)?(?:www\.)?facebook\.com\/[a-zA-Z0-9._%+-]+/i',
            '/(?:https?:\/\/)?(?:www\.)?fb\.com\/[a-zA-Z0-9._%+-]+/i',
        ],
        'twitter' => [
            '/(?:https?:\/\/)?(?:www\.)?twitter\.com\/[a-zA-Z0-9_]+/i',
            '/(?:https?:\/\/)?(?:www\.)?x\.com\/[a-zA-Z0-9_]+/i',
        ],
        'linkedin' => [
            '/(?:https?:\/\/)?(?:www\.)?linkedin\.com\/(?:company|in)\/[a-zA-Z0-9-]+/i',
        ],
        'instagram' => [
            '/(?:https?:\/\/)?(?:www\.)?instagram\.com\/[a-zA-Z0-9_.]+/i',
        ],
        'youtube' => [
            '/(?:https?:\/\/)?(?:www\.)?youtube\.com\/(?:channel|user|c)\/[a-zA-Z0-9_-]+/i',
            '/(?:https?:\/\/)?(?:www\.)?youtu\.be\/[a-zA-Z0-9_-]+/i',
        ],
        'tiktok' => [
            '/(?:https?:\/\/)?(?:www\.)?tiktok\.com\/@[a-zA-Z0-9_.]+/i',
        ],
    ];

    private const ENGAGEMENT_PATTERNS = [
        'follow_us' => '/follow\s+us\s+on/i',
        'social_icons' => '/social.*icons?/i',
        'share_buttons' => '/share.*(?:facebook|twitter|linkedin)/i',
        'social_feeds' => '/(?:twitter|instagram)\s+feed/i',
    ];

    /**
     * Extract social media links from website content
     */
    public function extractSocialMediaLinks(string $content): array
    {
        try {
            $socialMediaLinks = [];

            foreach (self::SOCIAL_MEDIA_PATTERNS as $platform => $patterns) {
                foreach ($patterns as $pattern) {
                    if (preg_match_all($pattern, $content, $matches)) {
                        foreach ($matches[0] as $match) {
                            $cleanUrl = $this->cleanUrl($match);
                            if (! in_array($cleanUrl, $socialMediaLinks)) {
                                $socialMediaLinks[] = $cleanUrl;
                            }
                        }
                    }
                }
            }

            return array_unique($socialMediaLinks);

        } catch (\Exception $e) {
            throw new SocialMediaExtractionException(
                "Failed to extract social media links: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Categorize social media links by platform
     */
    public function categorizeLinks(array $links): array
    {
        $platforms = [];

        foreach ($links as $link) {
            $platform = $this->identifyPlatform($link);
            if ($platform) {
                if (! isset($platforms[$platform])) {
                    $platforms[$platform] = [];
                }
                $platforms[$platform][] = $link;
            }
        }

        return $platforms;
    }

    /**
     * Detect social engagement indicators in content
     */
    public function detectEngagementIndicators(string $content): array
    {
        $indicators = [];

        foreach (self::ENGAGEMENT_PATTERNS as $indicator => $pattern) {
            if (preg_match($pattern, $content)) {
                $indicators[] = $indicator;
            }
        }

        return $indicators;
    }

    /**
     * Calculate confidence score based on found links
     */
    public function calculateConfidence(array $socialMediaLinks): float
    {
        if (empty($socialMediaLinks)) {
            return 0.1; // Low confidence if no links found
        }

        $baseConfidence = 0.7;
        $platformBonus = count($this->categorizeLinks($socialMediaLinks)) * 0.05;
        $linkCountBonus = min(count($socialMediaLinks) * 0.02, 0.15);

        return min(1.0, $baseConfidence + $platformBonus + $linkCountBonus);
    }

    /**
     * Identify social media platform from URL
     */
    private function identifyPlatform(string $url): ?string
    {
        $url = strtolower($url);

        if (str_contains($url, 'facebook.com') || str_contains($url, 'fb.com')) {
            return 'facebook';
        }
        if (str_contains($url, 'twitter.com') || str_contains($url, 'x.com')) {
            return 'twitter';
        }
        if (str_contains($url, 'linkedin.com')) {
            return 'linkedin';
        }
        if (str_contains($url, 'instagram.com')) {
            return 'instagram';
        }
        if (str_contains($url, 'youtube.com') || str_contains($url, 'youtu.be')) {
            return 'youtube';
        }
        if (str_contains($url, 'tiktok.com')) {
            return 'tiktok';
        }

        return null;
    }

    /**
     * Clean and normalize URL
     */
    private function cleanUrl(string $url): string
    {
        // Remove tracking parameters and normalize URL
        $url = trim($url);

        // Add protocol if missing
        if (! preg_match('/^https?:\/\//', $url)) {
            $url = 'https://'.$url;
        }

        // Remove common tracking parameters
        $url = preg_replace('/[?&](?:utm_|fbclid=|gclid=)[^&]*/', '', $url);

        return rtrim($url, '/');
    }
}
