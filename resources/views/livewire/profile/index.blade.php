<div class="max-w-4xl mx-auto space-y-6">
    <!-- Page Header -->
    <div>
        <flux:heading size="xl">Profile Settings</flux:heading>
        <flux:text class="text-zinc-600 dark:text-zinc-400 mt-1">
            Manage your account settings and preferences
        </flux:text>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Profile Information -->
        <div class="lg:col-span-2">
            <flux:card class="space-y-6">
                <flux:heading size="lg">Profile Information</flux:heading>

                <form wire:submit="updateProfile" class="space-y-4">
                    <flux:input
                        wire:model="name"
                        label="Name"
                        placeholder="Your full name"
                        icon="user"
                        :invalid="$errors->has('name')"
                        required
                    />
                    @error('name')
                        <flux:error>{{ $message }}</flux:error>
                    @enderror

                    <flux:input
                        wire:model="email"
                        label="Email"
                        type="email"
                        placeholder="your@email.com"
                        icon="envelope"
                        :invalid="$errors->has('email')"
                        required
                    />
                    @error('email')
                        <flux:error>{{ $message }}</flux:error>
                    @enderror

                    <div class="flex justify-end">
                        <flux:button
                            type="submit"
                            variant="primary"
                            wire:loading.attr="disabled"
                        >
                            <span wire:loading.remove>Update Profile</span>
                            <span wire:loading>Updating...</span>
                        </flux:button>
                    </div>
                </form>
            </flux:card>
        </div>

        <!-- User Avatar & Stats -->
        <div class="space-y-6">
            <!-- Avatar -->
            <flux:card class="text-center space-y-4">
                <div class="w-24 h-24 bg-blue-500 rounded-full flex items-center justify-center mx-auto">
                    <flux:icon.user class="w-12 h-12 text-white" />
                </div>
                <div>
                    <flux:heading size="lg">{{ auth()->user()->name }}</flux:heading>
                    <flux:text class="text-zinc-600 dark:text-zinc-400">
                        {{ auth()->user()->email }}
                    </flux:text>
                </div>
                <flux:text size="sm" class="text-zinc-500">
                    Member since {{ auth()->user()->created_at->format('M Y') }}
                </flux:text>
            </flux:card>

            <!-- Account Stats -->
            <flux:card class="space-y-4">
                <flux:heading size="base">Account Activity</flux:heading>

                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <flux:text size="sm">Companies Added</flux:text>
                        <flux:text size="sm" class="font-medium">
{{--                            {{ auth()->user()?->companies()?->count() ?? 0 }}--}}
                        </flux:text>
                    </div>
                    <div class="flex items-center justify-between">
                        <flux:text size="sm">Classifications Made</flux:text>
                        <flux:text size="sm" class="font-medium">
                            {{ auth()->user()?->classificationResults()?->count() ?? 0 }}
                        </flux:text>
                    </div>
                    <div class="flex items-center justify-between">
                        <flux:text size="sm">Last Login</flux:text>
                        <flux:text size="sm" class="font-medium">
                            {{ now()->diffForHumans() }}
                        </flux:text>
                    </div>
                </div>
            </flux:card>
        </div>
    </div>

    <!-- Theme Preferences -->
    <flux:card class="space-y-6">
        <flux:heading size="lg">Appearance</flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <flux:text class="font-medium mb-3">Theme Preference</flux:text>

                <flux:radio.group wire:model.live="theme" class="space-y-2">
                    <flux:radio value="light" label="Light Mode" description="Clean and bright interface" />
                    <flux:radio value="dark" label="Dark Mode" description="Easy on the eyes" />
                </flux:radio.group>
            </div>

            <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg p-4">
                <flux:text class="font-medium mb-2">Preview</flux:text>
                <div class="{{ $theme === 'dark' ? 'bg-zinc-900 text-white' : 'bg-white text-zinc-900' }} border border-zinc-200 dark:border-zinc-700 rounded p-3 transition-colors">
                    <div class="flex items-center space-x-2 mb-2">
                        <div class="w-3 h-3 {{ $theme === 'dark' ? 'bg-zinc-700' : 'bg-zinc-300' }} rounded-full"></div>
                        <div class="w-16 h-2 {{ $theme === 'dark' ? 'bg-zinc-700' : 'bg-zinc-300' }} rounded"></div>
                    </div>
                    <div class="w-20 h-2 {{ $theme === 'dark' ? 'bg-blue-400' : 'bg-blue-500' }} rounded mb-1"></div>
                    <div class="w-24 h-1 {{ $theme === 'dark' ? 'bg-zinc-600' : 'bg-zinc-400' }} rounded"></div>
                </div>
            </div>
        </div>
    </flux:card>

    <!-- Danger Zone -->
    <flux:card class="border-red-200 dark:border-red-800">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg" class="text-red-600 dark:text-red-400">Danger Zone</flux:heading>
                <flux:text class="text-red-600 dark:text-red-400">
                    These actions cannot be undone
                </flux:text>
            </div>

            <div class="flex items-center justify-between p-4 border border-red-200 dark:border-red-800 rounded-lg">
                <div>
                    <flux:text class="font-medium">Delete Account</flux:text>
                    <flux:text size="sm" class="text-zinc-600 dark:text-zinc-400">
                        Permanently delete your account and all associated data
                    </flux:text>
                </div>
                <flux:button
                    onclick="alert('Account deletion not implemented yet')"
                >
                    Delete Account
                </flux:button>
            </div>
        </div>
    </flux:card>
</div>
