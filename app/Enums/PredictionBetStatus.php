<?php

namespace App\Enums;

enum PredictionBetStatus: string
{
    case Active = 'active';
    case Cancelled = 'cancelled';
    case Won = 'won';
    case Lost = 'lost';
}
