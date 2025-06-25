<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-start justify-between">
        <div class="flex items-start space-x-4">
            <!-- Company Avatar -->
            <div class="w-16 h-16 bg-zinc-100 dark:bg-zinc-800 rounded-xl flex items-center justify-center flex-shrink-0">
                <flux:icon.building-office class="w-8 h-8 text-zinc-600 dark:text-zinc-400" />
            </div>

            <!-- Company Info -->
            <div class="flex-1">
                <flux:heading size="2xl" class="mb-2">{{ $company->name }}</flux:heading>

                <div class="flex items-center space-x-4 mb-3">
                    @if($company->website)
                        <flux:text class="text-zinc-600 dark:text-zinc-400">
                            <a
                                href="{{ $company->website }}"
                                target="_blank"
                                class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors flex items-center space-x-1"
                            >
                                <flux:icon.globe-alt class="w-4 h-4" />
                                <span>{{ $company->domain ?? $company->website }}</span>
                                <flux:icon.arrow-top-right-on-square class="w-3 h-3" />
                            </a>
                        </flux:text>
                    @endif

                    <flux:text class="text-zinc-500 text-sm">
                        Added {{ $company->created_at->diffForHumans() }}
                    </flux:text>
                </div>

                <!-- Status and Classification Badges -->
                <div class="flex items-center space-x-3">
                    <flux:badge color="{{ $company->status->color() }}">
                        {{ $company->status->label() }}
                    </flux:badge>

                    @if($company->classification)
                        <flux:badge color="{{ $company->classification->color() }}">
                            {{ $company->classification->label() }}
                        </flux:badge>
                    @else
                        <flux:badge color="gray">
                            Not Classified
                        </flux:badge>
                    @endif

                    @if($company->confidence_score)
                        <flux:badge
                            color="{{ $this->confidenceLevel === 'high' ? 'green' : ($this->confidenceLevel === 'medium' ? 'yellow' : 'red') }}"
                        >
                            {{ number_format($company->confidence_score * 100, 1) }}% Confidence
                        </flux:badge>
                    @endif
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center space-x-3">
            <flux:button
                wire:click="refreshCompany"
                variant="ghost"
                icon="arrow-path"
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove>Refresh</span>
                <span wire:loading>Refreshing...</span>
            </flux:button>

            @if($company->status !== \App\Enums\CompanyStatus::PROCESSING)
                <flux:button
                    wire:click="analyzeCompany"
                    variant="primary"
                    icon="cog"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove>Re-analyze</span>
                    <span wire:loading>Starting...</span>
                </flux:button>
            @endif

            <flux:button
                href="{{ route('companies.index') }}"
                variant="ghost"
                icon="arrow-left"
            >
                Back to List
            </flux:button>
        </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="border-b border-zinc-200 dark:border-zinc-700">
        <nav class="flex space-x-8">
            <button
                wire:click="setTab('overview')"
                class="py-2 px-1 border-b-2 font-medium text-sm transition-colors {{ $activeTab === 'overview' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100' }}"
            >
                Overview
            </button>
            <button
                wire:click="setTab('analysis')"
                class="py-2 px-1 border-b-2 font-medium text-sm transition-colors {{ $activeTab === 'analysis' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100' }}"
            >
                Analysis Data
            </button>
            <button
                wire:click="setTab('history')"
                class="py-2 px-1 border-b-2 font-medium text-sm transition-colors {{ $activeTab === 'history' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100' }}"
            >
                Classification History
            </button>
            <button
                wire:click="setTab('technical')"
                class="py-2 px-1 border-b-2 font-medium text-sm transition-colors {{ $activeTab === 'technical' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100' }}"
            >
                Technical Details
            </button>
        </nav>
    </div>

    <!-- Tab Content -->
    <div class="mt-6">
        <!-- Overview Tab -->
        @if($activeTab === 'overview')
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Classification Card -->
                <div class="lg:col-span-2">
                    <flux:card class="space-y-6">
                        <flux:heading size="lg">Classification Summary</flux:heading>

                        @if($company->classification)
                            <!-- Current Classification -->
                            <div class="flex items-center space-x-4 p-4 rounded-lg {{ $company->classification === \App\Enums\CompanyClassification::B2B ? 'bg-blue-50 dark:bg-blue-950/30' : ($company->classification === \App\Enums\CompanyClassification::B2C ? 'bg-green-50 dark:bg-green-950/30' : 'bg-purple-50 dark:bg-purple-950/30') }}">
                                <div class="w-12 h-12 rounded-full {{ $company->classification === \App\Enums\CompanyClassification::B2B ? 'bg-blue-500' : ($company->classification === \App\Enums\CompanyClassification::B2C ? 'bg-green-500' : 'bg-purple-500') }} flex items-center justify-center">
                                    <flux:icon.building-office class="w-6 h-6 text-white" />
                                </div>
                                <div class="flex-1">
                                    <flux:heading size="lg" class="{{ $company->classification === \App\Enums\CompanyClassification::B2B ? 'text-blue-900 dark:text-blue-100' : ($company->classification === \App\Enums\CompanyClassification::B2C ? 'text-green-900 dark:text-green-100' : 'text-purple-900 dark:text-purple-100') }}">
                                        {{ $company->classification->label() }} Company
                                    </flux:heading>
                                    <flux:text class="{{ $company->classification === \App\Enums\CompanyClassification::B2B ? 'text-blue-700 dark:text-blue-300' : ($company->classification === \App\Enums\CompanyClassification::B2C ? 'text-green-700 dark:text-green-300' : 'text-purple-700 dark:text-purple-300') }}">
                                        {{ $company->classification->description() }}
                                    </flux:text>
                                </div>
                                @if($company->confidence_score)
                                    <div class="text-right">
                                        <div class="text-2xl font-bold {{ $company->classification === \App\Enums\CompanyClassification::B2B ? 'text-blue-600' : ($company->classification === \App\Enums\CompanyClassification::B2C ? 'text-green-600' : 'text-purple-600') }}">
                                            {{ number_format($company->confidence_score * 100, 1) }}%
                                        </div>
                                        <flux:text size="sm" class="{{ $company->classification === \App\Enums\CompanyClassification::B2B ? 'text-blue-600' : ($company->classification === \App\Enums\CompanyClassification::B2C ? 'text-green-600' : 'text-purple-600') }}">
                                            Confidence
                                        </flux:text>
                                    </div>
                                @endif
                            </div>

                            <!-- Latest Analysis Reasoning -->
                            @if($this->latestAnalysis && isset($this->latestAnalysis->processed_data['reasoning']))
                                <div class="space-y-3">
                                    <flux:heading size="base">Key Indicators</flux:heading>
                                    <div class="prose prose-sm max-w-none text-zinc-600 dark:text-zinc-400">
                                        {{ $this->latestAnalysis->processed_data['reasoning'] }}
                                    </div>
                                </div>
                            @endif
                        @else
                            <!-- No Classification Yet -->
                            <div class="text-center py-8">
                                <flux:icon.question-mark-circle class="w-16 h-16 text-zinc-400 mx-auto mb-4" />
                                <flux:heading size="lg" class="mb-2">Classification Pending</flux:heading>
                                <flux:text class="text-zinc-600 dark:text-zinc-400 mb-4">
                                    This company hasn't been classified yet.
                                </flux:text>
                                @if($company->status !== \App\Enums\CompanyStatus::PROCESSING)
                                    <flux:button
                                        wire:click="analyzeCompany"
                                        variant="primary"
                                        icon="cog"
                                    >
                                        Start Analysis
                                    </flux:button>
                                @endif
                            </div>
                        @endif
                    </flux:card>
                </div>

                <!-- Side Stats -->
                <div class="space-y-6">
                    <!-- Analysis Stats -->
                    <flux:card class="space-y-4">
                        <flux:heading size="base">Analysis Stats</flux:heading>

                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <flux:text size="sm">Total Analyses</flux:text>
                                <flux:text size="sm" class="font-medium">{{ $company->analyses->count() }}</flux:text>
                            </div>
                            <div class="flex items-center justify-between">
                                <flux:text size="sm">Classifications</flux:text>
                                <flux:text size="sm" class="font-medium">{{ $company->classificationResults->count() }}</flux:text>
                            </div>
                            <div class="flex items-center justify-between">
                                <flux:text size="sm">Last Analyzed</flux:text>
                                <flux:text size="sm" class="font-medium">
                                    {{ $company->last_analyzed_at?->diffForHumans() ?? 'Never' }}
                                </flux:text>
                            </div>
                        </div>
                    </flux:card>

                    <!-- Data Sources -->
                    <flux:card class="space-y-4">
                        <flux:heading size="base">Data Sources</flux:heading>

                        <div class="space-y-3">
                            @php
                                $breakdown = $this->analysisBreakdown;
                            @endphp

                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <flux:icon.globe-alt class="w-4 h-4 text-zinc-500" />
                                    <flux:text size="sm">Website</flux:text>
                                </div>
                                <flux:badge color="{{ $breakdown['website'] ? 'green' : 'gray' }}" size="sm">
                                    {{ $breakdown['website'] ? 'Analyzed' : 'Pending' }}
                                </flux:badge>
                            </div>

                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <flux:icon.users class="w-4 h-4 text-zinc-500" />
                                    <flux:text size="sm">Social Media</flux:text>
                                </div>
                                <flux:badge color="{{ $breakdown['social_media'] ? 'green' : 'gray' }}" size="sm">
                                    {{ $breakdown['social_media'] ? 'Analyzed' : 'Pending' }}
                                </flux:badge>
                            </div>

                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <flux:icon.map-pin class="w-4 h-4 text-zinc-500" />
                                    <flux:text size="sm">Google Business</flux:text>
                                </div>
                                <flux:badge color="{{ $breakdown['google_business'] ? 'green' : 'gray' }}" size="sm">
                                    {{ $breakdown['google_business'] ? 'Analyzed' : 'Pending' }}
                                </flux:badge>
                            </div>
                        </div>
                    </flux:card>
                </div>
            </div>
        @endif

        <!-- Analysis Data Tab -->
        @if($activeTab === 'analysis')
            <div class="space-y-6">
                @forelse($company->analyses as $analysis)
                    <flux:card class="space-y-4">
                        <div class="flex items-center justify-between">
                            <flux:heading size="lg" class="capitalize">
                                {{ str_replace('_', ' ', $analysis->data_source) }} Analysis
                            </flux:heading>
                            <div class="flex items-center space-x-2">
                                <flux:badge color="blue" size="sm">
                                    {{ number_format($analysis->source_confidence * 100, 1) }}% Confidence
                                </flux:badge>
                                <flux:text size="sm" class="text-zinc-500">
                                    {{ $analysis->scraped_at->diffForHumans() }}
                                </flux:text>
                            </div>
                        </div>

                        @if(isset($analysis->indicators) && !empty($analysis->indicators))
                            <div>
                                <flux:heading size="base" class="mb-2">Key Indicators</flux:heading>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($analysis->indicators as $indicator)
                                        <flux:badge color="gray" size="sm">
                                            {{ is_string($indicator) ? $indicator : json_encode($indicator) }}
                                        </flux:badge>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if(isset($analysis->processed_data) && !empty($analysis->processed_data))
                            <div>
                                <flux:heading size="base" class="mb-2">Processed Data</flux:heading>
                                <div class="bg-zinc-50 dark:bg-zinc-900/50 p-4 rounded-lg">
                                    <pre class="text-sm text-zinc-600 dark:text-zinc-400 whitespace-pre-wrap">{{ json_encode($analysis->processed_data, JSON_PRETTY_PRINT) }}</pre>
                                </div>
                            </div>
                        @endif
                    </flux:card>
                @empty
                    <div class="text-center py-12">
                        <flux:icon.document-magnifying-glass class="w-16 h-16 text-zinc-400 mx-auto mb-4" />
                        <flux:heading size="lg" class="mb-2">No Analysis Data</flux:heading>
                        <flux:text class="text-zinc-600 dark:text-zinc-400">
                            No analysis data available for this company yet.
                        </flux:text>
                    </div>
                @endforelse
            </div>
        @endif

        <!-- Classification History Tab -->
        @if($activeTab === 'history')
            <div class="space-y-6">
                @forelse($this->classificationHistory as $result)
                    <flux:card class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <flux:badge color="{{ $result->classification->color() }}">
                                    {{ $result->classification->label() }}
                                </flux:badge>
                                <flux:text class="font-medium">
                                    {{ number_format($result->confidence_score * 100, 1) }}% Confidence
                                </flux:text>
                                <flux:badge color="gray" size="sm">
                                    {{ ucfirst($result->method->value) }}
                                </flux:badge>
                            </div>
                            <flux:text size="sm" class="text-zinc-500">
                                {{ $result->created_at->diffForHumans() }}
                            </flux:text>
                        </div>

                        @if(isset($result->reasoning) && !empty($result->reasoning))
                            <div>
                                <flux:heading size="base" class="mb-2">Reasoning</flux:heading>
                                <div class="prose prose-sm max-w-none text-zinc-600 dark:text-zinc-400">
                                    @if(is_array($result->reasoning))
                                        <ul class="list-disc pl-5 space-y-1">
                                            @foreach($result->reasoning as $reason)
                                                <li class="font-medium">{{ is_array($reason) ? reset($reason) : $reason }}</li>
                                            @endforeach
                                        </ul>
                                    @else
                                        {{ $result->reasoning }}
                                    @endif
                                </div>
                            </div>
                        @endif

                        @if($result->user)
                            <div class="flex items-center space-x-2 text-sm text-zinc-500">
                                <flux:icon.user class="w-4 h-4" />
                                <span>Classified by {{ $result->user->name }}</span>
                            </div>
                        @endif
                    </flux:card>
                @empty
                    <div class="text-center py-12">
                        <flux:icon.clock class="w-16 h-16 text-zinc-400 mx-auto mb-4" />
                        <flux:heading size="lg" class="mb-2">No Classification History</flux:heading>
                        <flux:text class="text-zinc-600 dark:text-zinc-400">
                            No classification history available for this company yet.
                        </flux:text>
                    </div>
                @endforelse
            </div>
        @endif

        <!-- Technical Details Tab -->
        @if($activeTab === 'technical')
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Company Information -->
                <flux:card class="space-y-4">
                    <flux:heading size="lg">Company Information</flux:heading>

                    <div class="space-y-3">
                        <div>
                            <flux:text size="sm" class="text-zinc-500">ID</flux:text>
                            <flux:text class="font-mono">{{ $company->id }}</flux:text>
                        </div>
                        <div>
                            <flux:text size="sm" class="text-zinc-500">Domain</flux:text>
                            <flux:text>{{ $company->domain ?? 'N/A' }}</flux:text>
                        </div>
                        <div>
                            <flux:text size="sm" class="text-zinc-500">Created</flux:text>
                            <flux:text>{{ $company->created_at->format('M j, Y g:i A') }}</flux:text>
                        </div>
                        <div>
                            <flux:text size="sm" class="text-zinc-500">Last Updated</flux:text>
                            <flux:text>{{ $company->updated_at->format('M j, Y g:i A') }}</flux:text>
                        </div>
                    </div>
                </flux:card>

                <!-- Recent Scraping Jobs -->
                <flux:card class="space-y-4">
                    <flux:heading size="lg">Recent Scraping Jobs</flux:heading>

                    <div class="space-y-3">
                        @forelse($this->recentScrapingJobs as $job)
                            <div class="flex items-center justify-between p-3 rounded-lg border border-zinc-200 dark:border-zinc-700">
                                <div>
                                    <flux:text class="font-medium capitalize">
                                        {{ str_replace('_', ' ', $job->job_type) }}
                                    </flux:text>
                                    <flux:text size="sm" class="text-zinc-500">
                                        {{ $job->created_at->diffForHumans() }}
                                    </flux:text>
                                </div>
                                <flux:badge color="{{ $job->status->color() }}" size="sm">
                                    {{ $job->status->label() }}
                                </flux:badge>
                            </div>
                        @empty
                            <flux:text class="text-zinc-500">No scraping jobs yet</flux:text>
                        @endforelse
                    </div>
                </flux:card>
            </div>
        @endif
    </div>
</div>
