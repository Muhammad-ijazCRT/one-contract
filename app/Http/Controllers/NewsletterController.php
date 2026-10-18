<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ], [
            'email.required' => 'Please enter your email address.',
            'email.email' => 'Please enter a valid email address.',
        ]);

        $email = strtolower(trim($validated['email']));

        NewsletterSubscriber::query()->firstOrCreate(
            ['email' => $email],
            ['subscribed_at' => now()]
        );

        return back()
            ->withFragment('contact')
            ->with('newsletter_status', 'Thanks for subscribing! You will receive platform updates soon.');
    }
}
