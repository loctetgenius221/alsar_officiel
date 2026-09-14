<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Rejouable (updateOrCreate par email) — identifiants pilotés par .env, rien en dur.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('admin.email');
        $password = config('admin.password');

        if (! $email || ! $password) {
            $this->command?->warn('ADMIN_EMAIL / ADMIN_PASSWORD absents de .env — compte admin non créé.');

            return;
        }

        $admin = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => config('admin.name', 'Super Admin'),
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ],
        );

        $admin->syncRoles(['Super-admin']);
    }
}
