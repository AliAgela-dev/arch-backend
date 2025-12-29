<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        //define users permissions
        Permission::create(['name' => 'list users']);
        Permission::create(['name' => 'view users']);
        Permission::create(['name' => 'create users']);
        Permission::create(['name' => 'update users']);
        Permission::create(['name' => 'delete users']);

        //define roles and assign existing permissions
        $super_admin = Role::create(['name' => UserRole::super_admin->value]);
        $super_admin->givePermissionTo(Permission::all());

        $archivist = Role::create(['name' => UserRole::archivist->value]);
        $archivist->givePermissionTo(['list users', 'view users']);

        $faculty_staff = Role::create(['name' => UserRole::faculty_staff->value]);
        $faculty_staff->givePermissionTo([]);
    }
}
