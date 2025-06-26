<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Jobs\Scrape\Exceptions\ScrapingJobException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (WebsiteContentException $e) {
            logger()->error('Website content exception occurred', $e->getContext());
        });

        $this->reportable(function (SocialMediaExtractionException $e) {
            logger()->error('Social media extraction exception occurred', $e->getContext());
        });

        $this->reportable(function (ScrapingJobException $e) {
            logger()->error('Scraping job exception occurred', $e->getContext());
        });

        $this->reportable(function (Throwable $e) {
            // Log all other exceptions with context
            logger()->error('Unhandled exception occurred', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
        });
    }
}
