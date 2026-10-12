<?php

namespace Database\Seeders;

use App\Support\AdminRoles;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Rejouable : `syncPermissions` réaligne un rôle existant sur le catalogue (AdminRoles) à
 * chaque exécution, donc retirer une permission dans le code la retire aussi en base — un
 * `findOrCreate` seul laisserait traîner d'anciens droits accordés par une version précédente.
 */
class RolesSeeder extends Seeder
{
    public function run(): void
    {
        // spatie met les permissions en cache 24 h : sans ce vidage, les droits fraîchement
        // créés ne seraient pris en compte qu'au prochain cycle de cache.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (AdminRoles::permissions() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        foreach (AdminRoles::map() as $role => $permissions) {
            Role::findOrCreate($role, 'web')->syncPermissions($permissions);
        }
    }
}
