<?php

/*
| Les URLs du back-office ne portent plus de clé primaire.
|
| Pourquoi c'est un sujet de sécurité et pas de cosmétique : `/admin/adherents/42` annonce
| qu'il y a au moins 42 adhérents, dit dans quel ordre ils se sont inscrits, et invite à
| parcourir les fiches en incrémentant un nombre. L'autorisation reste portée par les
| middlewares `can:` — l'opacité de l'URL est une couche de plus, jamais la seule.
|
| Ces tests verrouillent les deux garanties : un identifiant séquentiel ne résout plus rien,
| et aucune clé primaire n'est transmise à l'interface (qui, sinon, la remettrait dans l'URL).
*/

use App\Domain\Content\Models\Article;
use App\Domain\Content\Models\Category;
use App\Domain\Members\Models\Member;
use App\Domain\Payments\Models\MembershipPayment;
use App\Models\User;
use App\Support\AdminRoles;
use Database\Seeders\RolesSeeder;

beforeEach(function () {
    $this->seed(RolesSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole(AdminRoles::SUPER_ADMIN);
});

it('attribue un identifiant public opaque à la création', function (string $class) {
    $model = $class === Article::class
        ? Article::factory()->for(Category::factory())->create()
        : $class::factory()->create();

    expect($model->public_id)
        ->toBeString()
        ->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/')
        // C'est bien cet identifiant que les URLs utiliseront, pas `id`.
        ->and($model->getRouteKey())->toBe($model->public_id)
        ->and($model->getRouteKey())->not->toBe((string) $model->id);
})->with([User::class, Article::class, MembershipPayment::class]);

it("résout l'adhérent par son code d'adhésion, qui est déjà aléatoire", function () {
    $member = Member::factory()->create();

    expect($member->getRouteKey())->toBe($member->membership_code)
        ->and($member->membership_code)->toHaveLength(10);
});

it('ne résout plus une fiche par sa clé primaire', function () {
    $target = User::factory()->create();
    $target->assignRole(AdminRoles::WRITER);

    $article = Article::factory()->for(Category::factory())->create();
    $member = Member::factory()->create();
    $payment = MembershipPayment::factory()->create(['member_id' => $member->id]);

    $urls = [
        "/admin/utilisateurs/{$target->id}/modifier",
        "/admin/actualites/{$article->id}/modifier",
        "/admin/adherents/{$member->id}",
    ];

    foreach ($urls as $url) {
        expect($this->actingAs($this->admin)->get($url)->getStatusCode())->toBe(404, $url);
    }

    $this->actingAs($this->admin)
        ->post("/admin/paiements/{$payment->id}/confirmer")
        ->assertNotFound();
});

it('résout bien la fiche par son identifiant public', function () {
    $target = User::factory()->create();
    $target->assignRole(AdminRoles::WRITER);

    $this->actingAs($this->admin)
        ->get("/admin/utilisateurs/{$target->getRouteKey()}/modifier")
        ->assertOk();
});

it("ne transmet aucune clé primaire à l'interface", function () {
    // Si le serveur envoyait encore `id`, rien n'empêcherait une page de le remettre dans une
    // URL — c'est exactement ce qu'on vient de supprimer.
    $target = User::factory()->create();
    $target->assignRole(AdminRoles::CONTENT_MANAGER);

    $this->actingAs($this->admin)
        ->get('/admin/utilisateurs')
        ->assertInertia(fn ($page) => $page
            ->has('users.data.0.publicId')
            ->missing('users.data.0.id')
        );

    $this->actingAs($this->admin)
        ->get("/admin/utilisateurs/{$target->getRouteKey()}/modifier")
        ->assertInertia(fn ($page) => $page->has('user.publicId')->missing('user.id'));
});

it("n'expose pas de clé primaire sur la liste des adhérents ni des articles", function () {
    $member = Member::factory()->create();
    Article::factory()->for(Category::factory())->create();

    $this->actingAs($this->admin)
        ->get('/admin/adherents')
        ->assertInertia(fn ($page) => $page
            ->where('members.data.0.membershipCode', $member->membership_code)
            ->missing('members.data.0.id')
        );

    $this->actingAs($this->admin)
        ->get('/admin/actualites')
        ->assertInertia(fn ($page) => $page
            ->has('articles.data.0.publicId')
            ->missing('articles.data.0.id')
        );
});
