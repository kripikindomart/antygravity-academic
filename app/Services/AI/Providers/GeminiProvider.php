<?php

namespace App\Services\AI\Providers;

use App\Services\AI\AiProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiProvider implements AiProviderInterface
{
    protected string $apiKey;
    protected string $model;

    public function __construct(string $apiKey, string $model = 'gemini-1.5-flash')
    {
        $this->apiKey = $apiKey;
        $this->model = $model;
    }

    public function generate(string $prompt, array $config = []): string
    {
        try {
            $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/' . $this->model . ':generateContent';

            $payload = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => $config['temperature'] ?? 0.7,
                    'maxOutputTokens' => 2048,
                    'responseMimeType' => 'application/json',
                ]
            ];

            if (!empty($config['system_prompt'])) {
                $payload['systemInstruction'] = [
                    'parts' => [['text' => $config['system_prompt']]]
                ];
            }

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post("{$baseUrl}?key={$this->apiKey}", $payload);

            if ($response->failed()) {
                throw new \Exception('Gemini API Error: ' . $response->body());
            }

            $data = $response->json();
            return $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
        } catch (\Exception $e) {
            Log::error('Gemini Provider Error: ' . $e->getMessage());
            throw $e;
        }
    }
}
