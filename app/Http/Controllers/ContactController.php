<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Services\ContactSettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function __construct(
        private readonly ContactSettingService $contactSettings,
    ) {}

    public function index(): View
    {
        return view('contact.index', [
            'settings' => $this->contactSettings->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'in:general,account,deposits,withdrawals,technical'],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        SupportTicket::query()->create([
            'user_id' => auth()->id(),
            'name' => $validated['name'],
            'email' => $validated['email'],
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'status' => SupportTicket::STATUS_OPEN,
        ]);

        return back()->with('status', 'Thank you for reaching out! Our team will get back to you within 24 hours.');
    }
}
