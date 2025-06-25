<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ClassificationMethod;
use App\Enums\CompanyClassification;
use App\Enums\CompanyStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $name
 * @property string|null $website
 * @property string|null $domain
 * @property CompanyStatus $status
 * @property CompanyClassification|null $classification
 * @property float|null $confidence_score
 * @property Carbon|null $last_analyzed_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, CompanyAnalysis> $analyses
 * @property-read ClassificationResult|null $latestClassification
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ClassificationResult> $classificationResults
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ScrapingJob> $scrapingJobs
 */
class Company extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => CompanyStatus::class,
            'classification' => CompanyClassification::class,
            'confidence_score' => 'decimal:2',
            'last_analyzed_at' => 'datetime',
        ];
    }

    // Relationships
    public function analyses(): HasMany
    {
        return $this->hasMany(CompanyAnalysis::class);
    }

    public function classificationResults(): HasMany
    {
        return $this->hasMany(ClassificationResult::class);
    }

    public function latestClassification(): HasOne
    {
        return $this->hasOne(ClassificationResult::class)->latestOfMany();
    }

    // Alias for the latest classification result
    public function latestClassificationResult(): HasOne
    {
        return $this->latestClassification();
    }

    public function scrapingJobs(): HasMany
    {
        return $this->hasMany(ScrapingJob::class);
    }

    // Scopes
    public function scopeWithStatus(Builder $query, CompanyStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeWithClassification(Builder $query, CompanyClassification $classification): Builder
    {
        return $query->where('classification', $classification);
    }

    public function scopeClassified(Builder $query): Builder
    {
        return $query->whereNotNull('classification');
    }

    public function scopeUnclassified(Builder $query): Builder
    {
        return $query->whereNull('classification');
    }

    public function scopeWithHighConfidence(Builder $query, float $threshold = 0.8): Builder
    {
        return $query->where('confidence_score', '>=', $threshold);
    }

    public function scopeRecentlyAnalyzed(Builder $query, int $days = 30): Builder
    {
        return $query->where('last_analyzed_at', '>=', now()->subDays($days));
    }

    // Accessors & Mutators
    public function getDomainAttribute(?string $value): ?string
    {
        if ($value) {
            return $value;
        }

        if ($this->website) {
            return parse_url($this->website, PHP_URL_HOST);
        }

        return null;
    }

    public function setWebsiteAttribute(?string $value): void
    {
        $this->attributes['website'] = $value;

        if ($value) {
            $this->attributes['domain'] = parse_url($value, PHP_URL_HOST);
        }
    }

    // Helper Methods
    public function isClassified(): bool
    {
        return $this->classification !== null;
    }

    public function isProcessing(): bool
    {
        return $this->status === CompanyStatus::PROCESSING;
    }

    public function isCompleted(): bool
    {
        return $this->status === CompanyStatus::COMPLETED;
    }

    public function hasFailed(): bool
    {
        return $this->status === CompanyStatus::FAILED;
    }

    public function hasHighConfidence(float $threshold = 0.8): bool
    {
        return $this->confidence_score && $this->confidence_score >= $threshold;
    }

    public function getConfidencePercentage(): ?float
    {
        return $this->confidence_score ? round($this->confidence_score * 100, 1) : null;
    }

    public function needsReanalysis(int $days = 30): bool
    {
        if (! $this->last_analyzed_at) {
            return true;
        }

        return $this->last_analyzed_at->lt(now()->subDays($days));
    }

    public function markAsProcessing(): void
    {
        $this->update(['status' => CompanyStatus::PROCESSING]);
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => CompanyStatus::COMPLETED,
            'last_analyzed_at' => now(),
        ]);
    }

    public function markAsFailed(): void
    {
        $this->update(['status' => CompanyStatus::FAILED]);
    }

    public function updateClassification(
        CompanyClassification $classification,
        float $confidence,
        ClassificationMethod $method = ClassificationMethod::AUTOMATED,
        array $reasoning = [],
        ?User $user = null
    ): ClassificationResult {
        // Update company record
        $this->update([
            'classification' => $classification,
            'confidence_score' => $confidence,
            'status' => CompanyStatus::COMPLETED,
            'last_analyzed_at' => now(),
        ]);

        // Create classification result record
        /** @var ClassificationResult $result */
        $result = $this->classificationResults()->create([
            'classification' => $classification,
            'confidence_score' => $confidence,
            'method' => $method,
            'reasoning' => $reasoning,
            'classified_by' => $user?->id,
        ]);

        return $result;
    }
}
