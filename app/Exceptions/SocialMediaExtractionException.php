<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Throwable;

class SocialMediaExtractionException extends Exception
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get context for logging
     */
    public function getContext(): array
    {
        return [
            'code' => $this->getCode(),
            'message' => $this->getMessage(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
        ];
    }
}
