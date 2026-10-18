<?php

namespace App\Enums;

enum SavingsDeductionStatus: string
{
    case Success = 'success';
    case Skipped = 'skipped';
}
