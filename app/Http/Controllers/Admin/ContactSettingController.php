<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminLogService;
use App\Services\ContactSettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactSettingController extends Controller
{
    public function __construct(
        private readonly ContactSettingService $contactSettings,
        private readonly AdminLogService $adminLog,
    ) {}

    public function index(): View
    {
        return view('admin.contact-settings.index', [
            'settings' => $this->contactSettings->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'support_hours' => ['required', 'string', 'max:120'],
            'office_address' => ['required', 'string', 'max:255'],
            'social_telegram' => ['required', 'url', 'max:500'],
            'social_twitter' => ['nullable', 'url', 'max:500'],
            'social_facebook' => ['nullable', 'url', 'max:500'],
            'social_youtube' => ['nullable', 'url', 'max:500'],
        ]);

        $data['social_telegram'] = $data['social_telegram'] ?: 'https://t.me/';
        $data['social_twitter'] = $data['social_twitter'] ?: null;
        $data['social_facebook'] = $data['social_facebook'] ?: null;
        $data['social_youtube'] = $data['social_youtube'] ?: null;

        $this->contactSettings->update($data);

        $this->adminLog->log(
            auth()->user(),
            'contact_settings_updated',
            null,
            'Contact page settings updated',
            $data,
        );

        return back()->with('success', 'Contact page settings saved successfully.');
    }
}
