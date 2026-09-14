<?php

namespace Tests\Feature;

use Database\Seeders\PageContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    // `uses(RefreshDatabase::class)->in('Feature')` dans tests/Pest.php ne s'applique qu'aux
    // fichiers écrits en syntaxe fonctionnelle Pest (it()/test()) — cette classe PHPUnit
    // classique doit déclarer le trait elle-même. Passait inaperçu tant que `/` ne
    // touchait pas la base ; la page d'accueil interroge désormais les articles publiés.
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        // `/` lit désormais aussi PageContent (slug 'home', cf. HomeController) —
        // Storage::fake évite que le seeder écrive de vrais fichiers dans storage/app/public
        // pendant les tests.
        Storage::fake('public');
        $this->seed(PageContentSeeder::class);

        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
