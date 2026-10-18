<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DojahClient
{
    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    public function post(string $path, array $body, int $timeout = 30): array
    {
        return $this->send('post', $this->url($path), $body, $timeout);
    }

    /**
     * @return array<string, mixed>
     */
    public function get(string $path, int $timeout = 15): array
    {
        return $this->send('get', $this->url($path), [], $timeout);
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    public function ingest(array $body, int $timeout = 20): array
    {
        return $this->send('post', DojahSettings::ingestUrl(), $body, $timeout, [
            'Authorization' => DojahSettings::secretKey(),
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * @param  array<string, mixed>  $body
     * @param  array<string, string>|null  $headers
     * @return array<string, mixed>
     */
    private function send(string $method, string $url, array $body, int $timeout, ?array $headers = null): array
    {
        $headers ??= [
            'Authorization' => DojahSettings::secretKey(),
            'AppId' => DojahSettings::appId(),
            'Content-Type' => 'application/json',
        ];

        $request = Http::timeout($timeout)
            ->acceptJson()
            ->withHeaders($headers);

        /** @var Response $response */
        $response = $method === 'get'
            ? $request->get($url)
            : $request->post($url, $body);

        $payload = $response->json();
        $payload = is_array($payload) ? $payload : ['raw' => $response->body()];
        $payload['_http_status'] = $response->status();
        $payload['_ok'] = $response->successful();

        if (! $response->successful()) {
            Log::warning('Dojah API request failed', [
                'url' => $url,
                'status' => $response->status(),
                'body' => $this->safeLog($payload),
            ]);
        }

        return $payload;
    }

    private function url(string $path): string
    {
        return rtrim(DojahSettings::baseUrl(), '/').'/'.ltrim($path, '/');
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function safeLog(array $payload): array
    {
        unset($payload['image'], $payload['selfie'], $payload['face_image']);

        return $payload;
    }
}
