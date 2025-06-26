<?php

declare(strict_types=1);

namespace App\Jobs\Scrape\Exceptions;

use App\Models\Company;
use Throwable;

class SocialMediaJobException extends ScrapingJobException
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Company $company = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $company, 'social_media', $previous);
    }
}
