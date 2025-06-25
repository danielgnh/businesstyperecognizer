<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ request()->cookie('theme', 'light') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ isset($title) ? $title . ' - ' : '' }}{{ config('app.name', 'Business Type Recognizer') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
</head>
<body class="min-h-screen bg-white dark:bg-zinc-900">
    <!-- Main Layout Container -->
    <div class="flex min-h-screen">
        <flux:header container class="bg-zinc-50 dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
            <flux:brand :href="route('dashboard')" logo="https://fluxui.dev/img/demo/logo.png" name="BusinessType AI" class="max-lg:hidden dark:hidden" />
            <flux:brand :href="route('dashboard')" logo="https://fluxui.dev/img/demo/dark-mode-logo.png" name="BusinessType AI" class="max-lg:hidden! hidden dark:flex" />
            <flux:navbar class="-mb-px max-lg:hidden">
                <flux:navbar.item icon="home" href="{{ route('dashboard') }}" :current="request()->routeIs('dashboard')">
                    {{__('Dashboard')}}
                </flux:navbar.item>
                <flux:navbar.item icon="plus" href="{{ route('companies.create') }}" :current="request()->routeIs('companies.create')">
                    {{__('Analyze Company')}}
                </flux:navbar.item>
                <flux:navbar.item
                    icon="building-office"
                    href="{{ route('companies.index') }}"
                    :current="request()->routeIs('companies.index') || request()->routeIs('companies.show') || request()->routeIs('companies.edit')"
                    badge="{{ $companiesCount ?? null }}"
                >
                    Companies
                </flux:navbar.item>
                <flux:separator vertical variant="subtle" class="my-2"/>
                <flux:dropdown class="max-lg:hidden">
                    <flux:navbar.item icon:trailing="chevron-down">Analysis</flux:navbar.item>
                    <flux:navmenu>
                        <flux:navmenu.item href="{{ route('companies.classified') }}" :current="request()->routeIs('companies.classified')">Classified</flux:navmenu.item>
                        <flux:navmenu.item href="{{ route('companies.pending') }}" :current="request()->routeIs('companies.pending')">Pending</flux:navmenu.item>
                        <flux:navmenu.item href="{{ route('companies.processing') }}" :current="request()->routeIs('companies.processing')">Processing</flux:navmenu.item>
                    </flux:navmenu>
                </flux:dropdown>
            </flux:navbar>
            <flux:spacer />
            <flux:dropdown position="top" align="start">
                <flux:profile
                    icon:trailing="chevron-up-down"
                    avatar="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name) }}&background=0D8ABC&color=fff"
                    name="{{ auth()->user()->name }}"
                />

                <flux:menu>
                    <flux:menu.item
                        icon="user-circle"
                        href="{{ route('profile') }}"
                    >
                        Profile
                    </flux:menu.item>
                    <flux:menu.item
                        icon="cog-6-tooth"
                        href="{{ route('settings') }}"
                    >
                        Settings
                    </flux:menu.item>
                    <flux:menu.separator />
                    <flux:menu.item
                        icon="arrow-right-start-on-rectangle"
                        wire:click="logout"
                    >
                        Logout
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        </flux:header>


        <flux:sidebar stashable sticky class="lg:hidden bg-zinc-50 dark:bg-zinc-900 border rtl:border-r-0 rtl:border-l border-zinc-200 dark:border-zinc-700">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />
            <flux:brand href="#" logo="https://fluxui.dev/img/demo/logo.png" name="Acme Inc." class="px-2 dark:hidden" />
            <flux:brand href="#" logo="https://fluxui.dev/img/demo/dark-mode-logo.png" name="Acme Inc." class="px-2 hidden dark:flex" />
            <flux:navlist variant="outline">
                <flux:navlist.item
                    icon="home"
                    href="{{ route('dashboard') }}"
                    :current="request()->routeIs('dashboard')"
                >
                    Dashboard
                </flux:navlist.item>

                <flux:navlist.item
                    icon="plus-circle"
                    href="{{ route('companies.create') }}"
                    :current="request()->routeIs('companies.create')"
                >
                    Add Company
                </flux:navlist.item>

                <flux:navlist.item
                    icon="building-office"
                    href="{{ route('companies.index') }}"
                    :current="request()->routeIs('companies.*')"
                    badge="{{ $companiesCount ?? null }}"
                >
                    Companies
                </flux:navlist.item>

                <flux:navlist.group expandable heading="Analysis">
                    <flux:navlist.item href="{{ route('companies.classified') }}" :current="request()->routeIs('companies.classified')">
                        Classified
                    </flux:navlist.item>
                    <flux:navlist.item href="{{ route('companies.pending') }}" :current="request()->routeIs('companies.pending')">
                        Pending
                    </flux:navlist.item>
                    <flux:navlist.item href="{{ route('companies.processing') }}" :current="request()->routeIs('companies.processing')">
                        Processing
                    </flux:navlist.item>
                </flux:navlist.group>

                <flux:navlist.group expandable heading="Analytics" :expanded="false">
                    <flux:navlist.item href="#" icon="chart-bar">Reports</flux:navlist.item>
                    <flux:navlist.item href="#" icon="chart-pie">Statistics</flux:navlist.item>
                    <flux:navlist.item href="#" icon="document-chart-bar">Accuracy Metrics</flux:navlist.item>
                </flux:navlist.group>
            </flux:navlist>
            <flux:spacer />
            <flux:navlist variant="outline">
                <flux:navlist.item icon="cog-6-tooth" href="#">Settings</flux:navlist.item>
                <flux:navlist.item icon="information-circle" href="#">Help</flux:navlist.item>
            </flux:navlist>
        </flux:sidebar>

        <!-- Main Content Area -->
        <flux:main container class="flex-1 p-6">
            {{ $slot }}
        </flux:main>
    </div>

    @fluxScripts
    @livewireScripts
</body>
</html>
