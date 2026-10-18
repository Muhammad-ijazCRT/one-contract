<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewsletterController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->string('q')->toString());

        $subscribers = NewsletterSubscriber::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where('email', 'like', '%'.$search.'%');
            })
            ->latest('subscribed_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.newsletter.index', [
            'subscribers' => $subscribers,
            'search' => $search,
            'totalCount' => NewsletterSubscriber::query()->count(),
        ]);
    }

    public function destroy(NewsletterSubscriber $subscriber): RedirectResponse
    {
        $subscriber->delete();

        return back()->with('success', 'Subscriber removed successfully.');
    }
}
