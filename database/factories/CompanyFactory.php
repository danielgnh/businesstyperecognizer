<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CompanyClassification;
use App\Enums\CompanyStatus;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $companyNames = [
            'TechCorp Solutions',
            'DataFlow Analytics',
            'CloudVision Systems',
            'NextGen Innovations',
            'Digital Dynamics',
            'SmartScale Technologies',
            'FutureTech Industries',
            'StreamLine Software',
            'ByteForge Labs',
            'CodeCraft Studios',
            'Quantum Leap Systems',
            'InnovateTech Hub',
            'AgileCloud Solutions',
            'DataMine Corp',
            'TechPulse Networks',
        ];

        $domains = [
            'salesforce.com',
            'hubspot.com',
            'slack.com',
            'atlassian.com',
            'shopify.com',
            'stripe.com',
            'twilio.com',
            'zoom.us',
            'dropbox.com',
            'adobe.com',
            'microsoft.com',
            'oracle.com',
            'aws.amazon.com',
            'google.com',
            'apple.com',
        ];

        $companyName = $this->faker->randomElement($companyNames);
        $domain = $this->faker->unique()->domainName();
        $website = "https://{$domain}";

        return [
            'name' => $companyName,
            'website' => $website,
            'domain' => $domain,
            'status' => $this->faker->randomElement(CompanyStatus::cases()),
            'classification' => $this->faker->optional(0.7)->randomElement(CompanyClassification::cases()),
            'confidence_score' => $this->faker->optional(0.7)->randomFloat(2, 0.5, 1.0),
            'last_analyzed_at' => $this->faker->optional(0.6)->dateTimeBetween('-30 days', 'now'),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CompanyStatus::PENDING,
            'classification' => null,
            'confidence_score' => null,
            'last_analyzed_at' => null,
        ]);
    }

    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CompanyStatus::PROCESSING,
            'classification' => null,
            'confidence_score' => null,
            'last_analyzed_at' => null,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CompanyStatus::COMPLETED,
            'classification' => $this->faker->randomElement(CompanyClassification::cases()),
            'confidence_score' => $this->faker->randomFloat(2, 0.7, 1.0),
            'last_analyzed_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CompanyStatus::FAILED,
            'classification' => null,
            'confidence_score' => null,
            'last_analyzed_at' => null,
        ]);
    }

    public function b2b(): static
    {
        return $this->state(fn (array $attributes) => [
            'classification' => CompanyClassification::B2B,
            'confidence_score' => $this->faker->randomFloat(2, 0.8, 1.0),
            'status' => CompanyStatus::COMPLETED,
            'last_analyzed_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
        ]);
    }

    public function b2c(): static
    {
        return $this->state(fn (array $attributes) => [
            'classification' => CompanyClassification::B2C,
            'confidence_score' => $this->faker->randomFloat(2, 0.8, 1.0),
            'status' => CompanyStatus::COMPLETED,
            'last_analyzed_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
        ]);
    }

    public function hybrid(): static
    {
        return $this->state(fn (array $attributes) => [
            'classification' => CompanyClassification::HYBRID,
            'confidence_score' => $this->faker->randomFloat(2, 0.6, 0.8),
            'status' => CompanyStatus::COMPLETED,
            'last_analyzed_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
        ]);
    }

    public function highConfidence(): static
    {
        return $this->state(fn (array $attributes) => [
            'confidence_score' => $this->faker->randomFloat(2, 0.9, 1.0),
        ]);
    }

    public function lowConfidence(): static
    {
        return $this->state(fn (array $attributes) => [
            'confidence_score' => $this->faker->randomFloat(2, 0.5, 0.7),
        ]);
    }

    public function withRealDomain(): static
    {
        $realDomains = [
            ['name' => 'Salesforce', 'domain' => 'salesforce.com'],
            ['name' => 'HubSpot', 'domain' => 'hubspot.com'],
            ['name' => 'Slack', 'domain' => 'slack.com'],
            ['name' => 'Shopify', 'domain' => 'shopify.com'],
            ['name' => 'Stripe', 'domain' => 'stripe.com'],
            ['name' => 'Amazon', 'domain' => 'amazon.com'],
            ['name' => 'Netflix', 'domain' => 'netflix.com'],
            ['name' => 'Spotify', 'domain' => 'spotify.com'],
            ['name' => 'Uber', 'domain' => 'uber.com'],
            ['name' => 'Airbnb', 'domain' => 'airbnb.com'],
        ];

        $company = $this->faker->randomElement($realDomains);

        return $this->state(fn (array $attributes) => [
            'name' => $company['name'],
            'domain' => $company['domain'],
            'website' => "https://{$company['domain']}",
        ]);
    }
}
