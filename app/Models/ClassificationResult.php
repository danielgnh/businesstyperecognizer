<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ClassificationMethod;
use App\Enums\CompanyClassification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $company_id
 * @property CompanyClassification $classification
 * @property float $confidence_score
 * @property ClassificationMethod $method
 * @property array $reasoning
 * @property string|null $classified_by
 * @property Carbon $created_at
 * @property-read Company $company
 * @property-read User|null $classifier
 */
class ClassificationResult extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'classification' => CompanyClassification::class,
            'method' => ClassificationMethod::class,
            'confidence_score' => 'decimal:2',
            'reasoning' => 'array',
            'created_at' => 'datetime',
        ];
    }

    // Boot method to automatically set created_at
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (ClassificationResult $result) {
            if ($result->created_at === null) {
                $result->created_at = now();
            }
        });
    }

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function classifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'classified_by');
    }

    // Scopes
    public function scopeWithClassification(Builder $query, CompanyClassification $classification): Builder
    {
        return $query->where('classification', $classification);
    }

    public function scopeWithMethod(Builder $query, ClassificationMethod $method): Builder
    {
        return $query->where('method', $method);
    }

    public function scopeAutomated(Builder $query): Builder
    {
        return $query->where('method', ClassificationMethod::AUTOMATED);
    }

    public function scopeManual(Builder $query): Builder
    {
        return $query->where('method', ClassificationMethod::MANUAL);
    }

    public function scopeAiVerified(Builder $query): Builder
    {
        return $query->where('method', ClassificationMethod::AI_VERIFIED);
    }

    public function scopeWithHighConfidence(Builder $query, float $threshold = 0.8): Builder
    {
        return $query->where('confidence_score', '>=', $threshold);
    }

    public function scopeWithLowConfidence(Builder $query, float $threshold = 0.6): Builder
    {
        return $query->where('confidence_score', '<', $threshold);
    }

    public function scopeB2B(Builder $query): Builder
    {
        return $query->where('classification', CompanyClassification::B2B);
    }

    public function scopeB2C(Builder $query): Builder
    {
        return $query->where('classification', CompanyClassification::B2C);
    }

    public function scopeHybrid(Builder $query): Builder
    {
        return $query->where('classification', CompanyClassification::HYBRID);
    }

    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeByUser(Builder $query, User $user): Builder
    {
        return $query->where('classified_by', $user->id);
    }

    // Helper Methods
    public function isB2B(): bool
    {
        return $this->classification === CompanyClassification::B2B;
    }

    public function isB2C(): bool
    {
        return $this->classification === CompanyClassification::B2C;
    }

    public function isHybrid(): bool
    {
        return $this->classification === CompanyClassification::HYBRID;
    }

    public function isAutomated(): bool
    {
        return $this->method === ClassificationMethod::AUTOMATED;
    }

    public function isManual(): bool
    {
        return $this->method === ClassificationMethod::MANUAL;
    }

    public function isAiVerified(): bool
    {
        return $this->method === ClassificationMethod::AI_VERIFIED;
    }

    public function hasHighConfidence(float $threshold = 0.8): bool
    {
        return $this->confidence_score >= $threshold;
    }

    public function hasLowConfidence(float $threshold = 0.6): bool
    {
        return $this->confidence_score < $threshold;
    }

    public function getConfidencePercentage(): float
    {
        return round($this->confidence_score * 100, 1);
    }

    public function getConfidenceLevel(): string
    {
        return match (true) {
            $this->confidence_score >= 0.9 => 'Very High',
            $this->confidence_score >= 0.8 => 'High',
            $this->confidence_score >= 0.7 => 'Medium',
            $this->confidence_score >= 0.6 => 'Low',
            default => 'Very Low',
        };
    }

    public function getReasoningSummary(): string
    {
        if (empty($this->reasoning)) {
            return 'No reasoning provided';
        }

        $summary = $this->reasoning['summary'] ?? '';

        if (empty($summary) && isset($this->reasoning['indicators'])) {
            $indicators = $this->reasoning['indicators'];
            $indicatorCount = count($indicators);
            $summary = "Based on {$indicatorCount} indicators: ".implode(', ', array_slice($indicators, 0, 3));

            if ($indicatorCount > 3) {
                $summary .= ' and '.($indicatorCount - 3).' more';
            }
        }

        return $summary ?: 'Classification completed';
    }

    public function getTopIndicators(int $limit = 3): array
    {
        $indicators = $this->reasoning['indicators'] ?? [];

        if (is_array($indicators)) {
            return array_slice($indicators, 0, $limit);
        }

        return [];
    }

    public function hasReasoning(): bool
    {
        return ! empty($this->reasoning);
    }

    public function getReasoningByCategory(): array
    {
        $reasoning = $this->reasoning;

        return [
            'website' => $reasoning['website'] ?? [],
            'social_media' => $reasoning['social_media'] ?? [],
            'content' => $reasoning['content'] ?? [],
            'technical' => $reasoning['technical'] ?? [],
            'business_model' => $reasoning['business_model'] ?? [],
        ];
    }

    public function wasClassifiedBy(User $user): bool
    {
        return $this->classified_by !== null && $this->classified_by === (string) $user->id;
    }

    public function getAgeInDays(): int
    {
        return (int) $this->created_at->diffInDays(now());
    }

    public function isRecent(int $days = 30): bool
    {
        return $this->created_at->gte(now()->subDays($days));
    }

    // Static factory methods
    public static function createAutomated(
        Company $company,
        CompanyClassification $classification,
        float $confidence,
        array $reasoning = []
    ): self {
        return self::query()->create([
            'company_id' => $company->id,
            'classification' => $classification,
            'confidence_score' => $confidence,
            'method' => ClassificationMethod::AUTOMATED,
            'reasoning' => $reasoning,
        ]);
    }

    public static function createManual(
        Company $company,
        CompanyClassification $classification,
        float $confidence,
        User $user,
        array $reasoning = []
    ): self {
        return self::query()->create([
            'company_id' => $company->id,
            'classification' => $classification,
            'confidence_score' => $confidence,
            'method' => ClassificationMethod::MANUAL,
            'reasoning' => $reasoning,
            'classified_by' => $user->id,
        ]);
    }

    public static function createAiVerified(
        Company $company,
        CompanyClassification $classification,
        float $confidence,
        array $reasoning = []
    ): self {
        return self::query()->create([
            'company_id' => $company->id,
            'classification' => $classification,
            'confidence_score' => $confidence,
            'method' => ClassificationMethod::AI_VERIFIED,
            'reasoning' => $reasoning,
        ]);
    }
}
