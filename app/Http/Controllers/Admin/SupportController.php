<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupportController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();
        $query = SupportTicket::query();

        if (in_array($status, ['open', 'resolved'], true)) {
            $query->where('status', $status);
        }

        return view('admin.support.index', [
            'tickets' => $query->latest()->paginate(20)->withQueryString(),
            'statusFilter' => $status,
        ]);
    }

    public function show(SupportTicket $ticket): View
    {
        return view('admin.support.show', [
            'ticket' => $ticket,
        ]);
    }

    public function update(SupportTicket $ticket, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:open,resolved'],
        ]);

        $ticket->update(['status' => $validated['status']]);

        return back()->with('success', 'Support ticket status updated successfully.');
    }
}
