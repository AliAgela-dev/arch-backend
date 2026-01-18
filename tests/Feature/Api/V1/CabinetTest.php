<?php

namespace Tests\Feature\Api\V1;

use App\Models\Cabinet;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CabinetTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Room $room;

    protected function setUp(): void
    {
        parent::setUp();
        
        Role::create(['name' => 'super_admin']);
        
        $this->user = User::factory()->create();
        $this->user->assignRole('super_admin');
        Sanctum::actingAs($this->user);

        $this->room = Room::factory()->create();
    }

    /*
    |--------------------------------------------------------------------------
    | Index Tests
    |--------------------------------------------------------------------------
    */

    public function test_can_list_all_cabinets(): void
    {
        Cabinet::factory()->count(3)->create(['room_id' => $this->room->id]);

        $response = $this->getJson('/api/v1/location/cabinets');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_cabinets_are_paginated(): void
    {
        Cabinet::factory()->count(20)->create(['room_id' => $this->room->id]);

        $response = $this->getJson('/api/v1/location/cabinets?per_page=5');

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.per_page', 5);
    }

    public function test_cabinets_include_drawers(): void
    {
        Cabinet::factory()->create(['room_id' => $this->room->id]);

        $response = $this->getJson('/api/v1/location/cabinets');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'drawers'],
                ],
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Show Tests
    |--------------------------------------------------------------------------
    */

    public function test_can_show_single_cabinet(): void
    {
        $cabinet = Cabinet::factory()->create(['room_id' => $this->room->id]);

        $response = $this->getJson("/api/v1/location/cabinets/{$cabinet->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $cabinet->id);
    }

    /*
    |--------------------------------------------------------------------------
    | Store Tests
    |--------------------------------------------------------------------------
    */

    public function test_can_create_cabinet(): void
    {
        $data = [
            'room_id' => $this->room->id,
            'name' => 'Cabinet A1',
            'position_x' => 100,
            'position_y' => 200,
            'status' => 'active',
        ];

        $response = $this->postJson('/api/v1/location/cabinets', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Cabinet A1')
            ->assertJsonPath('message', 'Cabinet created successfully');

        $this->assertDatabaseHas('cabinets', ['name' => 'Cabinet A1']);
    }

    public function test_cabinet_auto_creates_drawers(): void
    {
        $data = [
            'room_id' => $this->room->id,
            'name' => 'Cabinet With Drawers',
            'position_x' => 0,
            'position_y' => 0,
            'status' => 'active',
        ];

        $response = $this->postJson('/api/v1/location/cabinets', $data);

        $response->assertStatus(201);

        $cabinetId = $response->json('data.id');
        $this->assertDatabaseCount('drawers', Cabinet::DRAWER_COUNT);
    }

    public function test_create_cabinet_requires_valid_room(): void
    {
        $data = [
            'room_id' => 'nonexistent-uuid',
            'name' => 'Cabinet',
            'status' => 'active',
        ];

        $response = $this->postJson('/api/v1/location/cabinets', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['room_id']);
    }

    /*
    |--------------------------------------------------------------------------
    | Update Tests
    |--------------------------------------------------------------------------
    */

    public function test_can_update_cabinet(): void
    {
        $cabinet = Cabinet::factory()->create([
            'room_id' => $this->room->id,
            'name' => 'Old Name',
        ]);

        $response = $this->putJson("/api/v1/location/cabinets/{$cabinet->id}", [
            'name' => 'New Name',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'New Name');
    }

    public function test_cannot_change_cabinet_room(): void
    {
        $cabinet = Cabinet::factory()->create(['room_id' => $this->room->id]);
        $newRoom = Room::factory()->create();

        $response = $this->putJson("/api/v1/location/cabinets/{$cabinet->id}", [
            'room_id' => $newRoom->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Changing room_id is not supported via update() without move logic.');
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Tests
    |--------------------------------------------------------------------------
    */

    public function test_can_delete_cabinet(): void
    {
        $cabinet = Cabinet::factory()->create(['room_id' => $this->room->id]);

        $response = $this->deleteJson("/api/v1/location/cabinets/{$cabinet->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Cabinet (and its drawers) deleted successfully');

        $this->assertDatabaseMissing('cabinets', ['id' => $cabinet->id]);
    }

    public function test_deleting_cabinet_removes_drawers(): void
    {
        $cabinet = Cabinet::factory()->create(['room_id' => $this->room->id]);
        $cabinetId = $cabinet->id;

        // Cabinet has auto-created drawers from factory or we create them
        $this->deleteJson("/api/v1/location/cabinets/{$cabinetId}");

        $this->assertDatabaseMissing('drawers', ['cabinet_id' => $cabinetId]);
    }
}
