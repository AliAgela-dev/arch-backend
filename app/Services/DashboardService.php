<?php

namespace App\Services;

use App\Enums\BorrowingStatus;
use App\Models\Borrowing;
use App\Models\Faculty;
use App\Models\StudentDocument;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DashboardService
{
    /**
     * Default storage limit in bytes (2TB).
     */
    protected int $storageLimitBytes;

    /**
     * Storage warning threshold percentage.
     */
    protected int $warningThreshold;

    public function __construct()
    {
        // 2TB default, configurable via .env
        $this->storageLimitBytes = (int) config('dashboard.storage_limit_bytes', 2 * 1024 * 1024 * 1024 * 1024);
        $this->warningThreshold = (int) config('dashboard.storage_warning_threshold', 60);
    }

    /**
     * Get all dashboard statistics.
     */
    public function getStatistics(): array
    {
        return [
            'summary' => $this->getSummaryStats(),
            'storage' => $this->getStorageStats(),
            'faculty_storage_distribution' => $this->getFacultyStorageDistribution(),
            'warnings' => $this->getWarnings(),
        ];
    }

    /**
     * Get summary statistics for the dashboard cards.
     */
    protected function getSummaryStats(): array
    {
        return [
            'total_archive' => StudentDocument::count(),
            'active_borrows' => Borrowing::whereIn('status', [
                BorrowingStatus::APPROVED->value,
                BorrowingStatus::BORROWED->value,
            ])->count(),
        ];
    }

    /**
     * Get storage usage statistics.
     */
    protected function getStorageStats(): array
    {
        $usedBytes = (int) Media::sum('size');
        $totalBytes = $this->storageLimitBytes;
        $percentage = $totalBytes > 0 ? round(($usedBytes / $totalBytes) * 100) : 0;

        return [
            'used_bytes' => $usedBytes,
            'total_bytes' => $totalBytes,
            'percentage' => (int) $percentage,
            'used_formatted' => $this->formatBytes($usedBytes),
            'total_formatted' => $this->formatBytes($totalBytes),
        ];
    }

    /**
     * Get storage distribution per faculty.
     */
    protected function getFacultyStorageDistribution(): array
    {
        // Query: media -> student_documents -> students -> faculties
        $distribution = DB::table('media')
            ->join('student_documents', function ($join) {
                $join->on('media.model_id', '=', 'student_documents.id')
                    ->where('media.model_type', '=', 'App\\Models\\StudentDocument');
            })
            ->join('students', 'student_documents.student_id', '=', 'students.id')
            ->join('faculties', 'students.faculty_id', '=', 'faculties.id')
            ->select(
                'faculties.id as faculty_id',
                'faculties.name_en',
                'faculties.name_ar',
                DB::raw('COALESCE(SUM(media.size), 0) as used_bytes')
            )
            ->groupBy('faculties.id', 'faculties.name_en', 'faculties.name_ar')
            ->orderByDesc('used_bytes')
            ->get();

        // Include faculties with zero storage
        $allFaculties = Faculty::all();
        $distributionMap = $distribution->keyBy('faculty_id');

        return $allFaculties->map(function ($faculty) use ($distributionMap) {
            $data = $distributionMap->get($faculty->id);
            $usedBytes = $data ? (int) $data->used_bytes : 0;

            return [
                'faculty_id' => $faculty->id,
                'name_en' => $faculty->name_en,
                'name_ar' => $faculty->name_ar,
                'used_bytes' => $usedBytes,
                'used_formatted' => $this->formatBytes($usedBytes),
            ];
        })->sortByDesc('used_bytes')->values()->toArray();
    }

    /**
     * Get active warnings for the dashboard.
     */
    protected function getWarnings(): array
    {
        $warnings = [];
        $storage = $this->getStorageStats();

        if ($storage['percentage'] >= $this->warningThreshold) {
            $warnings[] = [
                'type' => 'storage_capacity',
                'message' => "Storage capacity is at {$storage['percentage']}%. Consider reviewing capacity.",
                'action' => 'review_capacity',
            ];
        }

        return $warnings;
    }

    /**
     * Format bytes to human-readable string.
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

        if ($bytes === 0) {
            return '0 B';
        }

        $i = (int) floor(log($bytes, 1024));
        $i = min($i, count($units) - 1);

        return round($bytes / pow(1024, $i), 1) . ' ' . $units[$i];
    }
}
