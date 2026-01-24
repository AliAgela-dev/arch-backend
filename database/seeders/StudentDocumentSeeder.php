<?php

namespace Database\Seeders;

use App\Enums\FileStatus;
use App\Models\DocumentType;
use App\Models\Student;
use App\Models\StudentDocument;
use Illuminate\Database\Seeder;

class StudentDocumentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $students = Student::all();
        $documentTypes = DocumentType::all();

        if ($students->isEmpty() || $documentTypes->isEmpty()) {
            $this->command->warn('No students or document types found. Please run StudentSeeder and DocumentTypeSeeder first.');
            return;
        }

        // Create documents for each student
        foreach ($students as $student) {
            // Each student gets at least some documents
            $documentsToCreate = $documentTypes->random(rand(2, $documentTypes->count()));

            foreach ($documentsToCreate as $documentType) {
                // Use factory states to vary document status
                $status = rand(0, 2);
                
                if ($status === 0) {
                    StudentDocument::factory()->create([
                        'student_id' => $student->id,
                        'document_type_id' => $documentType->id,
                    ]);
                } elseif ($status === 1) {
                    StudentDocument::factory()->incomplete()->create([
                        'student_id' => $student->id,
                        'document_type_id' => $documentType->id,
                    ]);
                } else {
                    StudentDocument::factory()->complete()->create([
                        'student_id' => $student->id,
                        'document_type_id' => $documentType->id,
                    ]);
                }
            }
        }
    }
}
