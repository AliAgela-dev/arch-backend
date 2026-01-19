<?php

namespace Tests\Feature\Api\V1;

use App\Enums\BorrowingStatus;
use App\Enums\UserRole;
use App\Models\Borrowing;
use App\Models\StudentDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BorrowingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $faculty;
    protected User $archivist;
    protected StudentDocument $document;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup roles
        foreach (UserRole::cases() as $role) {
            Role::firstOrCreate(['name' => $role->value]);
        }

        $this->admin = User::factory()->create();
        $this->admin->assignRole(UserRole::super_admin->value);

        $this->faculty = User::factory()->create();
        $this->faculty->assignRole(UserRole::faculty_staff->value);

        $this->archivist = User::factory()->create();
        $this->archivist->assignRole(UserRole::archivist->value);

        $this->document = StudentDocument::factory()->complete()->create();
    }

    /*
    |--------------------------------------------------------------------------
    | Store Tests
    |--------------------------------------------------------------------------
    */

    public function test_faculty_can_request_borrowing(): void
    {
        Sanctum::actingAs($this->faculty);

        $response = $this->postJson('/api/v1/borrowings', [
            'student_document_id' => $this->document->id,
            'notes' => 'Need for research',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', BorrowingStatus::PENDING->value);

        $this->assertDatabaseHas('borrowings', [
            'user_id' => $this->faculty->id,
            'student_document_id' => $this->document->id,
            'status' => BorrowingStatus::PENDING->value,
        ]);
    }

    public function test_cannot_borrow_unavailable_document(): void
    {
        // First borrowing active
        Borrowing::factory()->create([
            'student_document_id' => $this->document->id,
            'status' => BorrowingStatus::BORROWED,
        ]);

        Sanctum::actingAs($this->faculty);

        $response = $this->postJson('/api/v1/borrowings', [
            'student_document_id' => $this->document->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'This document is currently borrowed and unavailable.');
    }

    /*
    |--------------------------------------------------------------------------
    | Approval Tests
    |--------------------------------------------------------------------------
    */

    public function test_archivist_can_approve_borrowing(): void
    {
        $borrowing = Borrowing::factory()->pending()->create([
            'student_document_id' => $this->document->id,
        ]);

        Sanctum::actingAs($this->archivist);

        $response = $this->postJson("/api/v1/borrowings/{$borrowing->id}/approve", [
            'action' => 'approve',
            'due_days' => 7,
            'admin_notes' => 'Approved for short term',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', BorrowingStatus::APPROVED->value);

        $this->assertDatabaseHas('borrowings', [
            'id' => $borrowing->id,
            'status' => BorrowingStatus::APPROVED->value,
            'admin_notes' => 'Approved for short term',
        ]);
        
        $borrowing->refresh();
        $this->assertNotNull($borrowing->approved_at);
        $this->assertTrue($borrowing->due_date->isFuture());
    }

    public function test_archivist_can_reject_borrowing(): void
    {
        $borrowing = Borrowing::factory()->pending()->create();

        Sanctum::actingAs($this->archivist);

        $response = $this->postJson("/api/v1/borrowings/{$borrowing->id}/approve", [
            'action' => 'reject',
            'rejection_reason' => 'Document not found',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', BorrowingStatus::REJECTED->value);

        $this->assertDatabaseHas('borrowings', [
            'id' => $borrowing->id,
            'status' => BorrowingStatus::REJECTED->value,
            'rejection_reason' => 'Document not found',
        ]);
    }

    public function test_cannot_approve_non_pending_request(): void
    {
        $borrowing = Borrowing::factory()->approved()->create();

        Sanctum::actingAs($this->archivist);

        $response = $this->postJson("/api/v1/borrowings/{$borrowing->id}/approve", [
            'action' => 'approve',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Only pending borrowing requests can be approved or rejected.');
    }

    /*
    |--------------------------------------------------------------------------
    | Overdue Logic Tests
    |--------------------------------------------------------------------------
    */

    public function test_is_overdue_calculation(): void
    {
        $borrowing = Borrowing::factory()->overdue()->create();

        $this->assertTrue($borrowing->isOverdue());
        $this->assertGreaterThan(0, $borrowing->daysOverdue());
    }

    public function test_overdue_scope_queries(): void
    {
        Borrowing::factory()->overdue()->count(2)->create();
        Borrowing::factory()->borrowed()->create(['due_date' => now()->addDays(5)]); // Not overdue

        $this->assertEquals(2, Borrowing::overdue()->count());
    }

    /*
    |--------------------------------------------------------------------------
    | Authorization Tests
    |--------------------------------------------------------------------------
    */

    public function test_faculty_can_only_see_own_borrowings(): void
    {
        $myBorrowing = Borrowing::factory()->create(['user_id' => $this->faculty->id]);
        $otherBorrowing = Borrowing::factory()->create(); // different user

        Sanctum::actingAs($this->faculty);

        $response = $this->getJson('/api/v1/borrowings');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $myBorrowing->id);
    }

    public function test_admin_can_see_all_borrowings(): void
    {
        Borrowing::factory()->create(['user_id' => $this->faculty->id]);
        Borrowing::factory()->create();

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/borrowings');

        $response->assertOk()
             ->assertJsonCount(2, 'data');
    }
}
