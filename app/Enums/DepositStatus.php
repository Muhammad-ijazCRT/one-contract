<?php

namespace App\Enums;

enum DepositStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Completed = 'completed';
    case Withdrawn = 'withdrawn';
    case Rejected = 'rejected';
}
