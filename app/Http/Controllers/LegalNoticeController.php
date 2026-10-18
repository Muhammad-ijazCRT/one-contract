<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class LegalNoticeController extends Controller
{
    public function show(string $slug): View
    {
        $pages = array_merge(
            config('legal-notices', []),
            config('legal-program', []),
        );

        if (! isset($pages[$slug])) {
            abort(404);
        }

        $page = $pages[$slug];
        $group = $page['group'] ?? 'notices';
        $sidebarPages = array_filter(
            $pages,
            fn (array $item): bool => ($item['group'] ?? 'notices') === $group,
        );

        return view('legal.show', [
            'slug' => $slug,
            'notice' => $page,
            'notices' => $sidebarPages,
            'sidebarTitle' => $group === 'program' ? 'Program' : 'Platform Notices',
        ]);
    }
}
