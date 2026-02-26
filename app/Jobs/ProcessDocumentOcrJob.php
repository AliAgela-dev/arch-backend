<?php

namespace App\Jobs;

use App\Models\DocumentText;
use App\Models\StudentDocument;
use App\Services\OCR\DocumentManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessDocumentOcrJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times to retry the job.
     */
    public int $tries = 3;

    /**
     * Backoff times between retries (seconds).
     */
    public array $backoff = [30, 60, 120];

    /**
     * Timeout for the job (5 minutes for large PDFs).
     */
    public int $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public StudentDocument $studentDocument
    ) {}

    /**
     * Execute the job.
     */
    public function handle(DocumentManager $documentManager): void
    {
        // Get or create DocumentText record
        $documentText = DocumentText::firstOrCreate(
            ['student_document_id' => $this->studentDocument->id],
            ['ocr_status' => DocumentText::STATUS_PENDING]
        );

        // Mark as processing
        $documentText->markAsProcessing();

        try {
            // Get the file path from Spatie Media Library
            $media = $this->studentDocument->getFirstMedia('document');

            if (!$media) {
                throw new \RuntimeException('No media attached to document');
            }

            $filePath = $media->getPath();

            if (!file_exists($filePath)) {
                throw new \RuntimeException("File not found: {$filePath}");
            }

            // Run OCR
            $extractedText = $documentManager->parse($filePath);

            // Save result
            $documentText->markAsCompleted($extractedText);

            Log::info('OCR completed for document', [
                'student_document_id' => $this->studentDocument->id,
                'text_length' => strlen($extractedText),
            ]);

        } catch (\Exception $e) {
            $documentText->markAsFailed($e->getMessage());

            Log::error('OCR failed for document', [
                'student_document_id' => $this->studentDocument->id,
                'error' => $e->getMessage(),
            ]);

            // Re-throw to trigger retry
            throw $e;
        }
    }

    /**
     * Handle job failure after all retries.
     */
    public function failed(\Throwable $exception): void
    {
        $documentText = DocumentText::where('student_document_id', $this->studentDocument->id)->first();
        
        if ($documentText) {
            $documentText->markAsFailed('Max retries exceeded: ' . $exception->getMessage());
        }

        Log::error('OCR job permanently failed', [
            'student_document_id' => $this->studentDocument->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
