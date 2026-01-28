<?php

namespace Database\Seeders;

use App\Enums\FileStatus;
use App\Enums\UserRole;
use App\Models\Borrowing;
use App\Models\StudentDocument;
use App\Models\User;
use Illuminate\Database\Seeder;

class BorrowingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Prefer users with faculty_staff role, but use any user if available
        $users = User::role(UserRole::faculty_staff->value)->get();
        
        if ($users->isEmpty()) {
            $users = User::all();
        }

        if ($users->isEmpty()) {
            $this->command->warn('No users found. Please create users first.');
            return;
        }

        // Get student documents that are complete (valid for borrowing)
        $documents = StudentDocument::where('file_status', FileStatus::COMPLETE)
            ->get();

        if ($documents->isEmpty()) {
            $this->command->warn('No complete student documents found. Please run StudentDocumentSeeder first.');
            return;
        }

        // Create borrowings for a subset of documents
        $documentsToBorrow = $documents->random(min(20, $documents->count()));

        foreach ($documentsToBorrow as $document) {
            // Check if document is already borrowed
            if ($document->isBorrowed()) {
                continue;
            }

            // Randomly select a status for the borrowing
            $statusChoice = rand(0, 4);

            if ($statusChoice === 0) {
                Borrowing::factory()->pending()->create([
                    'user_id' => $users->random()->id,
                    'student_document_id' => $document->id,
                ]);
            } elseif ($statusChoice === 1) {
                Borrowing::factory()->approved()->create([
                    'user_id' => $users->random()->id,
                    'student_document_id' => $document->id,
                ]);
            } elseif ($statusChoice === 2) {
                Borrowing::factory()->rejected()->create([
                    'user_id' => $users->random()->id,
                    'student_document_id' => $document->id,
                ]);
            } elseif ($statusChoice === 3) {
                Borrowing::factory()->borrowed()->create([
                    'user_id' => $users->random()->id,
                    'student_document_id' => $document->id,
                ]);
            } else {
                Borrowing::factory()->returned()->create([
                    'user_id' => $users->random()->id,
                    'student_document_id' => $document->id,
                ]);
            }
        }
    }
}
