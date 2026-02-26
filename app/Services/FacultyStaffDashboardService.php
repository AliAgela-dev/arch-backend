<?php

namespace App\Services;

use App\Enums\BorrowingStatus;
use App\Models\Borrowing;
use App\Models\StudentDocument;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class FacultyStaffDashboardService
{
    protected User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Get all dashboard statistics for faculty staff.
     */
    public function getStatistics(): array
    {
        return [
            'summary' => $this->getSummaryStats(),
            'recent_borrowings' => $this->getRecentBorrowings(),
            'recent_requests' => $this->getRecentRequests(),
            'overdue_files' => $this->getOverdueFiles(),
        ];
    }

    /**
     * Get summary statistics for the dashboard cards.
     */
    protected function getSummaryStats(): array
    {
        $facultyIds = $this->user->faculties->pluck('id');

        return [
            'total_faculty_files' => StudentDocument::whereHas('student.faculty', function ($query) use ($facultyIds) {
                $query->whereIn('faculties.id', $facultyIds);
            })->count(),
            'active_borrowings_by_user' => Borrowing::where('user_id', $this->user->id)
                ->whereIn('status', [
                    BorrowingStatus::APPROVED->value,
                    BorrowingStatus::BORROWED->value,
                ])->count(),
            'active_requests_count' => Borrowing::whereHas('studentDocument.student.faculty', function ($query) use ($facultyIds) {
                $query->whereIn('faculties.id', $facultyIds);
            })->whereIn('status', [
                BorrowingStatus::PENDING->value,
            ])->count(),
            'overdue_files_count' => Borrowing::whereHas('studentDocument.student.faculty', function ($query) use ($facultyIds) {
                $query->whereIn('faculties.id', $facultyIds);
            })->where('status', BorrowingStatus::BORROWED->value)
                ->where('due_date', '<', now())
                ->count(),
        ];
    }

    /**
     * Get recent borrowings with their data.
     */
    protected function getRecentBorrowings(): array
    {
        $facultyIds = $this->user->faculties->pluck('id');

        return Borrowing::with([
            'studentDocument',
            'studentDocument.student',
            'studentDocument.student.faculty',
            'studentDocument.documentType',
            'user'
        ])
        ->whereHas('studentDocument.student.faculty', function ($query) use ($facultyIds) {
            $query->whereIn('faculties.id', $facultyIds);
        })
        ->orderByDesc('created_at')
        ->limit(10)
        ->get()
        ->map(function ($borrowing) {
            return [
                'document_title' => $borrowing->studentDocument->documentType->name ?? 'Unknown Document',
                'document_code' => $borrowing->studentDocument->file_number,
                'student_name' => $borrowing->studentDocument->student->name,
                'faculty_name' => $borrowing->studentDocument->student->faculty->name_en,
                'borrowed_by' => $borrowing->user->name,
                'status' => $borrowing->status,
                'borrowed_at' => $borrowing->created_at->format('Y-m-d H:i:s'),
                'due_date' => $borrowing->due_date->format('Y-m-d'),
                'returned_at' => $borrowing->returned_at?->format('Y-m-d H:i:s'),
            ];
        })->toArray();
    }

    /**
     * Get active requests count.
     */
    protected function getActiveRequests(): int
    {
        $facultyIds = $this->user->faculties->pluck('id');

        return Borrowing::whereHas('studentDocument.student.faculty', function ($query) use ($facultyIds) {
            $query->whereIn('faculties.id', $facultyIds);
        })->whereIn('status', [
            BorrowingStatus::PENDING->value,
        ])->count();
    }

    /**
     * Get recent requests with their data.
     */
    protected function getRecentRequests(): array
    {
        $facultyIds = $this->user->faculties->pluck('id');

        return Borrowing::with([
            'studentDocument',
            'studentDocument.student',
            'studentDocument.student.faculty',
            'studentDocument.documentType',
            'user'
        ])
        ->whereHas('studentDocument.student.faculty', function ($query) use ($facultyIds) {
            $query->whereIn('faculties.id', $facultyIds);
        })
        ->where('status', BorrowingStatus::PENDING->value)
        ->orderByDesc('created_at')
        ->limit(10)
        ->get()
        ->map(function ($borrowing) {
            return [
                'document_title' => $borrowing->studentDocument->documentType->name ?? 'Unknown Document',
                'document_code' => $borrowing->studentDocument->file_number,
                'student_name' => $borrowing->studentDocument->student->name,
                'faculty_name' => $borrowing->studentDocument->student->faculty->name_en,
                'requested_by' => $borrowing->user->name,
                'status' => $borrowing->status,
                'request_type' => 'borrowing',
                'created_at' => $borrowing->created_at->format('Y-m-d H:i:s'),
            ];
        })->toArray();
    }

    /**
     * Get overdue files.
     */
    protected function getOverdueFiles(): array
    {
        $facultyIds = $this->user->faculties->pluck('id');

        return Borrowing::with([
            'studentDocument',
            'studentDocument.student',
            'studentDocument.student.faculty',
            'studentDocument.documentType',
            'user'
        ])
        ->whereHas('studentDocument.student.faculty', function ($query) use ($facultyIds) {
            $query->whereIn('faculties.id', $facultyIds);
        })
        ->where('status', BorrowingStatus::BORROWED->value)
        ->where('due_date', '<', now())
        ->orderByDesc('due_date')
        ->get()
        ->map(function ($borrowing) {
            return [
                'document_title' => $borrowing->studentDocument->documentType->name ?? 'Unknown Document',
                'document_code' => $borrowing->studentDocument->file_number,
                'student_name' => $borrowing->studentDocument->student->name,
                'faculty_name' => $borrowing->studentDocument->student->faculty->name_en,
                'borrowed_by' => $borrowing->user->name,
                'due_date' => $borrowing->due_date->format('Y-m-d'),
                'days_overdue' => now()->diffInDays($borrowing->due_date),
                'borrowed_at' => $borrowing->created_at->format('Y-m-d H:i:s'),
            ];
        })->toArray();
    }
}