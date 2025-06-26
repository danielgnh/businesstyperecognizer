<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Throwable;

class WebsiteContentException extends Exception
{
    public function __construct(
        string $message = '',
        int $code = 0,
        private readonly ?string $url = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the URL that caused the exception
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * Get context for logging
     */
    public function getContext(): array
    {
        return [
            'url' => $this->url,
            'code' => $this->getCode(),
            'message' => $this->getMessage(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
        ];
    }
}
