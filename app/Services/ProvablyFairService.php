<?php

namespace App\Services;

class ProvablyFairService
{
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
     * Deterministic 50/50 outcome from seeds. Returns 1 or 2.
     */
    public function resolveOutcome(string $serverSeed, string $clientSeed, int $nonce): int
    {
        $hash = hash_hmac('sha256', $clientSeed.':'.$nonce, $serverSeed);
        $value = hexdec(substr($hash, 0, 8));

        return ($value % 2) === 0 ? 1 : 2;
    }

    public function verify(string $serverSeed, string $serverSeedHash, string $clientSeed, int $nonce, int $outcome): bool
    {
        if ($this->hashServerSeed($serverSeed) !== $serverSeedHash) {
            return false;
        }

        return $this->resolveOutcome($serverSeed, $clientSeed, $nonce) === $outcome;
    }
}
