<?php

namespace App\Enums;

enum FaceVerificationPurpose: string
{
    case Registration = 'registration';
    case Withdrawal = 'withdrawal';

    public function label(): string
    {
        return match ($this) {
            self::Registration => 'Registration',
            self::Withdrawal => 'Withdrawal',
        };
    }
}
