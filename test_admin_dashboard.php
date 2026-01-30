<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Hash;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Testing Super Admin Dashboard ===\n\n";

try {
    // Check if super admin user exists
    $adminUser = User::where('email', 'admin@test.com')->first();
    
    if (!$adminUser) {
        echo "Creating super admin user...\n";
        $adminUser = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@test.com',
            'password' => Hash::make('admin123')
        ]);
        
        // Assign super admin role
        $adminUser->assignRoleWithHierarchy(UserRole::super_admin->value);
        echo "Super admin user created\n";
    } else {
        echo "Using existing admin user: {$adminUser->email}\n";
        // Ensure user has super admin role
        if (!$adminUser->hasRole(UserRole::super_admin->value)) {
            $adminUser->assignRoleWithHierarchy(UserRole::super_admin->value);
            echo "Assigned super admin role\n";
        }
    }

    echo "\n=== Admin Dashboard Test ===\n";
    
    // Test the admin dashboard service
    $adminService = new \App\Services\DashboardService($adminUser);
    $adminStats = $adminService->getStatistics();
    
    echo "Admin dashboard statistics:\n";
    echo json_encode($adminStats, JSON_PRETTY_PRINT) . "\n\n";

    echo "=== API Testing Instructions ===\n";
    echo "Login to get admin token:\n";
    echo "POST http://127.0.0.1:8000/api/v1/login\n";
    echo "{\n";
    echo "  \"email\": \"admin@test.com\",\n";
    echo "  \"password\": \"admin123\"\n";
    echo "}\n\n";

    echo "Then test admin dashboard:\n";
    echo "GET http://127.0.0.1:8000/api/v1/admin/dashboard\n";
    echo "Authorization: Bearer YOUR_ADMIN_TOKEN\n\n";

    echo "Admin dashboard is ready for testing!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
