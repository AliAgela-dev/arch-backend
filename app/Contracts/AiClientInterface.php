<?php

namespace App\Contracts;

interface AiClientInterface
{
    /**
     * Send a text generation request to the AI provider.
     *
     * @param string $prompt The user prompt
     * @param string|null $systemInstruction Optional system instruction
     * @return array The raw parsed JSON response from the API
     * @throws \RuntimeException If the API call fails
     */
    public function generateContent(string $prompt, ?string $systemInstruction = null): array;
}
