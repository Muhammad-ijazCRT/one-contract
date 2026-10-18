<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class LuckyDiceController extends Controller
{
    /**
     * Placeholder page for the client's future 3rd game (details TBD).
     */
    public function index(): View
    {
        return view('user.coming-soon-game.index');
    }
}
