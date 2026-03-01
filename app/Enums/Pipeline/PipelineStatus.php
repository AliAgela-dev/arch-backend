<?php

namespace App\Enums\Pipeline;

enum PipelineStatus: string
{
    case Pending = 'pending';
    case OcrProcessing = 'ocr_processing';
    case OcrCompleted = 'ocr_completed';
    case Refining = 'refining';
    case Refined = 'refined';
    case Embedding = 'embedding';
    case Completed = 'completed';
    case Failed = 'failed';

    /**
     * Check if a transition to the given status is allowed.
     */
    public function canTransitionTo(self $newStatus): bool
    {
        return in_array($newStatus, $this->allowedTransitions(), true);
    }

    /**
     * Get the list of allowed transitions from this status.
     *
     * @return array<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::OcrProcessing],
            self::OcrProcessing => [self::OcrCompleted, self::Failed],
            self::OcrCompleted => [self::Refining],
            self::Refining => [self::Refined, self::Failed],
            self::Refined => [self::Embedding],
            self::Embedding => [self::Completed, self::Failed],
            self::Failed => [self::Pending],
            self::Completed => [],
        };
    }

    /**
     * Arabic display label for the frontend.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'قيد الانتظار',
            self::OcrProcessing => 'جاري استخراج النص',
            self::OcrCompleted => 'اكتمل استخراج النص',
            self::Refining => 'جاري التحليل الذكي',
            self::Refined => 'اكتمل التحليل',
            self::Embedding => 'جاري إنشاء التضمينات',
            self::Completed => 'مكتمل',
            self::Failed => 'فشل',
        };
    }
}
