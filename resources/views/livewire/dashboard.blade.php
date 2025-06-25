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
        <flux:card class="space-y-2">
            <div class="flex items-center justify-between">
                <flux:heading size="sm" class="text-zinc-600 dark:text-zinc-400">Total Companies</flux:heading>
                <flux:icon.building-office class="w-5 h-5 text-zinc-400" />
            </div>
            <flux:heading size="2xl" class="text-zinc-900 dark:text-zinc-100">
                {{ number_format($this->totalCompanies) }}
            </flux:heading>
        </flux:card>
        
        <flux:card class="space-y-2">
            <div class="flex items-center justify-between">
                <flux:heading size="sm" class="text-zinc-600 dark:text-zinc-400">Classified</flux:heading>
                <flux:icon.check-circle class="w-5 h-5 text-green-500" />
            </div>
            <flux:heading size="2xl" class="text-green-600 dark:text-green-400">
                {{ number_format($this->classifiedCount) }}
            </flux:heading>
            <flux:text size="sm" class="text-zinc-500">
                {{ $this->totalCompanies > 0 ? number_format(($this->classifiedCount / $this->totalCompanies) * 100, 1) : 0 }}% of total
            </flux:text>
        </flux:card>
        
        <flux:card class="space-y-2">
            <div class="flex items-center justify-between">
                <flux:heading size="sm" class="text-zinc-600 dark:text-zinc-400">Processing</flux:heading>
                <flux:icon.cog class="w-5 h-5 text-yellow-500 animate-spin" />
            </div>
            <flux:heading size="2xl" class="text-yellow-600 dark:text-yellow-400">
                {{ number_format($this->processingCount) }}
            </flux:heading>
            <flux:text size="sm" class="text-zinc-500">
                Active analyses
            </flux:text>
        </flux:card>
        
        <flux:card class="space-y-2">
            <div class="flex items-center justify-between">
                <flux:heading size="sm" class="text-zinc-600 dark:text-zinc-400">Accuracy Rate</flux:heading>
                <flux:icon.chart-bar class="w-5 h-5 text-blue-500" />
            </div>
            <flux:heading size="2xl" class="text-blue-600 dark:text-blue-400">
                {{ number_format($this->accuracyRate, 1) }}%
            </flux:heading>
            <flux:text size="sm" class="text-zinc-500">
                Classification accuracy
            </flux:text>
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

    <!-- Quick Actions -->
    <flux:card class="space-y-6">
        <flux:heading size="lg">Quick Actions</flux:heading>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:button 
                href="{{ route('companies.create') }}" 
                variant="outline" 
                icon="plus-circle"
                class="justify-start h-auto p-4"
            >
                <div class="text-left">
                    <div class="font-medium">Add Company</div>
                    <div class="text-sm text-zinc-500">Start analyzing a new company</div>
                </div>
            </flux:button>
            
            <flux:button 
                href="{{ route('companies.pending') }}" 
                variant="outline" 
                icon="clock"
                class="justify-start h-auto p-4"
            >
                <div class="text-left">
                    <div class="font-medium">Review Pending</div>
                    <div class="text-sm text-zinc-500">{{ $this->pendingCount }} companies waiting</div>
                </div>
            </flux:button>
            
            <flux:button 
                href="{{ route('companies.processing') }}" 
                variant="outline" 
                icon="cog"
                class="justify-start h-auto p-4"
            >
                <div class="text-left">
                    <div class="font-medium">Monitor Processing</div>
                    <div class="text-sm text-zinc-500">{{ $this->processingCount }} currently analyzing</div>
                </div>
            </flux:button>
        </div>
    </flux:card>
</div> 