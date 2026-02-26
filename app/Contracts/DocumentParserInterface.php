<?php

namespace App\Contracts;

interface DocumentParserInterface
{
    /**
     * Parse a document and extract its text content.
     *
     * @param string $path Absolute path to the file
     * @return string Extracted text content
     * @throws \RuntimeException If parsing fails
     */
    public function parse(string $path): string;

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
