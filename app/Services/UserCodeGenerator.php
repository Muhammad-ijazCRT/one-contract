<?php

namespace App\Services;

use App\Models\Referral;
use App\Models\User;
use Illuminate\Support\Str;

class UserCodeGenerator
{
    public static function memberId(): string
    {
        do {
            $id = 'OC'.str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (User::query()->where('member_id', $id)->exists());

        return $id;
    }

    public static function referralCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (User::query()->where('referral_code', $code)->exists());

        return $code;
    }

    public static function attachReferralChain(User $user, ?string $referralCode): void
    {
        if (! $referralCode) {
            return;
        }

        $directReferrer = User::query()->where('referral_code', $referralCode)->first();

        if (! $directReferrer || $directReferrer->id === $user->id) {
            return;
        }

        $user->update(['referred_by' => $directReferrer->id]);

        $chain = collect([$directReferrer]);
        $current = $directReferrer;

        while ($chain->count() < 3 && $current->referred_by) {
            $current = $current->referrer;
            if ($current) {
                $chain->push($current);
            }
        }

        foreach ($chain as $index => $referrer) {
            Referral::query()->create([
                'referrer_id' => $referrer->id,
                'referred_id' => $user->id,
                'level' => $index + 1,
            ]);
        }
    }
}
