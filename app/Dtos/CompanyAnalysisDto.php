<?php

declare(strict_types=1);

namespace App\Dtos;

final class CompanyAnalysisDto
{
    public function __construct(
        public string $summary,
        public string $branch,
        public string $scope,
        public array $keywords,
        public float $confidence
    ) {}
}
