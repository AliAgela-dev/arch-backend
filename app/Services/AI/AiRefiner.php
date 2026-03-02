<?php

namespace App\Services\AI;

use App\Contracts\AiClientInterface;
use App\DataTransferObjects\RefinementData;
use RuntimeException;

class AiRefiner
{
    private string $lastRawResponse = '';

    public function __construct(
        private readonly AiClientInterface $client,
    ) {}

    /**
     * Refine raw OCR text into structured data via the AI client.
     *
     * Accepts a string, returns a DTO. Knows nothing about Eloquent models.
     *
     * @param  string  $rawText  The raw OCR-extracted text
     * @param  string|null  $detectedType  Optional DocumentType.name for type-specific hints
     */
    public function refine(string $rawText, ?string $detectedType = null): RefinementData
    {
        $systemPrompt = config('ai-prompts.system');
        $prompt = config('ai-prompts.generic');

        $prompt = str_replace('{text}', $rawText, $prompt);

        if ($detectedType !== null) {
            $hint = config("ai-prompts.type_hints.{$detectedType}");
            if ($hint) {
                $prompt .= "\n\n".$hint;
            }
        }

        $response = $this->client->generateContent($prompt, $systemPrompt);

        $text = $response['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if ($text === null) {
            throw new RuntimeException(
                'Gemini response missing expected structure (candidates[0].content.parts[0].text). Raw: '
                .json_encode($response)
            );
        }

        $this->lastRawResponse = $text;

        $parsed = json_decode($text, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException(
                'Failed to parse Gemini JSON response: '.json_last_error_msg().'. Raw: '.$text
            );
        }

        return RefinementData::fromArray($parsed);
    }

    /**
     * Get the last raw API response text for storage/debugging.
     */
    public function getRawResponse(): string
    {
        return $this->lastRawResponse;
    }
}
