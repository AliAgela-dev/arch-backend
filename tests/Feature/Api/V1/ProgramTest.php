<?php

namespace Tests\Feature\Api\V1;

use App\Models\Faculty;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProgramTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Faculty $faculty;

    protected function setUp(): void
    {
        parent::setUp();
        
        Role::create(['name' => 'super_admin']);
        Role::create(['name' => 'archivist']);
        
        $this->user = User::factory()->create();
        $this->user->assignRole('super_admin');
        Sanctum::actingAs($this->user);

        $this->faculty = Faculty::factory()->create();
    }

    /*
    |--------------------------------------------------------------------------
    | Index Tests
    |--------------------------------------------------------------------------
    */

    public function test_can_list_all_programs(): void
    {
        Program::factory()->count(3)->create(['faculty_id' => $this->faculty->id]);

        $response = $this->getJson('/api/v1/academic/programs');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_programs_can_be_filtered_by_faculty(): void
    {
        $faculty2 = Faculty::factory()->create();
        Program::factory()->count(2)->create(['faculty_id' => $this->faculty->id]);
        Program::factory()->create(['faculty_id' => $faculty2->id]);

        $response = $this->getJson("/api/v1/academic/programs?filter[faculty_id]={$this->faculty->id}");

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_programs_include_faculty_relationship(): void
    {
        Program::factory()->create(['faculty_id' => $this->faculty->id]);

        $response = $this->getJson('/api/v1/academic/programs');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'code', 'name_ar', 'name_en', 'faculty'],
                ],
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Show Tests
    |--------------------------------------------------------------------------
    */

    public function test_can_show_single_program(): void
    {
        $program = Program::factory()->create(['faculty_id' => $this->faculty->id]);

        $response = $this->getJson("/api/v1/academic/programs/{$program->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $program->id);
    }

    public function test_show_returns_404_for_nonexistent_program(): void
    {
        $response = $this->getJson('/api/v1/academic/programs/99999');

        $response->assertNotFound();
    }

    /*
    |--------------------------------------------------------------------------
    | Store Tests
    |--------------------------------------------------------------------------
    */

    public function test_can_create_program(): void
    {
        $data = [
            'faculty_id' => $this->faculty->id,
            'code' => 'CS',
            'name_ar' => 'علوم الحاسوب',
            'name_en' => 'Computer Science',
            'status' => 'active',
        ];

        $response = $this->postJson('/api/v1/academic/programs', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.code', 'CS')
            ->assertJsonPath('message', 'Successfully created program');

        $this->assertDatabaseHas('programs', ['code' => 'CS']);
    }

    public function test_create_program_requires_valid_faculty(): void
    {
        $data = [
            'faculty_id' => 99999,
            'code' => 'CS',
            'name_ar' => 'علوم الحاسوب',
            'name_en' => 'Computer Science',
        ];

        $response = $this->postJson('/api/v1/academic/programs', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['faculty_id']);
    }

    /*
    |--------------------------------------------------------------------------
    | Update Tests
    |--------------------------------------------------------------------------
    */

    public function test_can_update_program(): void
    {
        $program = Program::factory()->create([
            'faculty_id' => $this->faculty->id,
            'name_en' => 'Old Name',
        ]);

        $response = $this->putJson("/api/v1/academic/programs/{$program->id}", [
            'name_en' => 'New Name',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name_en', 'New Name');

        $this->assertDatabaseHas('programs', ['name_en' => 'New Name']);
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Tests
    |--------------------------------------------------------------------------
    */

    public function test_can_delete_program(): void
    {
        $program = Program::factory()->create(['faculty_id' => $this->faculty->id]);

        $response = $this->deleteJson("/api/v1/academic/programs/{$program->id}");

        $response->assertNoContent();
        $this->assertSoftDeleted('programs', ['id' => $program->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | Restore Tests
    |--------------------------------------------------------------------------
    */

    public function test_can_restore_deleted_program(): void
    {
        $program = Program::factory()->create(['faculty_id' => $this->faculty->id]);
        $program->delete();

        $response = $this->postJson("/api/v1/academic/programs/{$program->id}/restore");

        $response->assertOk()
            ->assertJsonPath('message', 'Successfully restored program');

        $this->assertDatabaseHas('programs', [
            'id' => $program->id,
            'deleted_at' => null,
        ]);
    }
}
