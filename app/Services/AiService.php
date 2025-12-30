<?php

namespace App\Services;

use App\Models\Setting;
use App\Services\AI\AiProviderInterface;
use App\Services\AI\Providers\GeminiProvider;
use App\Services\AI\Providers\OpenAiProvider;
use Illuminate\Support\Facades\Log;

class AiService
{
    protected AiProviderInterface $provider;
    protected string $providerName;

    public function __construct()
    {
        // Load from DB settings
        $this->providerName = Setting::where('key', 'ai_provider')->value('value') ?? 'gemini';

        $this->provider = $this->resolveProvider($this->providerName);
    }

    /**
     * Resolve provider instance
     */
    protected function resolveProvider(string $provider): AiProviderInterface
    {
        if ($provider === 'openai') {
            $apiKey = Setting::where('key', 'openai_api_key')->value('value') ?? config('services.openai.api_key');
            $model = Setting::where('key', 'openai_model')->value('value') ?? 'gpt-3.5-turbo';

            if (empty($apiKey)) {
                throw new \Exception('API Key OpenAI belum dikonfigurasi.');
            }

            return new OpenAiProvider($apiKey, $model);
        } else {
            // Default Gemini
            $apiKey = Setting::where('key', 'gemini_api_key')->value('value') ?? config('services.gemini.api_key', env('GEMINI_API_KEY'));
            $model = Setting::where('key', 'gemini_model')->value('value') ?? 'gemini-1.5-flash';

            if (empty($apiKey)) {
                throw new \Exception('API Key Gemini belum dikonfigurasi.');
            }

            return new GeminiProvider($apiKey, $model);
        }
    }

    /**
     * Generate content
     */
    public function generate(string $prompt, array $config = []): string
    {
        return $this->provider->generate($prompt, $config);
    }
}
