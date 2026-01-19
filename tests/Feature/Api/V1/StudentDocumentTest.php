<?php

namespace Tests\Feature\Api\V1;

use App\Enums\FileStatus;
use App\Models\DocumentType;
use App\Models\Faculty;
use App\Models\Program;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudentDocumentTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Student $student;
    protected DocumentType $documentType;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'super_admin']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('super_admin');
        Sanctum::actingAs($this->admin);

        $faculty = Faculty::factory()->create();
        $program = Program::factory()->create(['faculty_id' => $faculty->id]);
        $this->student = Student::factory()->create([
            'faculty_id' => $faculty->id,
            'program_id' => $program->id,
        ]);
        $this->documentType = DocumentType::factory()->create();
    }

    /*
    |--------------------------------------------------------------------------
    | Index Tests
    |--------------------------------------------------------------------------
    */

    public function test_can_list_student_documents(): void
    {
        StudentDocument::factory()->count(3)->create([
            'student_id' => $this->student->id,
            'document_type_id' => $this->documentType->id,
        ]);

        $response = $this->getJson('/api/v1/student-documents');

        $response->assertOk();
        $this->assertCount(3, $response->json('data'));
    }

    public function test_documents_can_be_filtered_by_student(): void
    {
        $otherFaculty = Faculty::factory()->create();
        $otherProgram = Program::factory()->create(['faculty_id' => $otherFaculty->id]);
        $otherStudent = Student::factory()->create([
            'faculty_id' => $otherFaculty->id,
            'program_id' => $otherProgram->id,
        ]);

        StudentDocument::factory()->create([
            'student_id' => $this->student->id,
            'document_type_id' => $this->documentType->id,
        ]);
        StudentDocument::factory()->create([
            'student_id' => $otherStudent->id,
            'document_type_id' => $this->documentType->id,
        ]);

        $response = $this->getJson("/api/v1/student-documents?filter[student_id]={$this->student->id}");

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_documents_can_be_filtered_by_status(): void
    {
        StudentDocument::factory()->create([
            'student_id' => $this->student->id,
            'document_type_id' => $this->documentType->id,
            'file_status' => FileStatus::COMPLETE,
        ]);
        StudentDocument::factory()->create([
            'student_id' => $this->student->id,
            'document_type_id' => $this->documentType->id,
            'file_status' => FileStatus::DRAFT,
        ]);

        $response = $this->getJson('/api/v1/student-documents?filter[file_status]=complete');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    /*
    |--------------------------------------------------------------------------
    | Store Tests
    |--------------------------------------------------------------------------
    */

    public function test_can_create_student_document(): void
    {
        $data = [
            'student_id' => $this->student->id,
            'document_type_id' => $this->documentType->id,
            'file_status' => 'draft',
            'notes' => 'Test notes',
        ];

        $response = $this->postJson('/api/v1/student-documents', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.student_id', $this->student->id)
            ->assertJsonStructure(['data' => ['file_number']]);

        // Verify file_number was auto-generated
        $this->assertStringStartsWith('DOC-', $response->json('data.file_number'));
    }

    public function test_file_number_is_auto_generated(): void
    {
        $data = [
            'student_id' => $this->student->id,
            'document_type_id' => $this->documentType->id,
            'file_status' => 'draft',
        ];

        $response = $this->postJson('/api/v1/student-documents', $data);

        $response->assertStatus(201);
        $fileNumber = $response->json('data.file_number');
        
        $this->assertNotNull($fileNumber);
        $this->assertMatchesRegularExpression('/^DOC-\d{8}-[A-F0-9]{8}$/', $fileNumber);
    }

    public function test_create_validates_required_fields(): void
    {
        $response = $this->postJson('/api/v1/student-documents', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['student_id', 'document_type_id', 'file_status']);
    }

    /*
    |--------------------------------------------------------------------------
    | Show Tests
    |--------------------------------------------------------------------------
    */

    public function test_can_show_student_document(): void
    {
        $document = StudentDocument::factory()->create([
            'student_id' => $this->student->id,
            'document_type_id' => $this->documentType->id,
        ]);

        $response = $this->getJson("/api/v1/student-documents/{$document->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $document->id);
    }

    public function test_show_includes_relationships(): void
    {
        $document = StudentDocument::factory()->create([
            'student_id' => $this->student->id,
            'document_type_id' => $this->documentType->id,
        ]);

        $response = $this->getJson("/api/v1/student-documents/{$document->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['id', 'student', 'document_type'],
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Update Tests
    |--------------------------------------------------------------------------
    */

    public function test_can_update_student_document(): void
    {
        $document = StudentDocument::factory()->create([
            'student_id' => $this->student->id,
            'document_type_id' => $this->documentType->id,
            'file_status' => FileStatus::DRAFT,
        ]);

        $response = $this->putJson("/api/v1/student-documents/{$document->id}", [
            'file_status' => 'complete',
            'submitted_at' => now()->toDateTimeString(),
        ]);

        $response->assertOk()
            ->assertJsonPath('data.file_status', 'complete');
    }

    public function test_can_update_document_notes(): void
    {
        $document = StudentDocument::factory()->create([
            'student_id' => $this->student->id,
            'document_type_id' => $this->documentType->id,
            'notes' => 'Old notes',
        ]);

        $response = $this->putJson("/api/v1/student-documents/{$document->id}", [
            'notes' => 'Updated notes',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.notes', 'Updated notes');
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Tests
    |--------------------------------------------------------------------------
    */

    public function test_can_delete_student_document(): void
    {
        $document = StudentDocument::factory()->create([
            'student_id' => $this->student->id,
            'document_type_id' => $this->documentType->id,
        ]);

        $response = $this->deleteJson("/api/v1/student-documents/{$document->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('student_documents', ['id' => $document->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | Authorization Tests
    |--------------------------------------------------------------------------
    */

    public function test_unauthenticated_user_cannot_access(): void
    {
        $this->app['auth']->forgetGuards();

        $response = $this->getJson('/api/v1/student-documents');

        $response->assertStatus(401);
    }
}
