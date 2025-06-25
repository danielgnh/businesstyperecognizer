<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ScrapingJobStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $company_id
 * @property string $job_type
 * @property ScrapingJobStatus $status
 * @property int $priority
 * @property int $attempts
 * @property int $max_attempts
 * @property string|null $error_message
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property Carbon $created_at
 * @property-read Company $company
 */
class ScrapingJob extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'status' => ScrapingJobStatus::class,
            'priority' => 'integer',
            'attempts' => 'integer',
            'max_attempts' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (ScrapingJob $job) {
            if ($job->created_at === null) {
                $job->created_at = now();
            }

            if ($job->priority === null) {
                $job->priority = 0;
            }

            if ($job->attempts === null) {
                $job->attempts = 0;
            }

            if ($job->max_attempts === null) {
                $job->max_attempts = 3;
            }

            if ($job->status === null) {
                $job->status = ScrapingJobStatus::QUEUED;
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeWithStatus(Builder $query, ScrapingJobStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeQueued(Builder $query): Builder
    {
        return $query->where('status', ScrapingJobStatus::QUEUED);
    }

    public function scopeProcessing(Builder $query): Builder
    {
        return $query->where('status', ScrapingJobStatus::PROCESSING);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', ScrapingJobStatus::COMPLETED);
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', ScrapingJobStatus::FAILED);
    }

    public function scopeWithJobType(Builder $query, string $jobType): Builder
    {
        return $query->where('job_type', $jobType);
    }

    public function scopeWebsiteJobs(Builder $query): Builder
    {
        return $query->where('job_type', 'website');
    }

    public function scopeSocialMediaJobs(Builder $query): Builder
    {
        return $query->where('job_type', 'social_media');
    }

    public function scopeGoogleBusinessJobs(Builder $query): Builder
    {
        return $query->where('job_type', 'google_business');
    }

    public function scopeWithHighPriority(Builder $query): Builder
    {
        return $query->where('priority', '>', 0);
    }

    public function scopeByPriority(Builder $query): Builder
    {
        return $query->orderBy('priority', 'desc')->orderBy('created_at', 'asc');
    }

    public function scopeRetryable(Builder $query): Builder
    {
        return $query->where('status', ScrapingJobStatus::FAILED)
            ->whereColumn('attempts', '<', 'max_attempts');
    }

    public function scopeExhausted(Builder $query): Builder
    {
        return $query->where('status', ScrapingJobStatus::FAILED)
            ->whereColumn('attempts', '>=', 'max_attempts');
    }

    public function scopeStuck(Builder $query, int $minutes = 30): Builder
    {
        return $query->where('status', ScrapingJobStatus::PROCESSING)
            ->where('started_at', '<', now()->subMinutes($minutes));
    }

    public function scopeRecent(Builder $query, int $hours = 24): Builder
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    public function isQueued(): bool
    {
        return $this->status === ScrapingJobStatus::QUEUED;
    }

    public function isProcessing(): bool
    {
        return $this->status === ScrapingJobStatus::PROCESSING;
    }

    public function isCompleted(): bool
    {
        return $this->status === ScrapingJobStatus::COMPLETED;
    }

    public function hasFailed(): bool
    {
        return $this->status === ScrapingJobStatus::FAILED;
    }

    public function isInProgress(): bool
    {
        return $this->status->isInProgress();
    }

    public function hasHighPriority(): bool
    {
        return $this->priority > 0;
    }

    public function canRetry(): bool
    {
        return $this->hasFailed() && $this->attempts < $this->max_attempts;
    }

    public function isExhausted(): bool
    {
        return $this->hasFailed() && $this->attempts >= $this->max_attempts;
    }

    public function isStuck(int $minutes = 30): bool
    {
        return $this->isProcessing() &&
               $this->started_at &&
               $this->started_at->lt(now()->subMinutes($minutes));
    }

    public function getDurationInSeconds(): ?int
    {
        if ($this->started_at === null) {
            return null;
        }

        $endTime = $this->completed_at ?? now();

        return (int) $this->started_at->diffInSeconds($endTime);
    }

    public function getDurationHuman(): ?string
    {
        if ($this->started_at === null) {
            return null;
        }

        $endTime = $this->completed_at ?? now();

        return $this->started_at->diffForHumans($endTime, ['syntax' => true]);
    }

    public function getWaitTimeInSeconds(): int
    {
        if ($this->started_at) {
            return (int) $this->created_at->diffInSeconds($this->started_at);
        }

        return (int) $this->created_at->diffInSeconds(now());
    }

    public function getWaitTimeHuman(): string
    {
        if ($this->started_at) {
            return $this->created_at->diffForHumans($this->started_at, ['syntax' => true]);
        }

        return $this->created_at->diffForHumans(now(), ['syntax' => true]);
    }

    public function getRemainingAttempts(): int
    {
        return max(0, $this->max_attempts - $this->attempts);
    }

    public function getAttemptPercentage(): float
    {
        return ($this->attempts / $this->max_attempts) * 100;
    }

    // Status Management Methods
    public function markAsProcessing(): self
    {
        $this->update([
            'status' => ScrapingJobStatus::PROCESSING,
            'started_at' => now(),
        ]);

        return $this;
    }

    public function markAsCompleted(): self
    {
        $this->update([
            'status' => ScrapingJobStatus::COMPLETED,
            'completed_at' => now(),
            'error_message' => null,
        ]);

        return $this;
    }

    public function markAsFailed(?string $errorMessage = null): self
    {
        $this->update([
            'status' => ScrapingJobStatus::FAILED,
            'completed_at' => now(),
            'error_message' => $errorMessage,
            'attempts' => $this->attempts + 1,
        ]);

        return $this;
    }

    public function resetForRetry(): self
    {
        $this->update([
            'status' => ScrapingJobStatus::QUEUED,
            'started_at' => null,
            'completed_at' => null,
            'error_message' => null,
        ]);

        return $this;
    }

    public function incrementPriority(int $amount = 1): self
    {
        $this->increment('priority', $amount);

        return $this;
    }

    public function decrementPriority(int $amount = 1): self
    {
        $this->decrement('priority', $amount);

        return $this;
    }

    // Static factory methods
    public static function createWebsiteJob(
        Company $company,
        int $priority = 0,
        int $maxAttempts = 3
    ): self {
        return self::query()->create([
            'company_id' => $company->id,
            'job_type' => 'website',
            'priority' => $priority,
            'max_attempts' => $maxAttempts,
        ]);
    }

    public static function createSocialMediaJob(
        Company $company,
        int $priority = 0,
        int $maxAttempts = 3
    ): self {
        return self::query()->create([
            'company_id' => $company->id,
            'job_type' => 'social_media',
            'priority' => $priority,
            'max_attempts' => $maxAttempts,
        ]);
    }

    public static function createGoogleBusinessJob(
        Company $company,
        int $priority = 0,
        int $maxAttempts = 3
    ): self {
        return self::query()->create([
            'company_id' => $company->id,
            'job_type' => 'google_business',
            'priority' => $priority,
            'max_attempts' => $maxAttempts,
        ]);
    }

    public static function createAnalysisJob(
        Company $company,
        int $priority = 0,
        int $maxAttempts = 3
    ): self {
        return self::query()->create([
            'company_id' => $company->id,
            'job_type' => 'analysis',
            'priority' => $priority,
            'max_attempts' => $maxAttempts,
        ]);
    }

    // Bulk operations
    public static function createJobsForCompany(Company $company, int $priority = 0): array
    {
        $jobs = [];
        $jobTypes = ['website', 'social_media', 'google_business'];

        foreach ($jobTypes as $jobType) {
            $jobs[] = self::query()->create([
                'company_id' => $company->id,
                'job_type' => $jobType,
                'priority' => $priority,
            ]);
        }

        return $jobs;
    }
}
