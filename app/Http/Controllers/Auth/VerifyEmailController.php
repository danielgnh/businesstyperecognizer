<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    public function __construct(private readonly \Illuminate\Routing\Redirector $redirector, private readonly \Illuminate\Routing\UrlGenerator $urlGenerator, private readonly \Illuminate\Events\Dispatcher $dispatcher) {}

    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return $this->redirector->intended($this->urlGenerator->route('dashboard', absolute: false).'?verified=1');
        }

        if ($request->user()->markEmailAsVerified()) {
            /** @var \Illuminate\Contracts\Auth\MustVerifyEmail $user */
            $user = $request->user();

            $this->dispatcher->dispatch(new Verified($user));
        }

        return $this->redirector->intended($this->urlGenerator->route('dashboard', absolute: false).'?verified=1');
    }
}
