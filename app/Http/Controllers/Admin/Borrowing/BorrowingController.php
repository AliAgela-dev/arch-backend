<?php

namespace App\Http\Controllers\Admin\Borrowing;

use App\Enums\BorrowingStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Borrowing\ApproveBorrowingRequest;
use App\Http\Requests\Borrowing\ReturnBorrowingRequest;
use App\Http\Requests\Borrowing\StoreBorrowingRequest;
use App\Http\Resources\BorrowingResource;
use App\Models\Borrowing;
use App\Models\StudentDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @tags Borrowing
 */
class BorrowingController extends AdminController
{
    /**
     * Display a listing of borrowings.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Borrowing::class);

        $query = QueryBuilder::for(Borrowing::class)
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('user_id'),
                AllowedFilter::exact('student_document_id'),
                AllowedFilter::callback('overdue', function ($query, $value) {
                    if ($value) {
                        $query->overdue();
                    }
                }),
            ])
            ->allowedSorts(['requested_at', 'due_date', 'status', 'created_at'])
            ->with(['user', 'studentDocument']);

        // Filter by role: faculty_staff see only their own
        $user = $request->user();
        if ($user->hasRole(UserRole::faculty_staff->value) && 
            !$user->hasRole([UserRole::archivist->value, UserRole::super_admin->value])) {
            $query->where('user_id', $user->id);
        }

        $borrowings = $query->paginate($request->query('per_page', 15));

        return BorrowingResource::collection($borrowings);
    }

    /**
     * Store a newly created borrowing request.
     */
    public function store(StoreBorrowingRequest $request)
    {
        $this->authorize('create', Borrowing::class);

        $validated = $request->validated();

        // Additional check for document availability
        $document = StudentDocument::findOrFail($validated['student_document_id']);
        
        if ($document->isBorrowed()) {
            return $this->error(
                'This document is currently borrowed and unavailable.',
                422
            );
        }

        $borrowing = Borrowing::create([
            'user_id' => $request->user()->id,
            'student_document_id' => $validated['student_document_id'],
            'notes' => $validated['notes'] ?? null,
            'status' => BorrowingStatus::PENDING,
            'requested_at' => now(),
        ]);

        $borrowing->load(['user', 'studentDocument']);

        return $this->resource(
            new BorrowingResource($borrowing),
            'Borrowing request submitted successfully',
            201
        );
    }

    /**
     * Display the specified borrowing.
     */
    public function show(string $id)
    {
        $borrowing = Borrowing::with(['user', 'studentDocument'])->findOrFail($id);

        $this->authorize('view', $borrowing);

        return new BorrowingResource($borrowing);
    }

    /**
     * Update the specified borrowing (for pending requests only).
     */
    public function update(Request $request, string $id)
    {
        $borrowing = Borrowing::findOrFail($id);

        $this->authorize('update', $borrowing);

        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $borrowing->update($validated);

        return $this->resource(
            new BorrowingResource($borrowing->fresh()->load(['user', 'studentDocument'])),
            'Borrowing request updated successfully'
        );
    }

    /**
     * Cancel/delete the specified borrowing.
     */
    public function destroy(string $id)
    {
        $borrowing = Borrowing::findOrFail($id);

        $this->authorize('delete', $borrowing);

        $borrowing->delete();

        return $this->success(null, 'Borrowing request cancelled successfully');
    }

    /**
     * Approve or reject a borrowing request.
     */
    public function approve(ApproveBorrowingRequest $request, string $id)
    {
        $borrowing = Borrowing::findOrFail($id);

        $this->authorize('approve', Borrowing::class);

        if ($borrowing->status !== BorrowingStatus::PENDING) {
            return $this->error(
                'Only pending borrowing requests can be approved or rejected.',
                422
            );
        }

        $validated = $request->validated();

        DB::transaction(function () use ($borrowing, $validated) {
            if ($validated['action'] === 'approve') {
                // Calculate due date
                $dueDays = $validated['due_days'] ?? config('borrowing.default_duration_days', 14);
                
                $borrowing->update([
                    'status' => BorrowingStatus::APPROVED,
                    'approved_at' => now(),
                    'due_date' => now()->addDays($dueDays),
                    'admin_notes' => $validated['admin_notes'] ?? null,
                ]);
            } else {
                // Reject
                $borrowing->update([
                    'status' => BorrowingStatus::REJECTED,
                    'rejected_at' => now(),
                    'rejection_reason' => $validated['rejection_reason'],
                    'admin_notes' => $validated['admin_notes'] ?? null,
                ]);
            }
        });

        return $this->resource(
            new BorrowingResource($borrowing->fresh()->load(['user', 'studentDocument'])),
            $validated['action'] === 'approve' 
                ? 'Borrowing request approved successfully' 
                : 'Borrowing request rejected'
        );
    }

    /**
     * Mark borrowing as borrowed (picked up).
     */
    public function markBorrowed(Request $request, string $id)
    {
        $borrowing = Borrowing::findOrFail($id);

        $this->authorize('markBorrowed', Borrowing::class);

        if ($borrowing->status !== BorrowingStatus::APPROVED) {
            return $this->error(
                'Only approved borrowing requests can be marked as borrowed.',
                422
            );
        }

        $borrowing->update([
            'status' => BorrowingStatus::BORROWED,
            'borrowed_at' => now(),
        ]);

        return $this->resource(
            new BorrowingResource($borrowing->fresh()->load(['user', 'studentDocument'])),
            'Borrowing marked as picked up successfully'
        );
    }

    /**
     * Mark borrowing as returned.
     */
    public function return(ReturnBorrowingRequest $request, string $id)
    {
        $borrowing = Borrowing::findOrFail($id);

        $this->authorize('return', Borrowing::class);

        if (!in_array($borrowing->status, [BorrowingStatus::BORROWED, BorrowingStatus::APPROVED, BorrowingStatus::OVERDUE])) {
            return $this->error(
                'Only borrowed or overdue items can be marked as returned.',
                422
            );
        }

        $validated = $request->validated();

        DB::transaction(function () use ($borrowing, $validated) {
            $borrowing->update([
                'status' => BorrowingStatus::RETURNED,
                'returned_at' => now(),
                'admin_notes' => $validated['admin_notes'] ?? $borrowing->admin_notes,
            ]);
        });

        return $this->resource(
            new BorrowingResource($borrowing->fresh()->load(['user', 'studentDocument'])),
            'Borrowing returned successfully'
        );
    }
}
