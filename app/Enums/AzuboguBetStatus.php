<?php

namespace App\Enums;

enum AzuboguBetStatus: string
{
    case Active = 'active';
    case Cancelled = 'cancelled';
    case Won = 'won';
    case Lost = 'lost';
}
