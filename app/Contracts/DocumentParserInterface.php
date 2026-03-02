<?php

namespace App\Contracts;

interface DocumentParserInterface
{
    /**
     * Parse a document and extract text content per page.
     *
     * @param string $path Absolute path to the file
     * @return array<int, string> Array of [page_number => text_content], 1-indexed
     * @throws \RuntimeException If parsing fails
     */
    public function parse(string $path): array;

    /**
     * Check if this parser supports the given file extension.
     *
     * @param string $extension File extension (without dot)
     * @return bool
     */
    public function supports(string $extension): bool;

    /**
     * Get the list of supported file extensions.
     *
     * @return array<string>
     */
    public function getSupportedExtensions(): array;
}
