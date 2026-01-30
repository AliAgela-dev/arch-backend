<?php

namespace App\Http\Controllers\Admin\Dashboard;

use App\Enums\UserRole;
use App\Http\Controllers\Admin\AdminController;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;

/**
 * @tags Dashboard
 */
class DashboardController extends AdminController
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {}

    /**
     * Get dashboard statistics.
     *
     * Returns summary metrics, storage usage, and faculty distribution.
     * Only accessible by super_admin and archivist roles.
     */
    public function index(): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        // Check if user has admin access
        if (!$user->hasRole([UserRole::super_admin->value, UserRole::archivist->value])) {
            return $this->forbidden('You do not have permission to view dashboard statistics.');
        }

        $statistics = $this->dashboardService->getStatistics();

        return $this->success($statistics);
    }
}
