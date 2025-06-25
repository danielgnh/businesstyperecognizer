<?php

declare(strict_types=1);

namespace App\Livewire\Profile;

use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Profile')]
class Index extends Component
{
    public string $name = '';

    public string $email = '';

    public string $theme = 'light';

    public function mount(\Illuminate\Http\Request $request): void
    {
        $user = auth()->user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->theme = $request->cookie('theme', 'light');
    }

    public function updateProfile(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        auth()->user()->update([
            'name' => $this->name,
            'email' => $this->email,
        ]);

        session()->flash('message', 'Profile updated successfully!');
    }

    public function render(): View
    {
        return view('livewire.profile.index');
    }
}
