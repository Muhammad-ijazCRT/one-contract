<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MonnifyClient
{
    public function authenticate(): string
    {
        $cacheKey = 'monnify.token.'.MonnifySettings::mode();

        return Cache::remember($cacheKey, 3000, function () {
            $apiKey = MonnifySettings::apiKey();
            $secret = MonnifySettings::secretKey();

            if ($apiKey === '' || $secret === '') {
                throw new RuntimeException('Monnify API credentials are not configured.');
            }

            $response = Http::withBasicAuth($apiKey, $secret)
                ->acceptJson()
                ->post(MonnifySettings::baseUrl().'/api/v1/auth/login');

            if (! $response->successful()) {
                throw new RuntimeException('Monnify authentication failed: '.$response->body());
            }

            $token = data_get($response->json(), 'responseBody.accessToken');

            if (! is_string($token) || $token === '') {
                throw new RuntimeException('Monnify authentication returned no access token.');
            }

            return $token;
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function initTransaction(array $payload): array
    {
        return $this->request('post', '/api/v1/merchant/transactions/init-transaction', $payload);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function banks(): array
    {
        $body = $this->request('get', '/api/v1/banks');

        return is_array($body) ? $body : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function validateAccount(string $accountNumber, string $bankCode): array
    {
        return $this->request('get', '/api/v1/disbursements/account/validate', [
            'accountNumber' => $accountNumber,
            'bankCode' => $bankCode,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function initiateSingleTransfer(array $payload): array
    {
        return $this->request('post', '/api/v2/disbursements/single', $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function getTransactionStatus(string $transactionReference): array
    {
        return $this->request('get', '/api/v2/transactions/'.rawurlencode($transactionReference));
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array $query = []): array
    {
        $token = $this->authenticate();
        $url = MonnifySettings::baseUrl().$path;

        /** @var PendingRequest $client */
        $client = Http::withToken($token)->acceptJson();

        $response = match (strtolower($method)) {
            'get' => $client->get($url, $query),
            'post' => $client->post($url, $query),
            default => throw new RuntimeException('Unsupported Monnify HTTP method.'),
        };

        $json = $response->json();

        if (! $response->successful() || ! data_get($json, 'requestSuccessful')) {
            $message = (string) (data_get($json, 'responseMessage') ?: $response->body());
            throw new RuntimeException('Monnify API error: '.$message);
        }

        $body = data_get($json, 'responseBody');

        return is_array($body) ? $body : [];
    }
}
