<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Hash;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Creating Faculty Staff User ===\n\n";

try {
    // Check if user already exists
    $user = User::where('email', 'faculty@staff.com')->first();
    
    if (!$user) {
        // Create new user
        $user = User::create([
            'name' => 'Faculty Staff User',
            'email' => 'faculty@staff.com',
            'password' => Hash::make('password123')
        ]);
        echo "Created new user\n";
    } else {
        echo "Using existing user\n";
    }

    // Assign faculty staff role
    $user->assignRoleWithHierarchy(UserRole::faculty_staff->value);

    echo "Faculty staff user setup completed!\n\n";
    echo "Email: faculty@staff.com\n";
    echo "Password: password123\n";
    echo "Role: faculty_staff\n\n";

    echo "You can now login with these credentials and test the dashboard.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
