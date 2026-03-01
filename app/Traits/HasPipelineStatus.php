<?php

namespace App\Traits;

use App\Enums\Pipeline\PipelineStatus;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

trait HasPipelineStatus
{
    /**
     * Transition the pipeline to a new status, validating the transition is legal.
     *
     * @throws InvalidArgumentException If the transition is not allowed
     */
    public function transitionTo(PipelineStatus $newStatus): void
    {
        $currentStatus = $this->pipeline_status;

        if (! $currentStatus->canTransitionTo($newStatus)) {
            throw new InvalidArgumentException(
                "Cannot transition from [{$currentStatus->value}] to [{$newStatus->value}]."
            );
        }

        $this->pipeline_status = $newStatus;

        // Clear error when transitioning out of Failed
        if ($currentStatus === PipelineStatus::Failed) {
            $this->pipeline_error = null;
        }

        $this->save();
    }

    /**
     * Mark the pipeline as failed. Bypasses transition validation — any state can go to Failed.
     */
    public function markFailed(string $error): void
    {
        $this->pipeline_status = PipelineStatus::Failed;
        $this->pipeline_error = $error;
        $this->save();
    }

    /**
     * Determine the pipeline resume point based on existing data.
     */
    public function getResumePoint(): PipelineStatus
    {
        $hasContent = $this->documentContents()->exists();
        $hasRefinement = $this->refinement()
            ->where('refinement_status', 'completed')
            ->exists();

        if (! $hasContent) {
            return PipelineStatus::OcrProcessing;
        }

        if (! $hasRefinement) {
            return PipelineStatus::Refining;
        }

        return PipelineStatus::Embedding;
    }

    /**
     * Check if the document pipeline can be retried.
     */
    public function isRetryable(): bool
    {
        return in_array($this->pipeline_status, [
            PipelineStatus::Failed,
            PipelineStatus::OcrCompleted,
        ], true);
    }

    /**
     * Scope: filter by pipeline status.
     */
    public function scopeByPipelineStatus(Builder $query, PipelineStatus $status): Builder
    {
        return $query->where('pipeline_status', $status->value);
    }

    /**
     * Scope: only failed documents.
     */
    public function scopePipelineFailed(Builder $query): Builder
    {
        return $query->where('pipeline_status', PipelineStatus::Failed->value);
    }

    /**
     * Scope: only completed documents.
     */
    public function scopePipelineCompleted(Builder $query): Builder
    {
        return $query->where('pipeline_status', PipelineStatus::Completed->value);
    }

    /**
     * Scope: documents currently being processed.
     */
    public function scopePipelineProcessing(Builder $query): Builder
    {
        return $query->whereIn('pipeline_status', [
            PipelineStatus::OcrProcessing->value,
            PipelineStatus::Refining->value,
            PipelineStatus::Embedding->value,
        ]);
    }
}
