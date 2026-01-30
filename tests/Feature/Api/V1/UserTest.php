<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Enums\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        Role::create(['name' => 'super_admin']);
        Role::create(['name' => 'archivist']);
        Role::create(['name' => 'faculty_staff']);
        
        $this->admin = User::factory()->create();
        $this->admin->assignRole('super_admin');
        Sanctum::actingAs($this->admin);
    }

    /*
    |--------------------------------------------------------------------------
    | Index Tests
    |--------------------------------------------------------------------------
    */

    public function test_can_list_all_users(): void
    {
        User::factory()->count(5)->create();

        $response = $this->getJson('/api/v1/users');

        $response->assertOk();
        // +1 for admin user
        $this->assertCount(6, $response->json('data'));
    }

    public function test_users_can_be_filtered_by_name(): void
    {
        User::factory()->create(['name' => 'Xyz UniqueTestName']);
        User::factory()->create(['name' => 'Jane Smith']);

        $response = $this->getJson('/api/v1/users?filter[name]=UniqueTestName');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_users_can_be_filtered_by_email(): void
    {
        User::factory()->create(['email' => 'john@example.com']);
        User::factory()->create(['email' => 'jane@example.com']);

        $response = $this->getJson('/api/v1/users?filter[email]=john@example.com');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_users_can_be_sorted(): void
    {
        User::factory()->create(['name' => 'Zebra']);
        User::factory()->create(['name' => 'Alpha']);

        $response = $this->getJson('/api/v1/users?sort=name');

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('name')->toArray();
        $this->assertEquals($names, collect($names)->sort()->values()->toArray());
    }

    /*
    |--------------------------------------------------------------------------
    | Show Tests
    |--------------------------------------------------------------------------
    */

    public function test_can_show_single_user(): void
    {
        $user = User::factory()->create();

        $response = $this->getJson("/api/v1/users/{$user->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $user->id);
    }

    public function test_show_returns_404_for_nonexistent_user(): void
    {
        $response = $this->getJson('/api/v1/users/nonexistent-uuid');

        $response->assertNotFound();
    }

    /*
    |--------------------------------------------------------------------------
    | Store Tests
    |--------------------------------------------------------------------------
    */

    public function test_can_create_user(): void
    {
        $faculty = \App\Models\Faculty::factory()->create();

        $data = [
            'name' => 'New User',
            'email' => 'newuser@limu.edu.ly',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'archivist',
            'faculties' => [$faculty->id],
        ];

        $response = $this->postJson('/api/v1/users', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'New User')
            ->assertJsonPath('message', 'User created successfully.');

        $this->assertDatabaseHas('users', ['email' => 'newuser@limu.edu.ly']);
    }

    public function test_create_user_validates_required_fields(): void
    {
        $response = $this->postJson('/api/v1/users', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password', 'role', 'faculties']);
    }

    public function test_create_user_validates_unique_email(): void
    {
        // Create user with valid email domain
        User::factory()->create(['email' => 'existing@limu.edu.ly']);
        $faculty = \App\Models\Faculty::factory()->create();

        $data = [
            'name' => 'New User',
            'email' => 'existing@limu.edu.ly',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'archivist',
            'faculties' => [$faculty->id],
        ];

        $response = $this->postJson('/api/v1/users', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_user_role_is_assigned_on_create(): void
    {
        $faculty = \App\Models\Faculty::factory()->create();
        
        $data = [
            'name' => 'Archivist User',
            'email' => 'archivist@limu.edu.ly',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'archivist',
            'faculties' => [$faculty->id],
        ];

        $response = $this->postJson('/api/v1/users', $data);

        $response->assertStatus(201);

        $user = User::where('email', 'archivist@limu.edu.ly')->first();
        $this->assertTrue($user->hasRole('archivist'));
    }

    /*
    |--------------------------------------------------------------------------
    | Update Tests
    |--------------------------------------------------------------------------
    */

    public function test_can_update_user(): void
    {
        $user = User::factory()->create(['name' => 'Old Name']);

        $response = $this->putJson("/api/v1/users/{$user->id}", [
            'name' => 'New Name',
            'password_confirmation' => 'dummy123',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'New Name');

        $this->assertDatabaseHas('users', ['name' => 'New Name']);
    }

    public function test_can_update_user_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole('faculty_staff');

        $response = $this->putJson("/api/v1/users/{$user->id}", [
            'role' => 'archivist',
            'password_confirmation' => 'dummy123',
        ]);

        $response->assertOk();

        $user->refresh();
        $this->assertTrue($user->hasRole('archivist'));
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Tests
    |--------------------------------------------------------------------------
    */

    public function test_can_delete_user(): void
    {
        $user = User::factory()->create();

        $response = $this->deleteJson("/api/v1/users/{$user->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'User deleted successfully.');

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | Authorization Tests
    |--------------------------------------------------------------------------
    */

    public function test_unauthenticated_user_cannot_access_users(): void
    {
        $this->app['auth']->forgetGuards();

        $response = $this->getJson('/api/v1/users');

        $response->assertStatus(401);
    }
}
