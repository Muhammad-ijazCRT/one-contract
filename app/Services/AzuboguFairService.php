<?php

namespace App\Services;

/**
 * Provably fair multiplier assignment for Azubogu Prediction Game.
 * Shuffles 1–12 into four buckets of three: 1.90×, 1.40×, 0.45×, 0×.
 */
class AzuboguFairService
{
    public const MULTIPLIERS = [1.90, 1.40, 0.45, 0.0];

    public function generateServerSeed(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function hashServerSeed(string $serverSeed): string
    {
        return hash('sha256', $serverSeed);
    }

    public function generateClientSeed(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * @return array<int, float> number => multiplier
     */
    public function resolveMultipliers(string $serverSeed, string $clientSeed, int $nonce): array
    {
        $numbers = range(1, 12);
        $n = count($numbers);

        for ($i = $n - 1; $i > 0; $i--) {
            $hash = hash_hmac('sha256', $clientSeed.':'.$nonce.':shuffle:'.$i, $serverSeed);
            $j = hexdec(substr($hash, 0, 8)) % ($i + 1);
            [$numbers[$i], $numbers[$j]] = [$numbers[$j], $numbers[$i]];
        }

        $map = [];
        $tiers = [
            [1.90, 3],
            [1.40, 3],
            [0.45, 3],
            [0.0, 3],
        ];

        $offset = 0;
        foreach ($tiers as [$mult, $count]) {
            for ($k = 0; $k < $count; $k++) {
                $map[$numbers[$offset + $k]] = $mult;
            }
            $offset += $count;
        }

        ksort($map);

        return $map;
    }

    /**
     * @param  list<int>  $selected
     * @param  array<int, float>  $multipliers
     */
    public function combinedMultiplier(array $selected, array $multipliers): float
    {
        $product = 1.0;
        foreach ($selected as $num) {
            $product *= (float) ($multipliers[(int) $num] ?? 0);
        }

        return round($product, 8);
    }

    public function verify(
        string $serverSeed,
        string $serverSeedHash,
        string $clientSeed,
        int $nonce,
        array $expectedMultipliers,
    ): bool {
        if ($this->hashServerSeed($serverSeed) !== $serverSeedHash) {
            return false;
        }

        $resolved = $this->resolveMultipliers($serverSeed, $clientSeed, $nonce);

        foreach ($expectedMultipliers as $number => $mult) {
            if (! isset($resolved[(int) $number]) || abs($resolved[(int) $number] - (float) $mult) > 0.0000001) {
                return false;
            }
        }

        return count($resolved) === count($expectedMultipliers);
    }
}
