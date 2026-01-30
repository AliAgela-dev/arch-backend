<?php

namespace App\Http\Controllers\FacultyStaff\Dashboard;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\User;
use App\Enums\UserRole;
use App\Services\FacultyStaffDashboardService;

class DashboardController extends Controller
{
    use ApiResponse;
    public function index(): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();
        
        // Check if user has faculty staff access
        if (!$user->hasRole([UserRole::faculty_staff->value])) {
            return $this->forbidden('You do not have permission to view dashboard statistics.');
        }
        
        $facultyStaffDashboardService = new FacultyStaffDashboardService($user);
        $statistics = $facultyStaffDashboardService->getStatistics();

        return $this->success($statistics);
    }
}
