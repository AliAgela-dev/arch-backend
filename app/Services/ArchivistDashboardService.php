<?php

namespace App\Services;

use App\Enums\BorrowingStatus;
use App\Models\Borrowing;
use App\Models\StudentDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ArchivistDashboardService
{
    /**
     * Get all archivist dashboard statistics.
     */
    public function getStatistics(): array
    {
        return [
            'capacity_warning' => $this->getStorageCapacityWarning(),
            'summary' => $this->getKpiSummary(),
            'ocr_queue' => $this->getOcrQueueToday(),
            'recent_activity_list' => $this->getRecentActivity(),
        ];
    }

    /**
     * Storage capacity warning (>= 95% usage on any drawer).
     */
    protected function getStorageCapacityWarning(): array
    {
        $drawers = DB::table('drawers')
            ->leftJoin('students', 'drawers.id', '=', 'students.drawer_id')
            ->leftJoin('cabinets', 'drawers.cabinet_id', '=', 'cabinets.id')
            ->select(
                'drawers.id',
                'drawers.label',
                'drawers.number',
                'drawers.capacity',
                'cabinets.name as cabinet_name',
                DB::raw('COUNT(students.id) as current_count')
            )
            ->groupBy(
                'drawers.id',
                'drawers.label',
                'drawers.number',
                'drawers.capacity',
                'cabinets.name'
            )
            ->get();

        $threshold = 95;
        $topDrawer = null;
        $topPercent = 0;

        foreach ($drawers as $drawer) {
            $capacity = (int) ($drawer->capacity ?? 0);
            $count = (int) $drawer->current_count;
            $percent = $capacity > 0 ? (int) round(($count / $capacity) * 100) : 0;

            if ($percent >= $threshold && $percent >= $topPercent) {
                $topDrawer = $drawer;
                $topPercent = $percent;
            }
        }

        if (!$topDrawer) {
            return ['show' => false];
        }

        $drawerLabel = trim((string) ($topDrawer->label ?? ''));
        $drawerNumber = (string) ($topDrawer->number ?? '');
        $drawerCombined = $drawerLabel !== '' ? $drawerLabel : ($drawerNumber !== '' ? "Drawer {$drawerNumber}" : null);

        return [
            'show' => true,
            'cabinetName' => $topDrawer->cabinet_name,
            'drawer' => $drawerCombined,
            'usagePercent' => $topPercent,
            'overrideAllowed' => true,
        ];
    }

    /**
     * KPI summary cards.
     */
    protected function getKpiSummary(): array
    {
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        $totalFiles = StudentDocument::count();
        $borrowedNow = Borrowing::whereIn('status', [
            BorrowingStatus::BORROWED->value,
            BorrowingStatus::OVERDUE->value,
        ])->count();

        $overdue = Borrowing::overdue()->count();

        $scansToday = StudentDocument::whereDate('created_at', $today)->count();
        $scansYesterday = StudentDocument::whereDate('created_at', $yesterday)->count();

        if ($scansYesterday > 0) {
            $scansTodayChangePct = round((($scansToday - $scansYesterday) / $scansYesterday) * 100, 1);
        } else {
            $scansTodayChangePct = $scansToday > 0 ? 100 : 0;
        }

        return [
            'totalFiles' => $totalFiles,
            'borrowedNow' => $borrowedNow,
            'overdue' => $overdue,
            'scansToday' => $scansToday,
            'scansTodayChangePct' => $scansTodayChangePct,
        ];
    }

    /**
     * OCR queue counts for documents created today.
     */
    protected function getOcrQueueToday(): array
    {
        if (!Schema::hasColumn('student_documents', 'ocr_status')) {
            return [
                'pending' => 0,
                'completed' => 0,
                'failed' => 0,
            ];
        }

        $today = now()->toDateString();

        $counts = StudentDocument::whereDate('created_at', $today)
            ->select('ocr_status', DB::raw('COUNT(*) as total'))
            ->groupBy('ocr_status')
            ->pluck('total', 'ocr_status');

        return [
            'pending' => (int) ($counts['pending'] ?? 0),
            'completed' => (int) ($counts['completed'] ?? 0),
            'failed' => (int) ($counts['failed'] ?? 0),
        ];
    }

    /**
     * Recent activity for archivist-relevant entities.
     */
    protected function getRecentActivity(int $limit = 10): array
    {
        if (!Schema::hasTable('audit_logs')) {
            return [];
        }

        $columns = Schema::getColumnListing('audit_logs');

        $actionColumn = in_array('action', $columns, true)
            ? 'action'
            : (in_array('event', $columns, true) ? 'event' : (in_array('description', $columns, true) ? 'description' : null));

        $entityColumn = in_array('entity', $columns, true)
            ? 'entity'
            : (in_array('entity_type', $columns, true)
                ? 'entity_type'
                : (in_array('subject_type', $columns, true)
                    ? 'subject_type'
                    : (in_array('auditable_type', $columns, true) ? 'auditable_type' : null)));

        $timeColumn = in_array('created_at', $columns, true)
            ? 'created_at'
            : (in_array('time', $columns, true) ? 'time' : (in_array('occurred_at', $columns, true) ? 'occurred_at' : null));

        if (!$actionColumn || !$entityColumn || !$timeColumn) {
            return [];
        }

        $query = DB::table('audit_logs');

        if (in_array('user_id', $columns, true)) {
            $query->leftJoin('users', 'audit_logs.user_id', '=', 'users.id');
        }

        $query->where(function ($q) use ($entityColumn) {
            $q->whereIn("audit_logs.$entityColumn", ['file', 'document', 'borrowing'])
                ->orWhere("audit_logs.$entityColumn", 'like', '%file%')
                ->orWhere("audit_logs.$entityColumn", 'like', '%document%')
                ->orWhere("audit_logs.$entityColumn", 'like', '%borrowing%');
        });

        $select = [
            "audit_logs.$actionColumn as action",
            "audit_logs.$entityColumn as entity",
            "audit_logs.$timeColumn as time",
        ];

        if (in_array('user_id', $columns, true)) {
            $select[] = 'users.name as user_name';
        } else {
            $select[] = DB::raw('NULL as user_name');
        }

        $rows = $query->select($select)
            ->orderBy("audit_logs.$timeColumn", 'desc')
            ->limit($limit)
            ->get();

        return $rows->map(function ($row) {
            return [
                'user' => $row->user_name ?? null,
                'action' => $row->action,
                'entity' => $row->entity,
                'time' => $row->time,
            ];
        })->toArray();
    }
}
