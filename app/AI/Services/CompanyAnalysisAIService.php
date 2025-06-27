<?php

declare(strict_types=1);

namespace App\AI\Services;

use App\AI\Schemas\CompanyAnalysisSchema;
use App\Dtos\CompanyAnalysisDto;
use App\Exceptions\CompanyAnalysisException;
use App\Models\Company;
use Exception;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Prism;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\NumberSchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;
use Throwable;

final readonly class CompanyAnalysisAIService
{
    /**
     * Analyze company website content using AI
     * @throws CompanyAnalysisException
     */
    public function analyzeCompany(Company $company, string $websiteContent): CompanyAnalysisDto
    {
        try {
            $schema = CompanyAnalysisSchema::define();
            $response = Prism::structured()
                ->using(Provider::OpenAI, 'gpt-4o')
                ->withSystemPrompt(view('prompts.company-analysis-system'))
                ->withPrompt(
                    view('prompts.company-analysis', [
                        'company' => $company,
                        'websiteContent' => $this->preprocessContent($websiteContent),
                    ])
                )
                ->withSchema($schema)
                ->asStructured();

            return new CompanyAnalysisDto(
                summary: $response->structured['summary'],
                branch: $response->structured['branch'],
                scope: $response->structured['scope'],
                keywords: $response->structured['keywords'],
                confidence: $response->structured['confidence']
            );

        } catch (Exception|Throwable $e ) {
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
        // Remove HTML tags and clean-up content
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
