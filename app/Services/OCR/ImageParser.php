<?php

namespace App\Services\OCR;

use App\Contracts\DocumentParserInterface;
use RuntimeException;

class ImageParser implements DocumentParserInterface
{
    protected string $tesseractPath;
    protected string $tempDirectory;

    public function __construct()
    {
        $this->tesseractPath = config('services.document_parsing.tesseract_path');
        $this->tempDirectory = config('services.document_parsing.temp_directory', storage_path('app/ocr_temp'));
        
        if (!is_dir($this->tempDirectory)) {
            mkdir($this->tempDirectory, 0755, true);
        }
    }

    public function parse(string $path): string
    {
        if (!file_exists($path)) {
            throw new RuntimeException("File not found: {$path}");
        }

        if (!file_exists($this->tesseractPath)) {
            throw new RuntimeException("Tesseract binary not found at: {$this->tesseractPath}");
        }

        $outputBase = $this->tempDirectory . '/' . uniqid('ocr_');
        $outputFile = $outputBase . '.txt';

        // Build Tesseract command
        $command = sprintf(
            '"%s" "%s" "%s" -l eng 2>&1',
            $this->tesseractPath,
            $path,
            $outputBase
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new RuntimeException("Tesseract OCR failed: " . implode("\n", $output));
        }

        if (!file_exists($outputFile)) {
            throw new RuntimeException("Tesseract did not produce output file");
        }

        $text = file_get_contents($outputFile);
        
        // Cleanup
        @unlink($outputFile);

        return trim($text);
    }

    public function supports(string $extension): bool
    {
        return in_array(strtolower($extension), $this->getSupportedExtensions(), true);
    }

    public function getSupportedExtensions(): array
    {
        return ['png', 'jpg', 'jpeg', 'tiff', 'tif', 'bmp', 'gif'];
    }
}
