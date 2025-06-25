<?php

namespace App\Livewire\Actions;

class Logout
{
    /**
     * Log the current user out of the application.
     */
    public function __invoke()
    {
        auth()->guard('web')->logout();

        session()->invalidate();
        session()->regenerateToken();

        return redirect('/');
    }

    public function logout()
    {
        auth()->logout();
        session()->invalidate();
        session()->regenerateToken();

        return redirect('/');
    }
}
