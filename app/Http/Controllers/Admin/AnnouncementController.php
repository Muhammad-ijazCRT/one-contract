<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Services\AdminLogService;
use App\Services\AnnouncementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function __construct(
        private readonly AnnouncementService $announcements,
        private readonly AdminLogService $adminLog,
    ) {}

    public function index(): View
    {
        return view('admin.announcements.index', [
            'recent' => AdminLog::query()
                ->where('action', 'announcement_sent')
                ->latest()
                ->limit(20)
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $count = $this->announcements->broadcast($data['title'], $data['message']);

        $this->adminLog->log(
            auth()->user(),
            'announcement_sent',
            null,
            "Announcement sent to {$count} users: {$data['title']}",
            $data,
        );

        return back()->with('success', "Announcement sent to {$count} users.");
    }
}
