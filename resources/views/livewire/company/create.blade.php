<div class=" mx-auto space-y-6">
    <!-- Page Header -->
    <div class="flex sm:flex-row flex-col-reverse sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">Add New Company</flux:heading>
            <flux:text class="text-zinc-600 dark:text-zinc-400 mt-1">
                Add a company to analyze its business type (B2B, B2C, or Hybrid)
            </flux:text>
        </div>
        <flux:button
            href="{{ route('companies.index') }}"
            variant="ghost"
            icon="arrow-left"
        >
            Back to Companies
        </flux:button>
    </div>

    <div class="flex flex-col sm:flex-row gap-8">
        <!-- Main Form Card -->
        <flux:card class="space-y-6 flex-1">
            <form wire:submit="save" class="space-y-6">
                <!-- Company Details Section -->
                <flux:fieldset>
                    <flux:legend>Company Details</flux:legend>

                    <div class="space-y-4">
                        <!-- Website URL Input -->
                        <flux:input
                            wire:model.lazy="website"
                            wire:blur="validateWebsite"
                            label="Company's Website URL"
                            type="url"
                            placeholder="https://example.com"
                            icon="globe-alt"
                            :invalid="$errors->has('website')"
                            required
                        />
                        @error('website')
                            <flux:error>{{ $message }}</flux:error>
                        @enderror

                        <!-- Company Name Input -->
                        <flux:input
                            wire:model.lazy="name"
                            wire:blur="validateName"
                            label="Company Name"
                            placeholder="Will be auto-generated from website"
                            icon="building-office"
                            :invalid="$errors->has('name')"
                            required
                        />
                        @error('name')
                            <flux:error>{{ $message }}</flux:error>
                        @enderror

                    </div>
                </flux:fieldset>

                <!-- Analysis Options -->
                <flux:fieldset>
                    <flux:legend>Analysis Options</flux:legend>
                    <div class="space-y-4">
                        <flux:switch
                            wire:model.lazy="autoAnalyze"
                            label="Start analysis automatically"
                            description="Immediately begin analyzing this company after adding it"
                        />
                    </div>
                </flux:fieldset>

                <!-- Analysis Preview -->
                @if($website && filter_var($website, FILTER_VALIDATE_URL) && !$errors->has('website'))
                    <flux:card class="bg-blue-50 dark:bg-blue-950/30 border-blue-200 dark:border-blue-800">
                        <div class="flex items-start space-x-3">
                            <flux:icon.information-circle class="w-5 h-5 text-blue-500 mt-0.5" />
                            <div class="flex-1 space-y-2">
                                <flux:heading size="sm" class="text-blue-900 dark:text-blue-100">
                                    Ready for Analysis
                                </flux:heading>
                                <flux:text size="sm" class="text-blue-700 dark:text-blue-300">
                                    We'll analyze <strong>{{ $website }}</strong> to determine if it's B2B, B2C, or Hybrid by examining:
                                </flux:text>
                                <div class="grid grid-cols-2 gap-2 text-sm text-blue-600 dark:text-blue-400">
                                    <div class="flex items-center space-x-1">
                                        <flux:icon.globe-alt class="w-4 h-4" />
                                        <span>Website content</span>
                                    </div>
                                    <div class="flex items-center space-x-1">
                                        <flux:icon.users class="w-4 h-4" />
                                        <span>Target audience</span>
                                    </div>
                                    <div class="flex items-center space-x-1">
                                        <flux:icon.currency-dollar class="w-4 h-4" />
                                        <span>Pricing patterns</span>
                                    </div>
                                    <div class="flex items-center space-x-1">
                                        <flux:icon.chat-bubble-left-right class="w-4 h-4" />
                                        <span>Communication style</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </flux:card>
                @endif

                <!-- Form Actions -->
                <div class="flex items-center justify-between pt-6 border-t border-zinc-200 dark:border-zinc-700">
                    <flux:button
                        type="button"
                        variant="ghost"
                        href="{{ route('companies.index') }}"
                    >
                        Cancel
                    </flux:button>

                    <div class="flex items-center space-x-3">
                        <flux:button
                            type="submit"
                            variant="primary"
                            icon="plus"
                            wire:loading.attr="disabled"
                            wire:target="save"
                        >
                            <span wire:loading.remove wire:target="save">Add Company</span>
                        </flux:button>
                    </div>
                </div>
            </form>
        </flux:card>

        <!-- Help Section -->
        <flux:card class="bg-zinc-50 dark:bg-zinc-900/50">
            <div class="space-y-4">
                <flux:heading size="lg" class="flex items-center space-x-2">
                    <flux:icon.question-mark-circle class="w-5 h-5 text-zinc-500" />
                    <span>How it works</span>
                </flux:heading>

                <div class="space-y-3">
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0 w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-sm font-medium">
                            1
                        </div>
                        <div>
                            <flux:text class="font-medium">Website Analysis</flux:text>
                            <flux:text size="sm" class="text-zinc-600 dark:text-zinc-400">
                                We scan the website content, structure, and design patterns
                            </flux:text>
                        </div>
                    </div>

                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0 w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-sm font-medium">
                            2
                        </div>
                        <div>
                            <flux:text class="font-medium">AI Classification</flux:text>
                            <flux:text size="sm" class="text-zinc-600 dark:text-zinc-400">
                                Our AI analyzes the data to determine B2B, B2C, or Hybrid classification
                            </flux:text>
                        </div>
                    </div>

                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0 w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-sm font-medium">
                            3
                        </div>
                        <div>
                            <flux:text class="font-medium">Confidence Score</flux:text>
                            <flux:text size="sm" class="text-zinc-600 dark:text-zinc-400">
                                Get a confidence score and detailed reasoning for the classification
                            </flux:text>
                        </div>
                    </div>
                </div>
            </div>
        </flux:card>
    </div>
</div>
