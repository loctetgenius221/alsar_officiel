<?php

/*
| Journal d'audit — lisibilité des lignes.
|
| Le journal affichait « Article #12 » : un numéro de ligne n'aide personne à relire ce qui
| s'est passé, et c'est précisément la clé primaire qu'on a cessé d'exposer partout ailleurs
| (App\Support\HasPublicId). Ces tests verrouillent les deux cas qui en découlent : nommer
| l'objet quand il existe, dire qu'il a été supprimé quand il n'existe plus.
*/

use App\Domain\Content\Models\Article;
use App\Domain\Content\Models\Category;
use App\Domain\Members\Models\Member;
use App\Models\User;
use App\Support\AdminRoles;
use Database\Seeders\RolesSeeder;

beforeEach(function () {
    $this->seed(RolesSeeder::class);

    $this->admin = User::factory()->create(['name' => 'Racine Admin']);
    $this->admin->assignRole(AdminRoles::SUPER_ADMIN);
});

it("nomme l'objet concerné plutôt que d'afficher sa clé primaire", function () {
    $article = Article::factory()->for(Category::factory())->create(['title' => 'Meeting à Podor']);

    activity()->causedBy($this->admin)->performedOn($article)->log('Article modifié');

    $this->actingAs($this->admin)
        ->get('/admin/journal-audit')
        ->assertInertia(fn ($page) => $page
            ->where('activities.data.0.subjectLabel', 'Meeting à Podor')
            ->where('activities.data.0.subjectType', 'Article')
            ->where('activities.data.0.causerName', 'Racine Admin')
        );
});

it("utilise le nom complet d'un adhérent", function () {
    $member = Member::factory()->create(['first_name' => 'Awa', 'last_name' => 'Diop']);

    activity()->causedBy($this->admin)->performedOn($member)->log('Statut modifié');

    $this->actingAs($this->admin)
        ->get('/admin/journal-audit')
        ->assertInertia(fn ($page) => $page->where('activities.data.0.subjectLabel', 'Awa Diop'));
});

it('indique que l’objet a été supprimé depuis', function () {
    // Cas fréquent : on relit le journal pour comprendre une suppression, donc l'objet n'est
    // justement plus là. Un tiret laisserait croire à une donnée manquante.
    $article = Article::factory()->for(Category::factory())->create();
    activity()->causedBy($this->admin)->performedOn($article)->log('Article supprimé');
    $article->forceDelete();

    $this->actingAs($this->admin)
        ->get('/admin/journal-audit')
        ->assertInertia(fn ($page) => $page->where('activities.data.0.subjectLabel', 'supprimé depuis'));
});

it('attribue au « Système » une action sans auteur identifié', function () {
    $article = Article::factory()->for(Category::factory())->create(['title' => 'Publication automatique']);

    activity()->performedOn($article)->log('Article publié');

    $this->actingAs($this->admin)
        ->get('/admin/journal-audit')
        ->assertInertia(fn ($page) => $page->where('activities.data.0.causerName', 'Système'));
});
