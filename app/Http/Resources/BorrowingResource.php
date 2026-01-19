<?php

namespace App\Http\Resources;

use App\Http\Resources\User\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BorrowingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            
            // Relationships
            'user' => new UserResource($this->whenLoaded('user')),
            'student_document' => new StudentDocumentResource($this->whenLoaded('studentDocument')),
            
            // Timestamps
            'requested_at' => $this->requested_at,
            'approved_at' => $this->approved_at,
            'rejected_at' => $this->rejected_at,
            'borrowed_at' => $this->borrowed_at,
            'due_date' => $this->due_date,
            'returned_at' => $this->returned_at,
            
            // Notes
            'notes' => $this->notes,
            'admin_notes' => $this->admin_notes,
            'rejection_reason' => $this->rejection_reason,
            
            // Computed fields
            'is_overdue' => $this->isOverdue(),
            'days_until_due' => $this->daysUntilDue(),
            'days_overdue' => $this->daysOverdue(),
            
            // Standard timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Get human-readable status label.
     */
    protected function getStatusLabel(): string
    {
        return match($this->status->value) {
            'pending' => 'Pending Approval',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'borrowed' => 'Currently Borrowed',
            'returned' => 'Returned',
            'overdue' => 'Overdue',
            default => ucfirst($this->status->value),
        };
    }
}
