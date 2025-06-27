<?php

declare(strict_types=1);

namespace App\AI\Schemas;

use Prism\Prism\Contracts\Schema;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\NumberSchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;

final readonly class CompanyAnalysisSchema
{
    public function define(): Schema
    {
        return new ObjectSchema(
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
    }
}
