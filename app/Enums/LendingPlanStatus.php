<?php

namespace App\Enums;

enum LendingPlanStatus: string
{
    case Active = 'active';
    case Matured = 'matured';
    case PendingMaturityReturn = 'pending_maturity_return';
    case PendingEarlyReturn = 'pending_early_return';
    case WithdrawnEarly = 'withdrawn_early';
    case Completed = 'completed';
}
