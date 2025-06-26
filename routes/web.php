<?php

use App\Livewire\Dashboard;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return redirect()->route('dashboard');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');

    Route::prefix('companies')->name('companies.')->group(function () {
        Route::get('/', \App\Livewire\Company\Index::class)->name('index');
        Route::get('/create', \App\Livewire\Company\Create::class)->name('create');
        Route::get('/{company}', \App\Livewire\Company\Show::class)->name('show');

        Route::get('/status/classified', \App\Livewire\Company\Index::class)
            ->name('classified')
            ->defaults('statusFilter', 'completed');
        Route::get('/status/pending', \App\Livewire\Company\Index::class)
            ->name('pending')
            ->defaults('statusFilter', 'pending');
        Route::get('/status/processing', \App\Livewire\Company\Index::class)
            ->name('processing')
            ->defaults('statusFilter', 'processing');
    });

    Route::get('/settings', \App\Livewire\Company\Index::class)->name('settings');
});

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

require __DIR__.'/auth.php';
