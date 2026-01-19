<?php

namespace App\Policies;

use App\Enums\BorrowingStatus;
use App\Enums\UserRole;
use App\Models\Borrowing;
use App\Models\User;

class BorrowingPolicy
{
    /**
     * Determine whether the user can view any borrowings.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view borrowings
        // (Controller will filter based on role)
        return true;
    }

    /**
     * Determine whether the user can view the borrowing.
     */
    public function view(User $user, Borrowing $borrowing): bool
    {
        // Users can view their own borrowings
        // Archivists and admins can view all borrowings
        return $user->id === $borrowing->user_id
            || $user->hasRole([UserRole::archivist->value, UserRole::super_admin->value]);
    }

    /**
     * Determine whether the user can create borrowings.
     */
    public function create(User $user): bool
    {
        // Only faculty_staff can create borrowing requests
        return $user->hasRole(UserRole::faculty_staff->value);
    }

    /**
     * Determine whether the user can update the borrowing.
     */
    public function update(User $user, Borrowing $borrowing): bool
    {
        // Users can only update their own pending requests
        return $user->id === $borrowing->user_id
            && $borrowing->status === BorrowingStatus::PENDING;
    }

    /**
     * Determine whether the user can delete the borrowing.
     */
    public function delete(User $user, Borrowing $borrowing): bool
    {
        // Users can cancel their own pending requests
        if ($user->id === $borrowing->user_id && $borrowing->status === BorrowingStatus::PENDING) {
            return true;
        }

        // Admins can delete any borrowing
        return $user->hasRole(UserRole::super_admin->value);
    }

    /**
     * Determine whether the user can approve/reject borrowings.
     */
    public function approve(User $user): bool
    {
        // Only archivists and admins can approve/reject
        return $user->hasRole([UserRole::archivist->value, UserRole::super_admin->value]);
    }

    /**
     * Determine whether the user can mark borrowing as returned.
     */
    public function return(User $user): bool
    {
        // Only archivists and admins can mark as returned
        return $user->hasRole([UserRole::archivist->value, UserRole::super_admin->value]);
    }

    /**
     * Determine whether the user can mark borrowing as borrowed (picked up).
     */
    public function markBorrowed(User $user): bool
    {
        // Only archivists and admins can mark as borrowed
        return $user->hasRole([UserRole::archivist->value, UserRole::super_admin->value]);
    }
}
