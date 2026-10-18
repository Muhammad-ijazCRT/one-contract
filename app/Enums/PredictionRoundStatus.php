<?php

namespace App\Enums;

enum PredictionRoundStatus: string
{
    case Betting = 'betting';
    case Settling = 'settling';
    case Completed = 'completed';
}
