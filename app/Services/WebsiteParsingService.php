<?php

declare(strict_types=1);

namespace App\Services;

class WebsiteParsingService
{
    /**
     * Extract domain from website URL
     */
    public function extractDomain(string $website): string
    {
        $parsed = parse_url($website);

        if (isset($parsed['host'])) {
            return strtolower($parsed['host']);
        }

        return '';
    }

    /**
     * Extract company name from website URL
     */
    public function extractCompanyNameFromWebsite(string $website): string
    {
        $domain = $this->extractDomain($website);

        if (! $domain) {
            return '';
        }

        // Remove www prefix
        $domain = preg_replace('/^www\./', '', $domain);

        // Extract main domain name before TLD
        $pattern = '/^([a-zA-Z0-9\-]+)(?:\.[a-zA-Z]{2,})*$/';
        if (preg_match($pattern, $domain, $matches)) {
            $companyName = $matches[1];

            // Replace hyphens and underscores with spaces
            $companyName = str_replace(['-', '_'], ' ', $companyName);

            // Remove common prefixes
            $companyName = preg_replace('/^(the|get|my|your|try)\s+/i', '', $companyName);

            // Remove common suffixes
            $companyName = preg_replace('/\s+(app|inc|corp|llc|ltd|company|co)$/i', '', $companyName);

            // Capitalize words
            return ucwords(strtolower(trim($companyName)));
        }

        return '';
    }

    /**
     * Validate if URL is properly formatted
     */
    public function isValidUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Normalize URL by adding protocol if missing
     */
    public function normalizeUrl(string $url): string
    {
        if (! preg_match('/^https?:\/\//', $url)) {
            $url = 'https://'.$url;
        }

        return rtrim($url, '/');
    }

    /**
     * Extract subdomain from URL
     */
    public function extractSubdomain(string $website): ?string
    {
        $domain = $this->extractDomain($website);

        if (! $domain) {
            return null;
        }

        $parts = explode('.', $domain);

        if (count($parts) > 2) {
            return $parts[0];
        }

        return null;
    }

    /**
     * Check if domain appears to be a business domain
     */
    public function isBusinessDomain(string $domain): bool
    {
        $businessIndicators = [
            'corp', 'inc', 'llc', 'ltd', 'company', 'business',
            'enterprise', 'group', 'solutions', 'services',
            'consulting', 'technologies', 'systems',
        ];

        $domainLower = strtolower($domain);

        foreach ($businessIndicators as $indicator) {
            if (str_contains($domainLower, $indicator)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract TLD (top-level domain) from URL
     */
    public function extractTld(string $website): string
    {
        $domain = $this->extractDomain($website);

        if (! $domain) {
            return '';
        }

        $parts = explode('.', $domain);

        return end($parts);
    }

    /**
     * Generate variations of domain for searching
     */
    public function getDomainVariations(string $domain): array
    {
        $variations = [$domain];

        // Add www version
        if (! str_starts_with($domain, 'www.')) {
            $variations[] = 'www.'.$domain;
        }

        // Remove www version
        if (str_starts_with($domain, 'www.')) {
            $variations[] = substr($domain, 4);
        }

        return array_unique($variations);
    }

    /**
     * Check if URL is likely a homepage
     */
    public function isHomepage(string $url): bool
    {
        $parsed = parse_url($url);
        $path = $parsed['path'] ?? '/';

        return $path === '/' || $path === '';
    }

    /**
     * Extract base URL (protocol + domain)
     */
    public function getBaseUrl(string $url): string
    {
        $parsed = parse_url($url);

        if (! isset($parsed['scheme']) || ! isset($parsed['host'])) {
            return '';
        }

        return $parsed['scheme'].'://'.$parsed['host'];
    }
}
