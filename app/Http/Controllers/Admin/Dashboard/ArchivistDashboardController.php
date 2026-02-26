<?php

namespace App\Http\Controllers\Admin\Dashboard;

use App\Enums\UserRole;
use App\Http\Controllers\Admin\AdminController;
use App\Models\User;
use App\Services\ArchivistDashboardService;
use Illuminate\Http\JsonResponse;

/**
 * @tags Archivist Dashboard
 */
class ArchivistDashboardController extends AdminController
{
    public function __construct(
        protected ArchivistDashboardService $dashboardService
    ) {}

    /**
     * Get archivist dashboard statistics.
     */
    public function index(): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if (!$user) {
            return $this->unauthorized('Unauthorized');
        }

        if ($user->hasRole(UserRole::super_admin->value)) {
            return $this->forbidden('You do not have permission to view archivist dashboard statistics.');
        }

        if (!$user->hasRole(UserRole::archivist->value)) {
            return $this->forbidden('You do not have permission to view archivist dashboard statistics.');
        }

        $statistics = $this->dashboardService->getStatistics();

        return $this->success($statistics);
    }
}
