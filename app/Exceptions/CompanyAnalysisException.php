<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\Company;
use Exception;
use Throwable;

class CompanyAnalysisException extends Exception
{
    public function __construct(
        string $message = '',
        int $code = 0,
        private readonly ?Company $company = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the company that caused the exception
     */
    public function getCompany(): ?Company
    {
        return $this->company;
    }

    /**
     * Get context for logging
     */
    public function getContext(): array
    {
        return [
            'company_id' => $this->company?->id,
            'company_name' => $this->company?->name,
            'company_website' => $this->company?->website,
            'code' => $this->getCode(),
            'message' => $this->getMessage(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
        ];
    }
} 