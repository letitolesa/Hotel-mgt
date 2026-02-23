<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            'view dashboard',
            'view users',
            'manage users',
            'view reports',
            'manage reports',
            'view settings',
            'manage settings',
            'view profile',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo(Permission::all());

        $manager = Role::create(['name' => 'manager']);
        $manager->givePermissionTo([
            'view dashboard',
            'view users',
            'view reports',
            'manage reports',
            'view profile',
        ]);

        $user = Role::create(['name' => 'user']);
        $user->givePermissionTo([
            'view dashboard',
            'view profile',
        ]);

        // Assign role to a user (example)
        // \App\Models\User::find(1)->assignRole('admin');
    }
}