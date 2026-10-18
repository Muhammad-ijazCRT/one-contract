<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use Illuminate\View\View;

class LogController extends Controller
{
    public function index(): View
    {
        return view('admin.logs.index', [
            'logs' => AdminLog::query()
                ->with('admin')
                ->latest()
                ->paginate(30),
        ]);
    }
}
