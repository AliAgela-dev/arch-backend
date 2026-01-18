<?php

namespace Tests\Feature\Api\V1;

use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoomTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        Role::create(['name' => 'super_admin']);
        
        $this->user = User::factory()->create();
        $this->user->assignRole('super_admin');
        Sanctum::actingAs($this->user);
    }

    /*
    |--------------------------------------------------------------------------
    | Index Tests
    |--------------------------------------------------------------------------
    */

    public function test_can_list_all_rooms(): void
    {
        Room::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/location/rooms');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_rooms_include_cabinets_and_drawers(): void
    {
        $room = Room::factory()->create();

        $response = $this->getJson('/api/v1/location/rooms');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'description', 'status', 'cabinets'],
                ],
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Show Tests
    |--------------------------------------------------------------------------
    */

    public function test_can_show_single_room(): void
    {
        $room = Room::factory()->create();

        $response = $this->getJson("/api/v1/location/rooms/{$room->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $room->id);
    }

    public function test_show_returns_404_for_nonexistent_room(): void
    {
        $response = $this->getJson('/api/v1/location/rooms/nonexistent-uuid');

        $response->assertNotFound();
    }

    /*
    |--------------------------------------------------------------------------
    | Store Tests
    |--------------------------------------------------------------------------
    */

    public function test_can_create_room(): void
    {
        $data = [
            'name' => 'Archive Room A',
            'description' => 'Main archive storage',
            'status' => 'active',
        ];

        $response = $this->postJson('/api/v1/location/rooms', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Archive Room A')
            ->assertJsonPath('message', 'Room created successfully');

        $this->assertDatabaseHas('rooms', ['name' => 'Archive Room A']);
    }

    public function test_create_room_validates_required_fields(): void
    {
        $response = $this->postJson('/api/v1/location/rooms', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /*
    |--------------------------------------------------------------------------
    | Update Tests
    |--------------------------------------------------------------------------
    */

    public function test_can_update_room(): void
    {
        $room = Room::factory()->create(['name' => 'Old Name']);

        $response = $this->putJson("/api/v1/location/rooms/{$room->id}", [
            'name' => 'New Name',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'New Name');

        $this->assertDatabaseHas('rooms', ['name' => 'New Name']);
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Tests
    |--------------------------------------------------------------------------
    */

    public function test_can_delete_room(): void
    {
        $room = Room::factory()->create();

        $response = $this->deleteJson("/api/v1/location/rooms/{$room->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Room (and its cabinets/drawers) deleted successfully');

        $this->assertDatabaseMissing('rooms', ['id' => $room->id]);
    }
}
