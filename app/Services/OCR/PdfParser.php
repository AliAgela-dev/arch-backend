<?php

namespace App\Services\OCR;

use App\Contracts\DocumentParserInterface;
use RuntimeException;

class PdfParser implements DocumentParserInterface
{
    protected string $pdftotextPath;
    protected string $tempDirectory;

    public function __construct()
    {
        $this->pdftotextPath = config('services.document_parsing.pdftotext_path');
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

        // Try pdftotext extraction first
        $text = $this->extractWithPdfToText($path);

        // Strip whitespace AND control characters (like form feed \f)
        $cleanText = preg_replace('/[\x00-\x1F\x7F\s]+/', '', $text);
        
        // If empty after cleaning, it's a scanned PDF - use OCR
        if (empty($cleanText)) {
            return $this->handleScannedPdf($path);
        }

        return trim($text);
    }

    /**
     * Extract text using pdftotext binary.
     */
    protected function extractWithPdfToText(string $path): string
    {
        if (!file_exists($this->pdftotextPath)) {
            throw new RuntimeException("pdftotext binary not found at: {$this->pdftotextPath}");
        }

        $outputFile = $this->tempDirectory . '/' . uniqid('pdf_') . '.txt';

        // Build pdftotext command
        $command = sprintf(
            '"%s" -enc UTF-8 "%s" "%s" 2>&1',
            $this->pdftotextPath,
            $path,
            $outputFile
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            // pdftotext failed, fall through to OCR
            return '';
        }

        if (!file_exists($outputFile)) {
            return '';
        }

        $text = file_get_contents($outputFile);

        // Cleanup
        @unlink($outputFile);

        return $text;
    }

    /**
     * Handle scanned PDFs using pdftoppm + Tesseract OCR pipeline.
     * 
     * 1. Convert PDF pages to PNG images using pdftoppm
     * 2. Run OCR on each image using Tesseract
     * 3. Combine results and cleanup
     */
    protected function handleScannedPdf(string $path): string
    {
        // 1. Setup paths from config
        $pdftoppmPath = config('services.document_parsing.pdftoppm_path');
        $tesseractPath = config('services.document_parsing.tesseract_path');
        
        if (!file_exists($pdftoppmPath)) {
            throw new RuntimeException("pdftoppm binary not found at: {$pdftoppmPath}");
        }
        
        if (!file_exists($tesseractPath)) {
            throw new RuntimeException("Tesseract binary not found at: {$tesseractPath}");
        }
        
        // Create a unique subfolder for this specific PDF's pages
        $jobId = uniqid('pages_');
        $outputFolder = $this->tempDirectory . '/' . $jobId;
        
        if (!is_dir($outputFolder)) {
            mkdir($outputFolder, 0755, true);
        }

        // 2. Convert PDF pages to PNG images
        // -png: output format
        // -r 300: high resolution for better OCR accuracy
        $pdfToPpmCommand = sprintf(
            '"%s" -png -r 300 "%s" "%s/page"',
            $pdftoppmPath,
            $path,
            $outputFolder
        );
        
        exec($pdfToPpmCommand);

        // 3. Loop through the generated images and OCR them
        $allImages = glob("$outputFolder/*.png");
        $fullText = "";

        foreach ($allImages as $image) {
            $outputBase = $image . '_text';
            
            // Run Tesseract on the page
            $ocrCommand = sprintf(
                '"%s" "%s" "%s" -l eng', 
                $tesseractPath,
                $image,
                $outputBase
            );
            
            exec($ocrCommand);
            
            $txtFile = $outputBase . '.txt';
            if (file_exists($txtFile)) {
                $fullText .= file_get_contents($txtFile) . "\n";
                @unlink($txtFile); // Clean up text file
            }
            @unlink($image); // Clean up image file
        }

        // 4. Final Cleanup
        @rmdir($outputFolder);

        return trim($fullText);
    }

    public function supports(string $extension): bool
    {
        return in_array(strtolower($extension), $this->getSupportedExtensions(), true);
    }

    public function getSupportedExtensions(): array
    {
        return ['pdf'];
    }
}
