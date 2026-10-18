<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Treasury;
use App\Services\AdminLogService;
use App\Services\TreasuryStatsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TreasuryController extends Controller
{
    public function __construct(
        private readonly TreasuryStatsService $treasuryStats,
        private readonly AdminLogService $adminLog,
    ) {}

    public function index(): View
    {
        $treasury = Treasury::query()->first();
        $stats = $this->treasuryStats->getStats();

        return view('admin.treasury.index', [
            'treasury' => $treasury,
            'stats' => $stats,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'balance' => ['required', 'numeric', 'min:0'],
            'reserved_balance' => ['required', 'numeric', 'min:0'],
        ]);

        $treasury = Treasury::query()->firstOrCreate([], [
            'balance' => 0,
            'reserved_balance' => 0,
        ]);

        $oldBalance = (float) $treasury->balance;
        $treasury->update($data);

        $this->adminLog->log(
            auth()->user(),
            'treasury_updated',
            $treasury,
            'Treasury balance updated from '.number_format($oldBalance, 2).' to '.number_format((float) $data['balance'], 2),
            $data,
        );

        return back()->with('success', 'Treasury liquidity updated successfully.');
    }
}
