<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * Un seul rôle pour l'instant (Super-admin) — Éditeur/Trésorier/Community Manager
 * viendront avec le lot CMS (cf. plan Phase 3), sans qu'il faille retoucher cette structure.
 */
class RolesSeeder extends Seeder
{
    public function run(): void
    {
        Role::findOrCreate('Super-admin', 'web');
    }
}
