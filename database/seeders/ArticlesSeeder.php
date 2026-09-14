<?php

namespace Database\Seeders;

use App\Domain\Content\Models\Article;
use App\Domain\Content\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Migration des 9 actualités réelles jusqu'ici en dur dans resources/js/lib/articles.ts
 * (titres/dates/catégories tirés de l'audit, cf. CLAUDE.md) vers la vraie table `articles`
 * — le contenu détaillé (corps, extrait) n'existait pas dans l'audit, donc pas inventé ici
 * non plus : ces 9 entrées restent avec `body`/`excerpt` à NULL, à compléter par un éditeur
 * via le nouveau back-office. Rejouable (updateOrCreate par slug), mêmes slugs que
 * l'ancienne source pour ne pas casser les URLs déjà partagées.
 */
class ArticlesSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::where('email', config('admin.email'))->first();

        $categories = Category::all()->keyBy(fn (Category $category) => $category->name);

        $articles = [
            ['alsar-sagatta-mobilisation-delegation-locale', 'Vie du parti', '2026-06-16', 'ALSAR Sagatta — mobilisation d’une délégation locale', '/images/slider-img-1.webp'],
            ['fete-du-travail-racine-sy-patronat', 'Vie nationale', '2026-05-01', 'Fête du Travail : Racine Sy représente le patronat', null],
            ['remise-pacte-stabilite-sociale-president', 'Vie nationale', '2026-04-24', 'Remise du Pacte de stabilité sociale au Président de la République', null],
            ['mairie-podor-autorisation-investissements', 'Action à Podor', '2026-04-04', 'Mairie de Podor : autorisation spéciale pour renforcer les investissements', null],
            ['hommage-ousmane-abdoulaye-oumar-thierno-baba-ly', 'Solidarité', '2026-03-30', 'Hommage à Ousmane Abdoulaye Oumar Thierno Baba Ly, soufi du Fouta', null],
            ['soutien-etudiants-podor', 'Action à Podor', '2026-03-23', 'Soutien aux étudiants de Podor', null],
            ['ramadan-2026-distribution-boeufs-podor', 'Action à Podor', '2026-03-23', 'Ramadan 2026 : distribution de bœufs aux nécessiteux à Podor', null],
            ['ramadan-podor-racine-sy-tradition', 'Action à Podor', '2026-03-04', 'Ramadan à Podor : Racine Sy perpétue la tradition', null],
            ['hommage-seydou-madani-sy', 'Solidarité', '2026-02-21', '40e jour de la disparition de Seydou Madani Sy : témoignage de Racine Sy', null],
        ];

        foreach ($articles as [$slug, $categoryName, $date, $title, $coverImage]) {
            Article::updateOrCreate(
                ['slug' => $slug],
                [
                    'category_id' => $categories[$categoryName]->id,
                    'author_id' => $author?->id,
                    'title' => $title,
                    'cover_image' => $coverImage,
                    'published_at' => $date,
                ],
            );
        }
    }
}
