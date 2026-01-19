<?php

namespace Tests\Feature\Api\V1;

use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DocumentTypeTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'super_admin']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('super_admin');
        Sanctum::actingAs($this->admin);
    }

    /*
    |--------------------------------------------------------------------------
    | Index Tests
    |--------------------------------------------------------------------------
    */

    public function test_can_list_document_types(): void
    {
        DocumentType::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/document-types');

        $response->assertOk();
        $this->assertCount(3, $response->json('data'));
    }

    public function test_document_types_can_be_filtered_by_status(): void
    {
        DocumentType::factory()->create(['status' => 'active']);
        DocumentType::factory()->create(['status' => 'inactive']);

        $response = $this->getJson('/api/v1/document-types?filter[status]=active');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_document_types_can_be_filtered_by_is_required(): void
    {
        DocumentType::factory()->create(['is_required' => true]);
        DocumentType::factory()->create(['is_required' => false]);

        $response = $this->getJson('/api/v1/document-types?filter[is_required]=1');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    /*
    |--------------------------------------------------------------------------
    | Store Tests
    |--------------------------------------------------------------------------
    */

    public function test_can_create_document_type(): void
    {
        $data = [
            'name' => 'Transcript',
            'description' => 'Academic transcript',
            'is_required' => true,
            'status' => 'active',
        ];

        $response = $this->postJson('/api/v1/document-types', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Transcript')
            ->assertJsonPath('data.is_required', true);

        $this->assertDatabaseHas('document_types', ['name' => 'Transcript']);
    }

    public function test_can_create_document_type_with_conditions(): void
    {
        $data = [
            'name' => 'Visa Document',
            'is_required' => true,
            'requirement_conditions' => [
                'operator' => 'AND',
                'conditions' => [
                    ['field' => 'nationality', 'op' => '!=', 'value' => 'libyan'],
                ],
            ],
            'status' => 'active',
        ];

        $response = $this->postJson('/api/v1/document-types', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.requirement_conditions.operator', 'AND');
    }

    public function test_create_validates_required_fields(): void
    {
        $response = $this->postJson('/api/v1/document-types', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'is_required', 'status']);
    }

    /*
    |--------------------------------------------------------------------------
    | Show Tests
    |--------------------------------------------------------------------------
    */

    public function test_can_show_document_type(): void
    {
        $docType = DocumentType::factory()->create();

        $response = $this->getJson("/api/v1/document-types/{$docType->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $docType->id);
    }

    /*
    |--------------------------------------------------------------------------
    | Update Tests
    |--------------------------------------------------------------------------
    */

    public function test_can_update_document_type(): void
    {
        $docType = DocumentType::factory()->create(['name' => 'Old Name']);

        $response = $this->putJson("/api/v1/document-types/{$docType->id}", [
            'name' => 'New Name',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'New Name');
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Tests
    |--------------------------------------------------------------------------
    */

    public function test_can_delete_document_type(): void
    {
        $docType = DocumentType::factory()->create();

        $response = $this->deleteJson("/api/v1/document-types/{$docType->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('document_types', ['id' => $docType->id]);
    }

    public function test_cannot_delete_document_type_with_documents(): void
    {
        $docType = DocumentType::factory()->create();
        \App\Models\StudentDocument::factory()->create(['document_type_id' => $docType->id]);

        $response = $this->deleteJson("/api/v1/document-types/{$docType->id}");

        $response->assertStatus(422);
    }

    /*
    |--------------------------------------------------------------------------
    | Authorization Tests
    |--------------------------------------------------------------------------
    */

    public function test_unauthenticated_user_cannot_access(): void
    {
        $this->app['auth']->forgetGuards();

        $response = $this->getJson('/api/v1/document-types');

        $response->assertStatus(401);
    }
}
