<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Dashboard</flux:heading>
        <flux:button
            href="{{ route('companies.create') }}"
            variant="primary"
            icon="plus"
        >
            Add Company
        </flux:button>
    </div>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <flux:card class="overflow-hidden min-w-[12rem]">
            <flux:text>{{__('Total Companies')}}</flux:text>
            <flux:heading size="xl" class="mt-2 tabular-nums">{{ number_format($this->totalCompanies) }}</flux:heading>
            <flux:chart class="-mx-8 -mb-8 h-[3rem]" :value="json_encode(data_get($this->dashboardStats, 'charts.total_companies', [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]))">
                <flux:chart.svg gutter="0">
                    <flux:chart.line class="text-sky-200 dark:text-sky-400" />
                    <flux:chart.area class="text-sky-100 dark:text-sky-400/30" />
                </flux:chart.svg>
            </flux:chart>
        </flux:card>

        <flux:card class="overflow-hidden min-w-[12rem]">
            <flux:text>{{__('Classified')}}</flux:text>
            <flux:heading size="xl" class="mt-2 tabular-nums text-green-600 dark:text-green-400">{{ number_format($this->classifiedCount) }}</flux:heading>
            <flux:chart class="-mx-8 -mb-8 h-[3rem]" :value="json_encode(data_get($this->dashboardStats, 'charts.classified', [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]))">
                <flux:chart.svg gutter="0">
                    <flux:chart.line class="text-green-200 dark:text-green-400" />
                    <flux:chart.area class="text-green-100 dark:text-green-400/30" />
                </flux:chart.svg>
            </flux:chart>
        </flux:card>

        <flux:card class="overflow-hidden min-w-[12rem]">
            <flux:text>{{__('Processing')}}</flux:text>
            <flux:heading size="xl" class="mt-2 tabular-nums text-yellow-600 dark:text-yellow-400">{{ number_format($this->processingCount) }}</flux:heading>
            <flux:chart class="-mx-8 -mb-8 h-[3rem]" :value="json_encode(data_get($this->dashboardStats, 'charts.processing', [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]))">
                <flux:chart.svg gutter="0">
                    <flux:chart.line class="text-yellow-200 dark:text-yellow-400" />
                    <flux:chart.area class="text-yellow-100 dark:text-yellow-400/30" />
                </flux:chart.svg>
            </flux:chart>
        </flux:card>

        <flux:card class="overflow-hidden min-w-[12rem]">
            <flux:text>{{__('Accuracy Rate')}}</flux:text>
            <flux:heading size="xl" class="mt-2 tabular-nums text-blue-600 dark:text-blue-400">{{ number_format($this->accuracyRate, 1) }}%</flux:heading>
            <flux:chart class="-mx-8 -mb-8 h-[3rem]" :value="json_encode(data_get($this->dashboardStats, 'charts.accuracy', [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]))">
                <flux:chart.svg gutter="0">
                    <flux:chart.line class="text-blue-200 dark:text-blue-400" />
                    <flux:chart.area class="text-blue-100 dark:text-blue-400/30" />
                </flux:chart.svg>
            </flux:chart>
        </flux:card>
    </div>

    <!-- Classification Breakdown -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Classification Distribution -->
        <flux:card class="space-y-6">
            <flux:heading size="lg">Classification Distribution</flux:heading>

            <div class="space-y-4">
                @php
                    $breakdown = $this->classificationBreakdown;
                    $total = array_sum($breakdown);
                @endphp

                @foreach(['b2b' => 'B2B', 'b2c' => 'B2C', 'hybrid' => 'Hybrid', 'unknown' => 'Unknown'] as $key => $label)
                    @php
                        $count = $breakdown[$key];
                        $percentage = $total > 0 ? ($count / $total) * 100 : 0;
                        $color = match($key) {
                            'b2b' => 'blue',
                            'b2c' => 'green',
                            'hybrid' => 'purple',
                            'unknown' => 'gray'
                        };
                    @endphp

                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <flux:badge
                                color="{{ $color }}"
                                size="sm"
                            >
                                {{ $label }}
                            </flux:badge>
                            <flux:text>{{ number_format($count) }} companies</flux:text>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="w-20 bg-zinc-200 dark:bg-zinc-700 rounded-full h-2">
                                <div
                                    class="bg-{{ $color }}-500 h-2 rounded-full transition-all duration-300"
                                    style="width: {{ $percentage }}%"
                                ></div>
                            </div>
                            <flux:text size="sm" class="text-zinc-500 w-12 text-right">
                                {{ number_format($percentage, 1) }}%
                            </flux:text>
                        </div>
                    </div>
                @endforeach
            </div>
        </flux:card>

        <!-- Recent Activity -->
        <flux:card class="space-y-6">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">Recent Companies</flux:heading>
                <flux:button
                    href="{{ route('companies.index') }}"
                    variant="ghost"
                    size="sm"
                    icon-trailing="arrow-right"
                >
                    View All
                </flux:button>
            </div>

            <div class="space-y-4">
                @forelse($this->recentCompanies as $company)
                    <div class="flex items-center justify-between p-3 rounded-lg border border-zinc-200 dark:border-zinc-700">
                        <div class="flex-1">
                            <flux:heading size="sm" class="mb-1">
                                <a
                                    href="{{ route('companies.show', $company) }}"
                                    class="hover:text-blue-600 dark:hover:text-blue-400"
                                >
                                    {{ $company->name }}
                                </a>
                            </flux:heading>
                            <flux:text size="sm" class="text-zinc-500">
                                {{ $company->website ?? 'No website' }}
                            </flux:text>
                        </div>
                        <div class="flex items-center space-x-2">
                            @if($company->classification)
                                <flux:badge
                                    color="{{ $company->classification->color() }}"
                                    size="sm"
                                >
                                    {{ $company->classification->label() }}
                                </flux:badge>
                            @else
                                <flux:badge color="gray" size="sm">
                                    {{ $company->status->label() }}
                                </flux:badge>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8">
                        <flux:icon.building-office class="w-12 h-12 text-zinc-400 mx-auto mb-3" />
                        <flux:text class="text-zinc-500">No companies yet</flux:text>
                        <flux:button
                            href="{{ route('companies.create') }}"
                            variant="primary"
                            size="sm"
                            class="mt-3"
                        >
                            Add Your First Company
                        </flux:button>
                    </div>
                @endforelse
            </div>
        </flux:card>
    </div>
</div>
