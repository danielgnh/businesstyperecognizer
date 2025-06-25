<?php

namespace App\Livewire\Actions;

class Logout
{
    /**
     * Log the current user out of the application.
     */
    public function __invoke()
    {
        $this->authManager->guard('web')->logout();

        $this->sessionManager->invalidate();
        $this->sessionManager->regenerateToken();

        return $this->redirector->to('/');
    }

    public function logout()
    {
        $this->guard->logout();
        $this->sessionManager->invalidate();
        $this->sessionManager->regenerateToken();

        return $this->redirector->to('/');
    }
}
