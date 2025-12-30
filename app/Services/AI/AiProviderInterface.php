<?php

namespace App\Services\AI;

interface AiProviderInterface
{
    /**
     * Generate content from prompt
     * @param string $prompt
     * @param array $config (optional override config like temperature)
     * @return string
     */
    public function generate(string $prompt, array $config = []): string;
}
