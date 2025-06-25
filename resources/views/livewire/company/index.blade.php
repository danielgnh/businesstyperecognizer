<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Companies</flux:heading>
            <flux:text class="text-zinc-600 dark:text-zinc-400 mt-1">
                Manage and analyze your company database
            </flux:text>
        </div>
        <flux:button
            href="{{ route('companies.create') }}"
            variant="primary"
            icon="plus"
        >
            Add Company
        </flux:button>
    </div>

    <!-- Filters and Search -->
    <flux:card class="space-y-4">
        <div class="flex flex-col lg:flex-row gap-4">
            <!-- Search -->
            <div class="flex-1">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search companies, websites, or domains..."
                    icon="magnifying-glass"
                    clearable
                />
            </div>

            <!-- Status Filter -->
            <flux:select
                wire:model.live="statusFilter"
                placeholder="All Statuses"
                class="w-full lg:w-48"
            >
                <flux:select.option value="">All Statuses</flux:select.option>
                @foreach($this->statusOptions as $value => $label)
                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>

            <!-- Classification Filter -->
            <flux:select
                wire:model.live="classificationFilter"
                placeholder="All Classifications"
                class="w-full lg:w-48"
            >
                <flux:select.option value="">All Classifications</flux:select.option>
                @foreach($this->classificationOptions as $value => $label)
                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>

            <!-- Clear Filters -->
            @if($this->hasFilters)
                <flux:button
                    wire:click="clearFilters"
                    variant="ghost"
                    icon="x-mark"
                >
                    Clear
                </flux:button>
            @endif
        </div>

        <!-- Bulk Actions -->
        @if(!empty($selectedCompanies))
            <div class="flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-950/30 rounded-lg border border-blue-200 dark:border-blue-800">
                <flux:text class="text-blue-700 dark:text-blue-300">
                    {{ count($selectedCompanies) }} companies selected
                </flux:text>
                <div class="flex items-center space-x-2">
                    <flux:button
                        wire:click="analyzeSelected"
                        variant="primary"
                        size="sm"
                        icon="cog"
                    >
                        Analyze Selected
                    </flux:button>
                    <flux:button
                        wire:click="deleteSelected"
                        variant="destructive"
                        size="sm"
                        icon="trash"
                        onclick="return confirm('Are you sure you want to delete the selected companies?')"
                    >
                        Delete Selected
                    </flux:button>
                </div>
            </div>
        @endif
    </flux:card>

    <!-- Companies Table -->
    <flux:card>
        @if($this->companies->count() > 0)
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>
                        <flux:checkbox
                            wire:model.live="selectAll"
                            :indeterminate="!empty($selectedCompanies) && count($selectedCompanies) < $this->companies->count()"
                        />
                    </flux:table.column>
                    <flux:table.column
                        sortable
                        wire:click="sortBy('name')"
                        class="cursor-pointer"
                    >
                        <div class="flex items-center space-x-1">
                            <span>Company</span>
                            @if($sortBy === 'name')
                                <flux:icon.chevron-up class="w-4 h-4 {{ $sortDirection === 'asc' ? '' : 'rotate-180' }}" />
                            @endif
                        </div>
                    </flux:table.column>
                    <flux:table.column>Classification</flux:table.column>
                    <flux:table.column>Confidence</flux:table.column>
                    <flux:table.column
                        sortable
                        wire:click="sortBy('status')"
                        class="cursor-pointer"
                    >
                        <div class="flex items-center space-x-1">
                            <span>Status</span>
                            @if($sortBy === 'status')
                                <flux:icon.chevron-up class="w-4 h-4 {{ $sortDirection === 'asc' ? '' : 'rotate-180' }}" />
                            @endif
                        </div>
                    </flux:table.column>
                    <flux:table.column
                        sortable
                        wire:click="sortBy('last_analyzed_at')"
                        class="cursor-pointer"
                    >
                        <div class="flex items-center space-x-1">
                            <span>Last Analyzed</span>
                            @if($sortBy === 'last_analyzed_at')
                                <flux:icon.chevron-up class="w-4 h-4 {{ $sortDirection === 'asc' ? '' : 'rotate-180' }}" />
                            @endif
                        </div>
                    </flux:table.column>
                    <flux:table.column>Actions</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach($this->companies as $company)
                        <flux:table.row>
                            <flux:table.cell>
                                <flux:checkbox
                                    wire:model.live="selectedCompanies"
                                    value="{{ $company->id }}"
                                />
                            </flux:table.cell>

                            <flux:table.cell>
                                <div class="flex items-center space-x-3">
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 bg-zinc-100 dark:bg-zinc-800 rounded-lg flex items-center justify-center">
                                            <flux:icon.building-office class="w-5 h-5 text-zinc-600 dark:text-zinc-400" />
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <flux:heading size="sm" class="mb-1">
                                            <a
                                                href="{{ route('companies.show', $company) }}"
                                                class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
                                            >
                                                {{ $company->name }}
                                            </a>
                                        </flux:heading>
                                        <flux:text size="sm" class="text-zinc-500 truncate">
                                            {{ $company->website ?? 'No website' }}
                                        </flux:text>
                                    </div>
                                </div>
                            </flux:table.cell>

                            <flux:table.cell>
                                @if($company->classification)
                                    <flux:badge
                                        color="{{ $company->classification->color() }}"
                                        size="sm"
                                    >
                                        {{ $company->classification->label() }}
                                    </flux:badge>
                                @else
                                    <flux:text size="sm" class="text-zinc-400">
                                        Not classified
                                    </flux:text>
                                @endif
                            </flux:table.cell>

                            <flux:table.cell>
                                @if($company->confidence_score)
                                    <div class="flex items-center space-x-2">
                                        <div class="w-16 bg-zinc-200 dark:bg-zinc-700 rounded-full h-2">
                                            <div
                                                class="h-2 rounded-full transition-all duration-300 {{ $company->confidence_score >= 0.8 ? 'bg-green-500' : ($company->confidence_score >= 0.6 ? 'bg-yellow-500' : 'bg-red-500') }}"
                                                style="width: {{ $company->confidence_score * 100 }}%"
                                            ></div>
                                        </div>
                                        <flux:text size="sm" class="text-zinc-600 dark:text-zinc-400">
                                            {{ number_format($company->confidence_score * 100, 1) }}%
                                        </flux:text>
                                    </div>
                                @else
                                    <flux:text size="sm" class="text-zinc-400">
                                        -
                                    </flux:text>
                                @endif
                            </flux:table.cell>

                            <flux:table.cell>
                                <flux:badge
                                    color="{{ $company->status->color() }}"
                                    size="sm"
                                >
                                    {{ $company->status->label() }}
                                </flux:badge>
                            </flux:table.cell>

                            <flux:table.cell>
                                <flux:text size="sm" class="text-zinc-600 dark:text-zinc-400">
                                    {{ $company->last_analyzed_at?->diffForHumans() ?? 'Never' }}
                                </flux:text>
                            </flux:table.cell>

                            <flux:table.cell>
                                <flux:dropdown>
                                    <flux:button
                                        variant="ghost"
                                        size="sm"
                                        icon="ellipsis-vertical"
                                    />

                                    <flux:menu>
                                        <flux:menu.item
                                            icon="eye"
                                            href="{{ route('companies.show', $company) }}"
                                        >
                                            View Details
                                        </flux:menu.item>

                                        @if($company->status !== \App\Enums\CompanyStatus::PROCESSING)
                                            <flux:menu.item
                                                icon="cog"
                                                wire:click="$dispatch('analyze-company', { id: {{ $company->id }} })"
                                            >
                                                Analyze
                                            </flux:menu.item>
                                        @endif

                                        <flux:menu.separator />

                                        <flux:menu.item
                                            icon="trash"
                                            variant="danger"
                                            onclick="return confirm('Are you sure you want to delete this company?')"
                                            wire:click="$dispatch('delete-company', { id: {{ $company->id }} })"
                                        >
                                            Delete
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>

            <!-- Pagination -->
            <div class="mt-6">
                {{ $this->companies->links() }}
            </div>
        @else
            <!-- Empty State -->
            <div class="text-center py-12">
                @if($this->hasFilters)
                    <flux:icon.magnifying-glass class="w-16 h-16 text-zinc-400 mx-auto mb-4" />
                    <flux:heading size="lg" class="mb-2">No companies found</flux:heading>
                    <flux:text class="text-zinc-600 dark:text-zinc-400 mb-4">
                        No companies match your current filters.
                    </flux:text>
                    <flux:button
                        wire:click="clearFilters"
                        variant="primary"
                    >
                        Clear Filters
                    </flux:button>
                @else
                    <flux:icon.building-office class="w-16 h-16 text-zinc-400 mx-auto mb-4" />
                    <flux:heading size="lg" class="mb-2">No companies yet</flux:heading>
                    <flux:text class="text-zinc-600 dark:text-zinc-400 mb-4">
                        Get started by adding your first company to analyze.
                    </flux:text>
                    <flux:button
                        href="{{ route('companies.create') }}"
                        variant="primary"
                        icon="plus"
                    >
                        Add Your First Company
                    </flux:button>
                @endif
            </div>
        @endif
    </flux:card>

    <!-- Results Summary -->
    @if($this->companies->count() > 0)
        <flux:card class="bg-zinc-50 dark:bg-zinc-900/50">
            <div class="flex items-center justify-between text-sm text-zinc-600 dark:text-zinc-400">
                <span>
                    Showing {{ $this->companies->firstItem() }} to {{ $this->companies->lastItem() }}
                    of {{ $this->companies->total() }} companies
                </span>
                <span>
                    {{ $this->companies->count() }} on this page
                </span>
            </div>
        </flux:card>
    @endif
</div>
