<?php

namespace Tests\Feature\Api\V1;

use App\Models\Cabinet;
use App\Models\Drawer;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DrawerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Room $room;
    protected Cabinet $cabinet;

    protected function setUp(): void
    {
        parent::setUp();
        
        Role::create(['name' => 'super_admin']);
        
        $this->user = User::factory()->create();
        $this->user->assignRole('super_admin');
        Sanctum::actingAs($this->user);

        $this->room = Room::factory()->create();
        $this->cabinet = Cabinet::factory()->create(['room_id' => $this->room->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | Index Tests
    |--------------------------------------------------------------------------
    */

    public function test_can_list_all_drawers(): void
    {
        Drawer::factory()->count(5)->create(['cabinet_id' => $this->cabinet->id]);

        $response = $this->getJson('/api/v1/location/drawers');

        $response->assertOk()
            ->assertJsonCount(5, 'data');
    }

    public function test_drawers_are_paginated(): void
    {
        Drawer::factory()->count(20)->create(['cabinet_id' => $this->cabinet->id]);

        $response = $this->getJson('/api/v1/location/drawers?per_page=10');

        $response->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.per_page', 10);
    }

    public function test_drawers_include_cabinet(): void
    {
        Drawer::factory()->create(['cabinet_id' => $this->cabinet->id]);

        $response = $this->getJson('/api/v1/location/drawers');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'number', 'label', 'capacity'],
                ],
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Show Tests
    |--------------------------------------------------------------------------
    */

    public function test_can_show_single_drawer(): void
    {
        $drawer = Drawer::factory()->create(['cabinet_id' => $this->cabinet->id]);

        $response = $this->getJson("/api/v1/location/drawers/{$drawer->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $drawer->id);
    }

    public function test_show_returns_404_for_nonexistent_drawer(): void
    {
        $response = $this->getJson('/api/v1/location/drawers/nonexistent-uuid');

        $response->assertNotFound();
    }

    /*
    |--------------------------------------------------------------------------
    | Store Tests
    |--------------------------------------------------------------------------
    */

    public function test_can_create_drawer(): void
    {
        $data = [
            'cabinet_id' => $this->cabinet->id,
            'number' => 5,
            'label' => 'Documents A-Z',
            'capacity' => 100,
            'status' => 'active',
        ];

        $response = $this->postJson('/api/v1/location/drawers', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.number', 5)
            ->assertJsonPath('message', 'Drawer created successfully');

        $this->assertDatabaseHas('drawers', ['number' => 5]);
    }

    public function test_create_drawer_requires_valid_cabinet(): void
    {
        $data = [
            'cabinet_id' => 'nonexistent-uuid',
            'number' => 1,
            'capacity' => 100,
        ];

        $response = $this->postJson('/api/v1/location/drawers', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cabinet_id']);
    }

    /*
    |--------------------------------------------------------------------------
    | Update Tests
    |--------------------------------------------------------------------------
    */

    public function test_can_update_drawer(): void
    {
        $drawer = Drawer::factory()->create([
            'cabinet_id' => $this->cabinet->id,
            'label' => 'Old Label',
        ]);

        $response = $this->putJson("/api/v1/location/drawers/{$drawer->id}", [
            'label' => 'New Label',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.label', 'New Label');

        $this->assertDatabaseHas('drawers', ['label' => 'New Label']);
    }

    public function test_can_update_drawer_capacity(): void
    {
        $drawer = Drawer::factory()->create([
            'cabinet_id' => $this->cabinet->id,
            'capacity' => 100,
        ]);

        $response = $this->putJson("/api/v1/location/drawers/{$drawer->id}", [
            'capacity' => 200,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.capacity', 200);
    }

    public function test_cannot_change_drawer_cabinet(): void
    {
        $drawer = Drawer::factory()->create(['cabinet_id' => $this->cabinet->id]);
        $newCabinet = Cabinet::factory()->create(['room_id' => $this->room->id]);

        $response = $this->putJson("/api/v1/location/drawers/{$drawer->id}", [
            'cabinet_id' => $newCabinet->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Changing cabinet_id is not supported via update() without move logic.');
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Tests
    |--------------------------------------------------------------------------
    */

    public function test_can_delete_drawer(): void
    {
        $drawer = Drawer::factory()->create(['cabinet_id' => $this->cabinet->id]);

        $response = $this->deleteJson("/api/v1/location/drawers/{$drawer->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Drawer deleted successfully');

        $this->assertDatabaseMissing('drawers', ['id' => $drawer->id]);
    }
}
