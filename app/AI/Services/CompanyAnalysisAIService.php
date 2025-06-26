<?php

declare(strict_types=1);

namespace App\AI\Services;

use App\AI\Schemas\CompanyAnalysisSchema;
use App\Exceptions\CompanyAnalysisException;
use App\Models\Company;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Prism;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\NumberSchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;

final readonly class CompanyAnalysisAIService
{
    /**
     * Analyze company website content using AI
     */
    public function analyzeCompany(Company $company, string $websiteContent): CompanyAnalysisSchema
    {
        try {
            $schema = new ObjectSchema(
                name: 'company_analysis',
                description: 'AI analysis of a company including branch, scope, and keywords',
                properties: [
                    new StringSchema('summary', 'A concise business description of the company'),
                    new StringSchema('branch', 'The primary industry or sector'),
                    new StringSchema('scope', 'The specific area of focus within their branch'),
                    new ArraySchema(
                        name: 'keywords',
                        description: 'Relevant business keywords',
                        items: new StringSchema('keyword', 'A business-relevant keyword')
                    ),
                    new NumberSchema('confidence', 'Confidence score from 0.0 to 1.0'),
                ],
                requiredFields: ['summary', 'branch', 'scope', 'keywords', 'confidence']
            );

            /** @phpstan-ignore-next-line */
            $systemPrompt = view('ai.prompts.company-analysis-system')->render();
            /** @phpstan-ignore-next-line */
            $userPrompt = view('ai.prompts.company-analysis', [
                'company' => $company,
                'websiteContent' => $this->preprocessContent($websiteContent),
            ])->render();

            $response = Prism::structured()
                ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
                ->withSystemPrompt($systemPrompt)
                ->withPrompt($userPrompt)
                ->withSchema($schema)
                ->asStructured();

            return new CompanyAnalysisSchema(
                summary: $response->structured['summary'],
                branch: $response->structured['branch'],
                scope: $response->structured['scope'],
                keywords: $response->structured['keywords'],
                confidence: $response->structured['confidence']
            );

        } catch (\Exception $e) {
            throw new CompanyAnalysisException(
                "Failed to analyze company with AI: {$e->getMessage()}",
                0,
                $company,
                $e
            );
        }
    }

    /**
     * Preprocess website content for AI analysis
     */
    private function preprocessContent(string $content): string
    {
        // Remove HTML tags and clean up content
        $cleanContent = strip_tags($content);
        
        // Remove extra whitespace and normalize
        $cleanContent = preg_replace('/\s+/', ' ', $cleanContent);
        
        // Limit content length to avoid token limits (approximately 8000 characters)
        if (strlen($cleanContent) > 8000) {
            $cleanContent = substr($cleanContent, 0, 8000) . '...';
        }
        
        return trim($cleanContent);
    }
} 