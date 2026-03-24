<?php

namespace App\Services\WhatsApp;

use Http;

class EvolutionService
{
    private string $baseUrl;

    private string $apiKey;

    private string $instance;

    public function __construct()
    {
        $this->baseUrl = config('evolution.url');
        $this->apiKey = config('evolution.api_key');
        $this->instance = config('evolution.instance');
    }

    public function sendText(string $to, string $message): void
    {
        $response = Http::withHeaders([
            'apikey' => $this->apiKey,
        ])->post("{$this->baseUrl}/message/sendText/{$this->instance}", [
            'number' => $to,
            'text' => $message,
        ]);

        \Log::info('Evolution API Response', [
            'status' => $response->status(),
            'body' => $response->json()
        ]);
    }
}
