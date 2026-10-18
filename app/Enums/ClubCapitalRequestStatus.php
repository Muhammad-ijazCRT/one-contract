<?php

namespace App\Enums;

enum ClubCapitalRequestStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
