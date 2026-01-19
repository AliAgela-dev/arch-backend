<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_number' => $this->student_number,
            'name' => $this->name,
            'nationality' => $this->nationality,
            'email' => $this->email,
            'phone' => $this->phone,
            'faculty_id' => $this->faculty_id,
            'faculty' => new FacultyResource($this->whenLoaded('faculty')),
            'program_id' => $this->program_id,
            'program' => new ProgramResource($this->whenLoaded('program')),
            'drawer_id' => $this->drawer_id,
            'drawer' => new DrawerResource($this->whenLoaded('drawer')),
            'enrollment_year' => $this->enrollment_year,
            'graduation_year' => $this->graduation_year,
            'student_status' => $this->student_status,
            'location_status' => $this->location_status,
            'documents' => StudentDocumentResource::collection($this->whenLoaded('documents')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
