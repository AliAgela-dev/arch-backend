<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'student' => new StudentResource($this->whenLoaded('student')),
            'document_type_id' => $this->document_type_id,
            'document_type' => new DocumentTypeResource($this->whenLoaded('documentType')),
            'file_number' => $this->file_number,
            'file_status' => $this->file_status,
            'notes' => $this->notes,
            'submitted_at' => $this->submitted_at,
            'file_url' => $this->getFirstMediaUrl('document'),
            'file_name' => $this->getFirstMedia('document')?->file_name,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
