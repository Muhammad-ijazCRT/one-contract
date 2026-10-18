<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        return view('user.transactions.index', [
            'transactions' => $user->transactions()->latest()->paginate(15),
        ]);
    }

    public function show(Transaction $transaction): View
    {
        abort_unless($transaction->user_id === auth()->id(), 403);

        return view('user.transactions.show', [
            'tx' => $transaction,
        ]);
    }
}
