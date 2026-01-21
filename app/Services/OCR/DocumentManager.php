<?php

namespace App\Services\OCR;

use App\Contracts\DocumentParserInterface;
use RuntimeException;

class DocumentManager
{
    /**
     * @var array<DocumentParserInterface>
     */
    protected array $parsers = [];

    /**
     * Register a parser.
     */
    public function registerParser(DocumentParserInterface $parser): self
    {
        $this->parsers[] = $parser;
        return $this;
    }

    /**
     * Parse a document and extract text.
     *
     * @param string $path Absolute path to the file
     * @return string Extracted text
     * @throws RuntimeException If no parser supports the file type
     */
    public function parse(string $path): string
    {
        if (!file_exists($path)) {
            throw new RuntimeException("File not found: {$path}");
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $parser = $this->getParserFor($extension);

        if (!$parser) {
            throw new RuntimeException("No parser available for extension: .{$extension}");
        }

        return $parser->parse($path);
    }

    /**
     * Get the parser for a specific file extension.
     */
    public function getParserFor(string $extension): ?DocumentParserInterface
    {
        $extension = strtolower($extension);

        foreach ($this->parsers as $parser) {
            if ($parser->supports($extension)) {
                return $parser;
            }
        }

        return null;
    }

    /**
     * Check if any parser supports the given extension.
     */
    public function canParse(string $extension): bool
    {
        return $this->getParserFor($extension) !== null;
    }

    /**
     * Get all supported extensions across all parsers.
     *
     * @return array<string>
     */
    public function getSupportedExtensions(): array
    {
        $extensions = [];

        foreach ($this->parsers as $parser) {
            $extensions = array_merge($extensions, $parser->getSupportedExtensions());
        }

        return array_unique($extensions);
    }

    /**
     * Get all registered parsers.
     *
     * @return array<DocumentParserInterface>
     */
    public function getParsers(): array
    {
        return $this->parsers;
    }
}
