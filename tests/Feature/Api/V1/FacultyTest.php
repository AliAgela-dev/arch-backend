<?php

namespace Tests\Feature\Api\V1;

use App\Models\Faculty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FacultyTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        Role::create(['name' => 'super_admin']);
        Role::create(['name' => 'archivist']);
        
        $this->user = User::factory()->create();
        $this->user->assignRole('super_admin');
        Sanctum::actingAs($this->user);
    }

    /*
    |--------------------------------------------------------------------------
    | Index Tests
    |--------------------------------------------------------------------------
    */

    public function test_can_list_all_faculties(): void
    {
        Faculty::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/academic/faculties');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_faculties_can_be_filtered_by_name(): void
    {
        Faculty::factory()->create(['name_en' => 'Engineering']);
        Faculty::factory()->create(['name_en' => 'Science']);

        $response = $this->getJson('/api/v1/academic/faculties?filter[name_en]=Eng');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_faculties_can_be_sorted(): void
    {
        Faculty::factory()->create(['name_en' => 'Zebra']);
        Faculty::factory()->create(['name_en' => 'Alpha']);

        $response = $this->getJson('/api/v1/academic/faculties?sort=name_en');

        $response->assertOk();
        $this->assertEquals('Alpha', $response->json('data.0.name_en'));
    }

    /*
    |--------------------------------------------------------------------------
    | Show Tests
    |--------------------------------------------------------------------------
    */

    public function test_can_show_single_faculty(): void
    {
        $faculty = Faculty::factory()->create();

        $response = $this->getJson("/api/v1/academic/faculties/{$faculty->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $faculty->id);
    }

    public function test_show_returns_404_for_nonexistent_faculty(): void
    {
        $response = $this->getJson('/api/v1/academic/faculties/99999');

        $response->assertNotFound();
    }

    /*
    |--------------------------------------------------------------------------
    | Store Tests
    |--------------------------------------------------------------------------
    */

    public function test_can_create_faculty(): void
    {
        $data = [
            'code' => 'ENG',
            'name_ar' => 'كلية الهندسة',
            'name_en' => 'Engineering',
            'status' => 'active',
        ];

        $response = $this->postJson('/api/v1/academic/faculties', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.code', 'ENG')
            ->assertJsonPath('message', 'Successfully created faculty');

        $this->assertDatabaseHas('faculties', ['code' => 'ENG']);
    }

    public function test_create_faculty_validates_required_fields(): void
    {
        $response = $this->postJson('/api/v1/academic/faculties', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code', 'name_ar', 'name_en']);
    }

    /*
    |--------------------------------------------------------------------------
    | Update Tests
    |--------------------------------------------------------------------------
    */

    public function test_can_update_faculty(): void
    {
        $faculty = Faculty::factory()->create(['name_en' => 'Old Name']);

        $response = $this->putJson("/api/v1/academic/faculties/{$faculty->id}", [
            'name_en' => 'New Name',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name_en', 'New Name');

        $this->assertDatabaseHas('faculties', ['name_en' => 'New Name']);
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Tests
    |--------------------------------------------------------------------------
    */

    public function test_can_delete_faculty(): void
    {
        $faculty = Faculty::factory()->create();

        $response = $this->deleteJson("/api/v1/academic/faculties/{$faculty->id}");

        $response->assertNoContent();
        $this->assertSoftDeleted('faculties', ['id' => $faculty->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | Restore Tests
    |--------------------------------------------------------------------------
    */

    public function test_can_restore_deleted_faculty(): void
    {
        $faculty = Faculty::factory()->create();
        $faculty->delete();

        $response = $this->postJson("/api/v1/academic/faculties/{$faculty->id}/restore");

        $response->assertOk()
            ->assertJsonPath('message', 'Successfully restored faculty');

        $this->assertDatabaseHas('faculties', [
            'id' => $faculty->id,
            'deleted_at' => null,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Authorization Tests
    |--------------------------------------------------------------------------
    */

    public function test_unauthenticated_user_cannot_access_faculties(): void
    {
        Sanctum::actingAs(User::factory()->create(), []);
        $this->app['auth']->forgetGuards();

        $response = $this->getJson('/api/v1/academic/faculties');

        $response->assertStatus(401);
    }
}
