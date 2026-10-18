<?php

namespace App\Enums;

enum FaceVerificationSessionStatus: string
{
    case Pending = 'pending';
    case Passed = 'passed';
    case Failed = 'failed';
    case Expired = 'expired';
    case Consumed = 'consumed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Passed => 'Passed',
            self::Failed => 'Failed',
            self::Expired => 'Expired',
            self::Consumed => 'Consumed',
        };
    }
}
