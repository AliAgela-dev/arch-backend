<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use App\Models\Faculty;
use App\Models\Program;
use App\Models\Student;
use App\Models\DocumentType;
use App\Models\StudentDocument;
use App\Models\Borrowing;
use App\Enums\UserRole;
use App\Enums\BorrowingStatus;
use App\Enums\StudentStatus;
use App\Enums\LocationStatus;
use Illuminate\Support\Facades\Hash;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Populating Test Data for Dashboard ===\n\n";

try {
    // Get faculty staff user
    $facultyUser = User::where('email', 'faculty@staff.com')->first();
    if (!$facultyUser) {
        echo "Faculty staff user not found. Please create one first.\n";
        exit(1);
    }

    // Create Faculty
    echo "1. Creating Faculty...\n";
    $faculty = Faculty::firstOrCreate([
        'code' => 'CS'
    ], [
        'name_en' => 'Computer Science Faculty',
        'name_ar' => 'كلية علوم الحاسوب'
    ]);

    // Create Program
    $program = Program::firstOrCreate([
        'code' => 'CS_PROG',
        'faculty_id' => $faculty->id
    ], [
        'name_en' => 'Computer Science Program',
        'name_ar' => 'برنامج علوم الحاسوب'
    ]);

    // Associate user with faculty
    $facultyUser->faculties()->syncWithoutDetaching([$faculty->id]);
    echo "Faculty and program created/assigned\n";

    // Create Document Types
    echo "\n2. Creating Document Types...\n";
    $documentTypes = [
        ['name' => 'Transcript', 'description' => 'Academic transcript', 'is_required' => true, 'status' => 'active'],
        ['name' => 'Degree Certificate', 'description' => 'Graduation certificate', 'is_required' => true, 'status' => 'active'],
        ['name' => 'ID Card', 'description' => 'Student identification card', 'is_required' => false, 'status' => 'active'],
        ['name' => 'Course Registration', 'description' => 'Course registration form', 'is_required' => false, 'status' => 'active'],
    ];

    foreach ($documentTypes as $typeData) {
        DocumentType::firstOrCreate(['name' => $typeData['name']], $typeData);
    }
    echo "Document types created\n";

    // Create Students and Documents
    echo "\n3. Creating Students and Documents...\n";
    $students = [];
    $documentTypes = DocumentType::all();

    for ($i = 1; $i <= 8; $i++) {
        $student = Student::firstOrCreate([
            'student_number' => 'STU' . str_pad($i, 3, '0', STR_PAD_LEFT)
        ], [
            'name' => "Student {$i}",
            'nationality' => 'Test Nationality',
            'email' => "student{$i}@test.com",
            'phone' => '+1234567890',
            'faculty_id' => $faculty->id,
            'program_id' => $program->id,
            'enrollment_year' => 2023,
            'graduation_year' => 2027,
            'student_status' => StudentStatus::ACTIVE->value,
            'location_status' => LocationStatus::IN_LOCATION->value
        ]);
        
        $students[] = $student;
        
        // Create 2-3 documents per student
        for ($j = 1; $j <= rand(2, 3); $j++) {
            $docType = $documentTypes->random();
            StudentDocument::firstOrCreate([
                'file_number' => 'FILE' . str_pad($i * 10 + $j, 4, '0', STR_PAD_LEFT)
            ], [
                'student_id' => $student->id,
                'document_type_id' => $docType->id,
                'title' => "{$docType->name} - Student {$i}",
                'code' => "DOC{$i}{$j}",
                'status' => 'active'
            ]);
        }
    }
    echo "Created " . count($students) . " students with documents\n";

    // Create Borrowings with different statuses
    echo "\n4. Creating Borrowings...\n";
    $documents = StudentDocument::all();
    
    // Pending Requests (5)
    for ($i = 0; $i < 5; $i++) {
        $document = $documents->random();
        Borrowing::create([
            'student_document_id' => $document->id,
            'user_id' => $facultyUser->id,
            'status' => BorrowingStatus::PENDING->value,
            'due_date' => now()->addDays(rand(5, 15)),
        ]);
    }

    // Approved Borrowings (3)
    for ($i = 0; $i < 3; $i++) {
        $document = $documents->random();
        Borrowing::create([
            'student_document_id' => $document->id,
            'user_id' => $facultyUser->id,
            'status' => BorrowingStatus::APPROVED->value,
            'due_date' => now()->addDays(rand(5, 15)),
        ]);
    }

    // Active Borrowings (4)
    for ($i = 0; $i < 4; $i++) {
        $document = $documents->random();
        Borrowing::create([
            'student_document_id' => $document->id,
            'user_id' => $facultyUser->id,
            'status' => BorrowingStatus::BORROWED->value,
            'due_date' => now()->addDays(rand(5, 15)),
            'borrowed_at' => now()->subDays(rand(1, 10)),
        ]);
    }

    // Overdue Borrowings (2)
    for ($i = 0; $i < 2; $i++) {
        $document = $documents->random();
        Borrowing::create([
            'student_document_id' => $document->id,
            'user_id' => $facultyUser->id,
            'status' => BorrowingStatus::BORROWED->value,
            'due_date' => now()->subDays(rand(1, 10)), // Past due date
            'borrowed_at' => now()->subDays(rand(15, 30)),
        ]);
    }

    // Returned Borrowings (3)
    for ($i = 0; $i < 3; $i++) {
        $document = $documents->random();
        Borrowing::create([
            'student_document_id' => $document->id,
            'user_id' => $facultyUser->id,
            'status' => BorrowingStatus::RETURNED->value,
            'due_date' => now()->subDays(rand(5, 10)),
            'borrowed_at' => now()->subDays(rand(15, 30)),
            'returned_at' => now()->subDays(rand(1, 5)),
        ]);
    }

    echo "Created borrowings:\n";
    echo "  - Pending requests: 5\n";
    echo "  - Approved borrowings: 3\n";
    echo "  - Active borrowings: 4\n";
    echo "  - Overdue borrowings: 2\n";
    echo "  - Returned borrowings: 3\n";

    // Display Summary
    echo "\n=== Test Data Summary ===\n";
    echo "Faculty Staff User: {$facultyUser->email}\n";
    echo "Faculty: {$faculty->name_en}\n";
    echo "Students: " . Student::count() . "\n";
    echo "Documents: " . StudentDocument::count() . "\n";
    echo "Total Borrowings: " . Borrowing::count() . "\n";

    echo "\n=== Expected Dashboard Statistics ===\n";
    echo "Total Faculty Files: " . StudentDocument::count() . "\n";
    echo "Active Borrowings by User: " . Borrowing::where('user_id', $facultyUser->id)
        ->whereIn('status', [BorrowingStatus::APPROVED->value, BorrowingStatus::BORROWED->value])->count() . "\n";
    echo "Active Requests: " . Borrowing::where('status', BorrowingStatus::PENDING->value)->count() . "\n";
    echo "Overdue Files: " . Borrowing::where('status', BorrowingStatus::BORROWED->value)
        ->where('due_date', '<', now())->count() . "\n";

    echo "\nTest data populated successfully!\n";
    echo "You can now test the dashboard API to see real statistics.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
