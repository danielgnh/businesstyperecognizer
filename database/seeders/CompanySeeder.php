<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CompanyClassification;
use App\Models\ClassificationResult;
use App\Models\Company;
use App\Models\CompanyAnalysis;
use App\Models\ScrapingJob;
use App\Models\User;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Skip if data already exists
        if (Company::count() > 0) {
            $this->command->info('Companies already exist, skipping seeding...');

            return;
        }

        // Create admin user for manual classifications
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => bcrypt('password'),
            ]
        );

        // Create B2B companies with full analysis
        $this->createB2BCompanies($adminUser);

        // Create B2C companies with full analysis
        $this->createB2CCompanies($adminUser);

        // Create hybrid companies
        $this->createHybridCompanies($adminUser);

        // Create pending companies (not yet analyzed)
        $this->createPendingCompanies();

        // Create failed companies
        $this->createFailedCompanies();

        $this->command->info('Created companies with analysis data:');
        $this->command->info('- Total Companies: '.Company::count());
        $this->command->info('- B2B Companies: '.Company::where('classification', CompanyClassification::B2B)->count());
        $this->command->info('- B2C Companies: '.Company::where('classification', CompanyClassification::B2C)->count());
        $this->command->info('- Hybrid Companies: '.Company::where('classification', CompanyClassification::HYBRID)->count());
        $this->command->info('- Pending Companies: '.Company::whereNull('classification')->count());
        $this->command->info('- Total Analyses: '.CompanyAnalysis::count());
        $this->command->info('- Total Classification Results: '.ClassificationResult::count());
        $this->command->info('- Total Scraping Jobs: '.ScrapingJob::count());
    }

    private function createB2BCompanies(User $adminUser): void
    {
        $b2bCompanies = [
            ['name' => 'Salesforce', 'domain' => 'salesforce.com'],
            ['name' => 'HubSpot', 'domain' => 'hubspot.com'],
            ['name' => 'Slack', 'domain' => 'slack.com'],
            ['name' => 'Atlassian', 'domain' => 'atlassian.com'],
            ['name' => 'Twilio', 'domain' => 'twilio.com'],
        ];

        foreach ($b2bCompanies as $companyData) {
            $company = Company::factory()->b2b()->create([
                'name' => $companyData['name'],
                'domain' => $companyData['domain'],
                'website' => "https://{$companyData['domain']}",
            ]);

            // Create website analysis
            CompanyAnalysis::createWebsiteAnalysis(
                $company,
                [
                    'title' => "{$company->name} - Enterprise Software Solution",
                    'meta_description' => 'Leading enterprise software for businesses',
                    'content' => 'Enterprise features, API documentation, case studies',
                ],
                [
                    'pricing_model' => 'enterprise',
                    'target_audience' => 'businesses',
                    'content_type' => 'professional',
                ],
                ['enterprise_pricing', 'api_documentation', 'case_studies', 'linkedin_focus'],
                0.92,
                0.8
            );

            // Create social media analysis
            CompanyAnalysis::createSocialMediaAnalysis(
                $company,
                [
                    'linkedin_followers' => rand(10000, 100000),
                    'twitter_followers' => rand(5000, 50000),
                    'facebook_followers' => rand(1000, 10000),
                ],
                [
                    'primary_platform' => 'linkedin',
                    'content_style' => 'professional',
                    'engagement_type' => 'business_focused',
                ],
                ['linkedin_focus', 'professional_content', 'business_engagement'],
                0.85,
                0.3
            );

            // Create classification result
            ClassificationResult::createAutomated(
                $company,
                CompanyClassification::B2B,
                (float) $company->confidence_score,
                [
                    'summary' => 'Strong B2B indicators across all data sources',
                    'indicators' => ['enterprise_pricing', 'api_documentation', 'case_studies', 'linkedin_focus'],
                    'website' => ['enterprise_pricing', 'api_documentation'],
                    'social_media' => ['linkedin_focus', 'professional_content'],
                    'confidence_factors' => [
                        'pricing_model' => 0.95,
                        'content_analysis' => 0.90,
                        'social_presence' => 0.85,
                    ],
                ]
            );

            // Create some scraping jobs
            ScrapingJob::createJobsForCompany($company);
        }
    }

    private function createB2CCompanies(User $adminUser): void
    {
        $b2cCompanies = [
            ['name' => 'Netflix', 'domain' => 'netflix.com'],
            ['name' => 'Spotify', 'domain' => 'spotify.com'],
            ['name' => 'Uber', 'domain' => 'uber.com'],
            ['name' => 'Airbnb', 'domain' => 'airbnb.com'],
            ['name' => 'Amazon', 'domain' => 'amazon.com'],
        ];

        foreach ($b2cCompanies as $companyData) {
            $company = Company::factory()->b2c()->create([
                'name' => $companyData['name'],
                'domain' => $companyData['domain'],
                'website' => "https://{$companyData['domain']}",
            ]);

            // Create website analysis
            CompanyAnalysis::createWebsiteAnalysis(
                $company,
                [
                    'title' => "{$company->name} - Consumer Service",
                    'meta_description' => 'Consumer-focused service for everyday users',
                    'content' => 'Consumer features, reviews, easy signup',
                ],
                [
                    'pricing_model' => 'consumer',
                    'target_audience' => 'consumers',
                    'content_type' => 'marketing',
                ],
                ['ecommerce', 'consumer_reviews', 'mobile_first', 'social_media_heavy'],
                0.88,
                0.8
            );

            // Create social media analysis
            CompanyAnalysis::createSocialMediaAnalysis(
                $company,
                [
                    'instagram_followers' => rand(100000, 1000000),
                    'facebook_followers' => rand(50000, 500000),
                    'twitter_followers' => rand(10000, 100000),
                ],
                [
                    'primary_platform' => 'instagram',
                    'content_style' => 'marketing',
                    'engagement_type' => 'consumer_focused',
                ],
                ['social_media_heavy', 'emotional_marketing', 'consumer_support'],
                0.82,
                0.3
            );

            // Create classification result
            ClassificationResult::createAutomated(
                $company,
                CompanyClassification::B2C,
                (float) $company->confidence_score,
                [
                    'summary' => 'Clear B2C indicators with consumer-focused approach',
                    'indicators' => ['ecommerce', 'consumer_reviews', 'mobile_first', 'social_media_heavy'],
                    'website' => ['ecommerce', 'consumer_reviews'],
                    'social_media' => ['social_media_heavy', 'emotional_marketing'],
                    'confidence_factors' => [
                        'pricing_model' => 0.90,
                        'content_analysis' => 0.85,
                        'social_presence' => 0.90,
                    ],
                ]
            );

            // Create scraping jobs
            ScrapingJob::createJobsForCompany($company);
        }
    }

    private function createHybridCompanies(User $adminUser): void
    {
        $hybridCompanies = [
            ['name' => 'Microsoft', 'domain' => 'microsoft.com'],
            ['name' => 'Adobe', 'domain' => 'adobe.com'],
            ['name' => 'Apple', 'domain' => 'apple.com'],
        ];

        foreach ($hybridCompanies as $companyData) {
            $company = Company::factory()->hybrid()->create([
                'name' => $companyData['name'],
                'domain' => $companyData['domain'],
                'website' => "https://{$companyData['domain']}",
            ]);

            // Create website analysis showing mixed indicators
            CompanyAnalysis::createWebsiteAnalysis(
                $company,
                [
                    'title' => "{$company->name} - Enterprise & Consumer Solutions",
                    'meta_description' => 'Solutions for both businesses and consumers',
                    'content' => 'Enterprise features, consumer products, mixed audience',
                ],
                [
                    'pricing_model' => 'mixed',
                    'target_audience' => 'mixed',
                    'content_type' => 'mixed',
                ],
                ['enterprise_pricing', 'consumer_reviews', 'api_documentation', 'ecommerce'],
                0.75,
                0.8
            );

            // Create classification result manually verified
            ClassificationResult::createManual(
                $company,
                CompanyClassification::HYBRID,
                (float) $company->confidence_score,
                $adminUser,
                [
                    'summary' => 'Mixed B2B and B2C indicators requiring manual verification',
                    'indicators' => ['enterprise_pricing', 'consumer_reviews', 'api_documentation', 'ecommerce'],
                    'reasoning' => 'Company serves both enterprise and consumer markets',
                    'confidence_factors' => [
                        'mixed_pricing' => 0.70,
                        'dual_audience' => 0.75,
                        'complex_offering' => 0.80,
                    ],
                ]
            );

            ScrapingJob::createJobsForCompany($company);
        }
    }

    private function createPendingCompanies(): void
    {
        Company::factory()->pending()->count(10)->create();
    }

    private function createFailedCompanies(): void
    {
        $failedCompanies = Company::factory()->failed()->count(3)->create();

        foreach ($failedCompanies as $company) {
            ScrapingJob::createWebsiteJob($company)->markAsFailed('Website unreachable');
            ScrapingJob::createSocialMediaJob($company)->markAsFailed('Social media profiles not found');
        }
    }
}
