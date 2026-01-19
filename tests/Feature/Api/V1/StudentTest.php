<?php

namespace Tests\Feature\Api\V1;

use App\Enums\LocationStatus;
use App\Enums\StudentStatus;
use App\Models\Faculty;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudentTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Faculty $faculty;
    protected Program $program;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'super_admin']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('super_admin');
        Sanctum::actingAs($this->admin);

        $this->faculty = Faculty::factory()->create();
        $this->program = Program::factory()->create(['faculty_id' => $this->faculty->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | Index Tests
    |--------------------------------------------------------------------------
    */

    public function test_can_list_students(): void
    {
        Student::factory()->count(3)->create([
            'faculty_id' => $this->faculty->id,
            'program_id' => $this->program->id,
        ]);

        $response = $this->getJson('/api/v1/students');

        $response->assertOk();
        $this->assertCount(3, $response->json('data'));
    }

    public function test_students_can_be_filtered_by_name(): void
    {
        Student::factory()->create([
            'name' => 'Ahmed Mohamed',
            'faculty_id' => $this->faculty->id,
            'program_id' => $this->program->id,
        ]);
        Student::factory()->create([
            'name' => 'Ali Hassan',
            'faculty_id' => $this->faculty->id,
            'program_id' => $this->program->id,
        ]);

        $response = $this->getJson('/api/v1/students?filter[name]=Ahmed');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_students_can_be_filtered_by_student_number(): void
    {
        Student::factory()->create([
            'student_number' => 'STU-123456',
            'faculty_id' => $this->faculty->id,
            'program_id' => $this->program->id,
        ]);

        $response = $this->getJson('/api/v1/students?filter[student_number]=123456');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_students_can_be_filtered_by_faculty(): void
    {
        $otherFaculty = Faculty::factory()->create();
        $otherProgram = Program::factory()->create(['faculty_id' => $otherFaculty->id]);

        Student::factory()->create([
            'faculty_id' => $this->faculty->id,
            'program_id' => $this->program->id,
        ]);
        Student::factory()->create([
            'faculty_id' => $otherFaculty->id,
            'program_id' => $otherProgram->id,
        ]);

        $response = $this->getJson("/api/v1/students?filter[faculty_id]={$this->faculty->id}");

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_students_can_be_filtered_by_status(): void
    {
        Student::factory()->create([
            'student_status' => StudentStatus::ACTIVE,
            'faculty_id' => $this->faculty->id,
            'program_id' => $this->program->id,
        ]);
        Student::factory()->create([
            'student_status' => StudentStatus::GRADUATED,
            'faculty_id' => $this->faculty->id,
            'program_id' => $this->program->id,
        ]);

        $response = $this->getJson('/api/v1/students?filter[student_status]=active');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    /*
    |--------------------------------------------------------------------------
    | Store Tests
    |--------------------------------------------------------------------------
    */

    public function test_can_create_student(): void
    {
        $data = [
            'student_number' => 'STU-999999',
            'name' => 'Test Student',
            'nationality' => 'Libyan',
            'email' => 'test@example.com',
            'faculty_id' => $this->faculty->id,
            'program_id' => $this->program->id,
            'enrollment_year' => 2024,
            'student_status' => 'active',
            'location_status' => 'in_location',
        ];

        $response = $this->postJson('/api/v1/students', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.student_number', 'STU-999999')
            ->assertJsonPath('data.name', 'Test Student');

        $this->assertDatabaseHas('students', ['student_number' => 'STU-999999']);
    }

    public function test_create_validates_required_fields(): void
    {
        $response = $this->postJson('/api/v1/students', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['student_number', 'name', 'nationality', 'faculty_id', 'program_id']);
    }

    public function test_create_validates_unique_student_number(): void
    {
        Student::factory()->create([
            'student_number' => 'STU-111111',
            'faculty_id' => $this->faculty->id,
            'program_id' => $this->program->id,
        ]);

        $data = [
            'student_number' => 'STU-111111',
            'name' => 'Another Student',
            'nationality' => 'Libyan',
            'faculty_id' => $this->faculty->id,
            'program_id' => $this->program->id,
            'enrollment_year' => 2024,
            'student_status' => 'active',
            'location_status' => 'in_location',
        ];

        $response = $this->postJson('/api/v1/students', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['student_number']);
    }

    /*
    |--------------------------------------------------------------------------
    | Show Tests
    |--------------------------------------------------------------------------
    */

    public function test_can_show_student(): void
    {
        $student = Student::factory()->create([
            'faculty_id' => $this->faculty->id,
            'program_id' => $this->program->id,
        ]);

        $response = $this->getJson("/api/v1/students/{$student->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $student->id);
    }

    public function test_show_includes_relationships(): void
    {
        $student = Student::factory()->create([
            'faculty_id' => $this->faculty->id,
            'program_id' => $this->program->id,
        ]);

        $response = $this->getJson("/api/v1/students/{$student->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['id', 'faculty', 'program'],
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Update Tests
    |--------------------------------------------------------------------------
    */

    public function test_can_update_student(): void
    {
        $student = Student::factory()->create([
            'name' => 'Old Name',
            'faculty_id' => $this->faculty->id,
            'program_id' => $this->program->id,
        ]);

        $response = $this->putJson("/api/v1/students/{$student->id}", [
            'name' => 'New Name',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'New Name');
    }

    public function test_can_update_student_status(): void
    {
        $student = Student::factory()->create([
            'student_status' => StudentStatus::ACTIVE,
            'faculty_id' => $this->faculty->id,
            'program_id' => $this->program->id,
        ]);

        $response = $this->putJson("/api/v1/students/{$student->id}", [
            'student_status' => 'graduated',
            'graduation_year' => 2025,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.student_status', 'graduated');
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Tests
    |--------------------------------------------------------------------------
    */

    public function test_can_delete_student(): void
    {
        $student = Student::factory()->create([
            'faculty_id' => $this->faculty->id,
            'program_id' => $this->program->id,
        ]);

        $response = $this->deleteJson("/api/v1/students/{$student->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('students', ['id' => $student->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | Authorization Tests
    |--------------------------------------------------------------------------
    */

    public function test_unauthenticated_user_cannot_access(): void
    {
        $this->app['auth']->forgetGuards();

        $response = $this->getJson('/api/v1/students');

        $response->assertStatus(401);
    }
}
