<?php

namespace Tests\Feature\Api\V1;

use App\Enums\BorrowingStatus;
use App\Models\Borrowing;
use App\Models\Faculty;
use App\Models\Program;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $regularUser;
    protected Faculty $faculty;
    protected Program $program;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'super_admin']);
        Role::create(['name' => 'archivist']);
        Role::create(['name' => 'faculty_staff']);

        // Create admin user
        $this->admin = User::factory()->create();
        $this->admin->assignRole('super_admin');

        // Create regular user (faculty_staff)
        $this->regularUser = User::factory()->create();
        $this->regularUser->assignRole('faculty_staff');

        // Create faculty and program for test data
        $this->faculty = Faculty::factory()->create([
            'name_en' => 'Medicine',
            'name_ar' => 'الطب',
        ]);
        $this->program = Program::factory()->create(['faculty_id' => $this->faculty->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | Access Control Tests
    |--------------------------------------------------------------------------
    */

    public function test_super_admin_can_access_dashboard(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/dashboard');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'summary' => ['total_archive', 'active_borrows'],
                    'storage' => ['used_bytes', 'total_bytes', 'percentage', 'used_formatted', 'total_formatted'],
                    'faculty_storage_distribution',
                    'warnings',
                ],
            ]);
    }

    public function test_archivist_can_access_dashboard(): void
    {
        $archivist = User::factory()->create();
        $archivist->assignRole('archivist');
        Sanctum::actingAs($archivist);

        $response = $this->getJson('/api/v1/dashboard');

        $response->assertOk();
    }

    public function test_faculty_staff_cannot_access_dashboard(): void
    {
        Sanctum::actingAs($this->regularUser);

        $response = $this->getJson('/api/v1/dashboard');

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_access_dashboard(): void
    {
        $response = $this->getJson('/api/v1/dashboard');

        $response->assertUnauthorized();
    }

    /*
    |--------------------------------------------------------------------------
    | Summary Statistics Tests
    |--------------------------------------------------------------------------
    */

    public function test_dashboard_returns_correct_total_archive_count(): void
    {
        Sanctum::actingAs($this->admin);

        $student = Student::factory()->create([
            'faculty_id' => $this->faculty->id,
            'program_id' => $this->program->id,
        ]);

        $documentType = DocumentType::factory()->create();
        StudentDocument::factory()->count(5)->create([
            'student_id' => $student->id,
            'document_type_id' => $documentType->id,
        ]);

        $response = $this->getJson('/api/v1/dashboard');

        $response->assertOk()
            ->assertJsonPath('data.summary.total_archive', 5);
    }

    public function test_dashboard_returns_correct_active_borrows_count(): void
    {
        Sanctum::actingAs($this->admin);

        $student = Student::factory()->create([
            'faculty_id' => $this->faculty->id,
            'program_id' => $this->program->id,
        ]);
        $documentType = DocumentType::factory()->create();
        $document = StudentDocument::factory()->create([
            'student_id' => $student->id,
            'document_type_id' => $documentType->id,
        ]);

        // Create borrowings with different statuses
        Borrowing::factory()->create([
            'user_id' => $this->admin->id,
            'student_document_id' => $document->id,
            'status' => BorrowingStatus::APPROVED,
        ]);
        Borrowing::factory()->create([
            'user_id' => $this->admin->id,
            'student_document_id' => $document->id,
            'status' => BorrowingStatus::BORROWED,
        ]);
        Borrowing::factory()->create([
            'user_id' => $this->admin->id,
            'student_document_id' => $document->id,
            'status' => BorrowingStatus::PENDING, // Should not be counted
        ]);
        Borrowing::factory()->create([
            'user_id' => $this->admin->id,
            'student_document_id' => $document->id,
            'status' => BorrowingStatus::RETURNED, // Should not be counted
        ]);

        $response = $this->getJson('/api/v1/dashboard');

        $response->assertOk()
            ->assertJsonPath('data.summary.active_borrows', 2);
    }

    /*
    |--------------------------------------------------------------------------
    | Storage Statistics Tests
    |--------------------------------------------------------------------------
    */

    public function test_dashboard_returns_storage_statistics(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/dashboard');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'storage' => [
                        'used_bytes',
                        'total_bytes',
                        'percentage',
                        'used_formatted',
                        'total_formatted',
                    ],
                ],
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Faculty Distribution Tests
    |--------------------------------------------------------------------------
    */

    public function test_dashboard_returns_all_faculties_in_distribution(): void
    {
        Sanctum::actingAs($this->admin);

        // Create additional faculties
        Faculty::factory()->create(['name_en' => 'Engineering']);
        Faculty::factory()->create(['name_en' => 'Law']);

        $response = $this->getJson('/api/v1/dashboard');

        $response->assertOk();
        
        $distribution = $response->json('data.faculty_storage_distribution');
        $this->assertCount(3, $distribution); // 3 faculties total
    }

    /*
    |--------------------------------------------------------------------------
    | Warnings Tests
    |--------------------------------------------------------------------------
    */

    public function test_dashboard_returns_warnings_array(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/dashboard');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['warnings'],
            ]);
        
        $this->assertIsArray($response->json('data.warnings'));
    }
}
