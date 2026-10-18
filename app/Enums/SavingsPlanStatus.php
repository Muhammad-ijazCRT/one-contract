<?php

namespace App\Enums;

enum SavingsPlanStatus: string
{
    case Active = 'active';
    case Matured = 'matured';
    case PendingMaturityReturn = 'pending_maturity_return';
    case PendingEarlyReturn = 'pending_early_return';
    case Completed = 'completed';
    case WithdrawnEarly = 'withdrawn_early';
    case Cancelled = 'cancelled';
}
