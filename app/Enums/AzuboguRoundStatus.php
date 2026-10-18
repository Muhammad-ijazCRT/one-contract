<?php

namespace App\Enums;

enum AzuboguRoundStatus: string
{
    case Betting = 'betting';
    case Settling = 'settling';
    case Completed = 'completed';
}
