<?php

namespace Database\Seeders;

use App\Domain\Content\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Les 4 catégories réelles déjà utilisées par les filtres du site public (CLAUDE.md,
 * audit §9) — rejouable (updateOrCreate par slug), même convention qu'AdminUserSeeder.
 */
class CategoriesSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Vie du parti', 'Vie nationale', 'Action à Podor', 'Solidarité'] as $name) {
            Category::updateOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
        }
    }
}
