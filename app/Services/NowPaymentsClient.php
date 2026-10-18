<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class NowPaymentsClient
{
    public function apiKey(): string
    {
        return NowPaymentsSettings::apiKey();
    }

    public function isConfigured(): bool
    {
        return $this->apiKey() !== '';
    }

    public function custodyConfigured(): bool
    {
        return $this->isConfigured()
            && NowPaymentsSettings::custodyEmail() !== ''
            && NowPaymentsSettings::custodyPassword() !== '';
    }

    /**
     * @return array<string, mixed>
     */
    public function estimate(float $amount, string $currencyFrom, string $currencyTo): array
    {
        return $this->get('/v1/estimate', [
            'amount' => $amount,
            'currency_from' => strtolower($currencyFrom),
            'currency_to' => strtolower($currencyTo),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function minAmount(string $currencyFrom, string $currencyTo): array
    {
        return $this->get('/v1/min-amount', [
            'currency_from' => strtolower($currencyFrom),
            'currency_to' => strtolower($currencyTo),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function createConversion(float $amount, string $currencyFrom, string $currencyTo): array
    {
        return $this->post('/v1/conversion', [
            'amount' => $amount,
            'from_currency' => strtolower($currencyFrom),
            'to_currency' => strtolower($currencyTo),
        ], $this->authToken());
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createInvoice(array $payload): array
    {
        $endpoint = NowPaymentsSettings::checkoutEndpoint();

        return $this->post($endpoint, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createPayment(array $payload): array
    {
        return $this->post('/v1/payment', $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function getConversion(int|string $id): array
    {
        $payload = $this->get('/v1/conversion', ['id' => $id], $this->authToken());

        if (isset($payload['result']) && is_array($payload['result'])) {
            return $payload;
        }

        if (isset($payload['conversions']) && is_array($payload['conversions'])) {
            return $payload;
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function get(string $path, array $query = [], ?string $jwt = null): array
    {
        return $this->request('get', $path, $query, null, $jwt);
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    public function post(string $path, array $body = [], ?string $jwt = null): array
    {
        return $this->request('post', $path, [], $body, $jwt);
    }

    public function authToken(): string
    {
        $email = NowPaymentsSettings::custodyEmail();
        $password = NowPaymentsSettings::custodyPassword();

        if ($email === '' || $password === '') {
            throw ValidationException::withMessages([
                'conversion' => 'NOWPayments Custody credentials are not configured. Contact support.',
            ]);
        }

        $cacheKey = 'nowpayments.jwt.'.md5(NowPaymentsSettings::mode().'|'.$email);

        return Cache::remember($cacheKey, now()->addMinutes(4), function () use ($email, $password) {
            $response = $this->request('post', '/v1/auth', [], [
                'email' => $email,
                'password' => $password,
            ]);

            $token = (string) ($response['token'] ?? '');

            if ($token === '') {
                throw ValidationException::withMessages([
                    'conversion' => 'NOWPayments authentication failed. Check Custody credentials in admin.',
                ]);
            }

            return $token;
        });
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>|null  $body
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array $query = [], ?array $body = null, ?string $jwt = null): array
    {
        if (! $this->isConfigured() && $path !== '/v1/auth') {
            throw ValidationException::withMessages([
                'conversion' => 'NOWPayments API key is not configured.',
            ]);
        }

        $url = NowPaymentsSettings::baseUrl().'/'.ltrim($path, '/');

        $pending = Http::timeout(25)
            ->acceptJson()
            ->withHeaders($this->headers($jwt));

        try {
            $response = match ($method) {
                'post' => $pending->post($url, $body ?? []),
                default => $pending->get($url, $query),
            };

            if ($response->failed()) {
                Log::warning('NOWPayments API error', [
                    'method' => strtoupper($method),
                    'path' => $path,
                    'status' => $response->status(),
                    'body' => $response->json() ?? $response->body(),
                ]);

                $message = (string) ($response->json('message') ?? $response->json('error') ?? 'NOWPayments request failed.');

                throw ValidationException::withMessages(['conversion' => $message]);
            }

            return $response->json() ?? [];
        } catch (ValidationException $e) {
            throw $e;
        } catch (RequestException $e) {
            Log::warning('NOWPayments HTTP exception', [
                'path' => $path,
                'message' => $e->getMessage(),
            ]);

            throw ValidationException::withMessages([
                'conversion' => 'NOWPayments is temporarily unavailable. Please try again.',
            ]);
        }
    }

    /**
     * @return array<string, string>
     */
    private function headers(?string $jwt): array
    {
        $headers = [
            'x-api-key' => $this->apiKey(),
        ];

        if ($jwt) {
            $headers['Authorization'] = 'Bearer '.$jwt;
        }

        return $headers;
    }
}
