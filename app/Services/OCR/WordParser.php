<?php

namespace App\Services\OCR;

use App\Contracts\DocumentParserInterface;
use PhpOffice\PhpWord\IOFactory;
use RuntimeException;

class WordParser implements DocumentParserInterface
{
    public function parse(string $path): string
    {
        if (!file_exists($path)) {
            throw new RuntimeException("File not found: {$path}");
        }

        try {
            $phpWord = IOFactory::load($path);
            $text = '';

            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    $text .= $this->extractTextFromElement($element);
                }
            }

            return trim($text);
        } catch (\Exception $e) {
            throw new RuntimeException("Failed to parse Word document: " . $e->getMessage());
        }
    }

    /**
     * Recursively extract text from PHPWord elements.
     */
    protected function extractTextFromElement($element): string
    {
        $text = '';

        if (method_exists($element, 'getText')) {
            $elementText = $element->getText();
            if (is_string($elementText)) {
                $text .= $elementText . ' ';
            } elseif (is_object($elementText) && method_exists($elementText, 'getText')) {
                $text .= $elementText->getText() . ' ';
            }
        }

        if (method_exists($element, 'getElements')) {
            foreach ($element->getElements() as $childElement) {
                $text .= $this->extractTextFromElement($childElement);
            }
        }

        // Handle tables
        if ($element instanceof \PhpOffice\PhpWord\Element\Table) {
            foreach ($element->getRows() as $row) {
                foreach ($row->getCells() as $cell) {
                    foreach ($cell->getElements() as $cellElement) {
                        $text .= $this->extractTextFromElement($cellElement);
                    }
                }
                $text .= "\n";
            }
        }

        return $text;
    }

    public function supports(string $extension): bool
    {
        return in_array(strtolower($extension), $this->getSupportedExtensions(), true);
    }

    public function getSupportedExtensions(): array
    {
        return ['docx', 'doc', 'odt', 'rtf'];
    }
}
