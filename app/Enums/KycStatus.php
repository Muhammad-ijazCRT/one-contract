<?php

namespace App\Enums;

enum KycStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Under Verification',
            self::Approved => 'Confirmed',
            self::Rejected => 'Rejected',
        };
    }

    public function isApproved(): bool
    {
        return $this === self::Approved;
    }
}
