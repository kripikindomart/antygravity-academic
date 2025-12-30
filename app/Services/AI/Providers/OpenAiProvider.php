<?php

namespace App\Services\AI\Providers;

use App\Services\AI\AiProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiProvider implements AiProviderInterface
{
    protected string $apiKey;
    protected string $model;

    public function __construct(string $apiKey, string $model = 'gpt-3.5-turbo')
    {
        $this->apiKey = $apiKey;
        $this->model = $model;
    }

    public function generate(string $prompt, array $config = []): string
    {
        try {
            $response = Http::withToken($this->apiKey)->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $config['system_prompt'] ?? 'You are a helpful assistant that outputs JSON.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => $config['temperature'] ?? 0.7,
            ]);

            if ($response->failed()) {
                throw new \Exception('OpenAI API Error: ' . $response->body());
            }

            return $response->json()['choices'][0]['message']['content'] ?? '';
        } catch (\Exception $e) {
            Log::error('OpenAI Provider Error: ' . $e->getMessage());
            throw $e;
        }
    }
}
