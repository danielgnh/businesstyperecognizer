<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $company_id
 * @property string $data_source
 * @property array $raw_data
 * @property array $processed_data
 * @property array $indicators
 * @property float $source_weight
 * @property float $source_confidence
 * @property Carbon $scraped_at
 * @property-read Company $company
 */
class CompanyAnalysis extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'raw_data' => 'array',
            'processed_data' => 'array',
            'indicators' => 'array',
            'source_weight' => 'decimal:2',
            'source_confidence' => 'decimal:2',
            'scraped_at' => 'datetime',
        ];
    }

    // Boot method to automatically set scraped_at
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (CompanyAnalysis $analysis) {
            if ($analysis->scraped_at === null) {
                $analysis->scraped_at = now();
            }
        });
    }

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // Scopes
    public function scopeFromSource(Builder $query, string $source): Builder
    {
        return $query->where('data_source', $source);
    }

    public function scopeWebsite(Builder $query): Builder
    {
        return $query->where('data_source', 'website');
    }

    public function scopeSocialMedia(Builder $query): Builder
    {
        return $query->where('data_source', 'social_media');
    }

    public function scopeGoogleBusiness(Builder $query): Builder
    {
        return $query->where('data_source', 'google_business');
    }

    public function scopePartners(Builder $query): Builder
    {
        return $query->where('data_source', 'partners');
    }

    public function scopeWithHighConfidence(Builder $query, float $threshold = 0.7): Builder
    {
        return $query->where('source_confidence', '>=', $threshold);
    }

    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('scraped_at', '>=', now()->subDays($days));
    }

    // Helper Methods
    public function isWebsiteSource(): bool
    {
        return $this->data_source === 'website';
    }

    public function isSocialMediaSource(): bool
    {
        return $this->data_source === 'social_media';
    }

    public function isGoogleBusinessSource(): bool
    {
        return $this->data_source === 'google_business';
    }

    public function isPartnersSource(): bool
    {
        return $this->data_source === 'partners';
    }

    public function hasHighConfidence(float $threshold = 0.7): bool
    {
        return $this->source_confidence >= $threshold;
    }

    public function getConfidencePercentage(): float
    {
        return round($this->source_confidence * 100, 1);
    }

    public function getWeightedScore(): float
    {
        return $this->source_confidence * $this->source_weight;
    }

    public function hasIndicator(string $indicator): bool
    {
        return in_array($indicator, $this->indicators ?? []);
    }

    public function getB2BIndicators(): array
    {
        $b2bIndicators = [
            'case_studies',
            'whitepapers',
            'enterprise_pricing',
            'linkedin_focus',
            'desktop_dominant',
            'long_sales_cycle',
            'professional_content',
            'api_documentation',
            'partner_networks',
        ];

        return array_intersect($this->indicators ?? [], $b2bIndicators);
    }

    public function getB2CIndicators(): array
    {
        $b2cIndicators = [
            'ecommerce',
            'shopping_cart',
            'consumer_reviews',
            'social_media_heavy',
            'mobile_first',
            'quick_checkout',
            'emotional_marketing',
            'consumer_support',
        ];

        return array_intersect($this->indicators ?? [], $b2cIndicators);
    }

    public function getB2BScore(): float
    {
        $b2bIndicators = $this->getB2BIndicators();
        $totalIndicators = count($this->indicators ?? []);

        if ($totalIndicators === 0) {
            return 0.0;
        }

        return count($b2bIndicators) / $totalIndicators;
    }

    public function getB2CScore(): float
    {
        $b2cIndicators = $this->getB2CIndicators();
        $totalIndicators = count($this->indicators ?? []);

        if ($totalIndicators === 0) {
            return 0.0;
        }

        return count($b2cIndicators) / $totalIndicators;
    }

    public function isStale(int $days = 30): bool
    {
        return $this->scraped_at->lt(now()->subDays($days));
    }

    public static function createWebsiteAnalysis(
        Company $company,
        array $rawData,
        array $processedData,
        array $indicators,
        float $confidence = 0.8,
        float $weight = 0.8
    ): self {
        return self::query()->create([
            'company_id' => $company->id,
            'data_source' => 'website',
            'raw_data' => $rawData,
            'processed_data' => $processedData,
            'indicators' => $indicators,
            'source_confidence' => $confidence,
            'source_weight' => $weight,
        ]);
    }

    public static function createSocialMediaAnalysis(
        Company $company,
        array $rawData,
        array $processedData,
        array $indicators,
        float $confidence = 0.3,
        float $weight = 0.3
    ): self {
        return self::query()->create([
            'company_id' => $company->id,
            'data_source' => 'social_media',
            'raw_data' => $rawData,
            'processed_data' => $processedData,
            'indicators' => $indicators,
            'source_confidence' => $confidence,
            'source_weight' => $weight,
        ]);
    }

    public static function createGoogleBusinessAnalysis(
        Company $company,
        array $rawData,
        array $processedData,
        array $indicators,
        float $confidence = 0.6,
        float $weight = 0.6
    ): self {
        return self::query()->create([
            'company_id' => $company->id,
            'data_source' => 'google_business',
            'raw_data' => $rawData,
            'processed_data' => $processedData,
            'indicators' => $indicators,
            'source_confidence' => $confidence,
            'source_weight' => $weight,
        ]);
    }
}
