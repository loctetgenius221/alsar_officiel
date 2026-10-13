<?php

/*
| Domaine officiel : www.alsar.sn (forme canonique ; alsar.sn y est redirigé par .htaccess).
|
| Tout ce qui porte une URL dans ce projet la construit à partir d'APP_URL — aucun domaine
| n'est écrit en dur. Ces tests verrouillent cette propriété là où une erreur serait la plus
| coûteuse : le QR code de la carte de membre et le lien de l'email de confirmation sont
| IMPRIMÉS et DISTRIBUÉS. Un QR pointant sur localhost ou sur le vhost de développement ne se
| rattrape pas une fois la carte entre les mains de l'adhérent.
|
| D'où aussi le contrôle des valeurs de repli : si APP_URL venait à manquer en production, le
| repli doit rester le domaine officiel, pas `http://localhost`.
*/

use App\Domain\Content\Models\Article;
use App\Domain\Content\Models\Category;
use App\Domain\Members\Actions\BuildMembershipQrCode;
use App\Domain\Members\Models\Member;
use App\Mail\MembershipConfirmed;
use Database\Seeders\PageContentSeeder;
use Illuminate\Support\Facades\URL;

/** Place l'application sur le domaine officiel, comme en production. */
function onOfficialDomain(): void
{
    config(['app.url' => 'https://www.alsar.sn']);
    URL::forceRootUrl('https://www.alsar.sn');
    URL::forceScheme('https');
}

it('garde le domaine officiel en repli si APP_URL venait à manquer', function (string $file, string $fallback) {
    // On lit le FICHIER de configuration et non `config(...)` : en local le `.env` fournit une
    // valeur, qui masquerait justement le repli que l'on veut vérifier. Or c'est bien ce repli
    // qui s'appliquerait sur un serveur de production dont le `.env` aurait perdu la variable —
    // et il finirait encodé dans des QR codes imprimés.
    expect(file_get_contents(base_path($file)))->toContain($fallback);
})->with([
    'URL de l’application' => ['config/app.php', "env('APP_URL', 'https://www.alsar.sn')"],
    'URL des fichiers stockés' => ['config/filesystems.php', "env('APP_URL', 'https://www.alsar.sn')"],
    'expéditeur des emails' => ['config/mail.php', "env('MAIL_FROM_ADDRESS', 'contact@alsar.sn')"],
]);

it('encode le domaine officiel dans le lien de vérification du QR', function () {
    onOfficialDomain();

    $member = Member::factory()->active()->create();

    // C'est exactement la donnée passée au générateur de QR (BuildMembershipQrCode).
    expect(route('verifier', $member->membership_code))
        ->toBe("https://www.alsar.sn/verifier/{$member->membership_code}");
});

it('produit un QR exploitable pour un adhérent', function () {
    onOfficialDomain();

    $qr = (new BuildMembershipQrCode)->handle(Member::factory()->active()->create());

    expect($qr)->toStartWith('data:image/png;base64,')
        ->and(base64_decode(substr($qr, strlen('data:image/png;base64,')), true))->not->toBeFalse();
});

it('met le lien de vérification sur le domaine officiel dans l’email de confirmation', function () {
    onOfficialDomain();

    $member = Member::factory()->active()->create();
    $html = (new MembershipConfirmed($member))->render();

    expect($html)->toContain("https://www.alsar.sn/verifier/{$member->membership_code}");
});

it('sert la carte de membre en PDF sur le domaine officiel', function () {
    onOfficialDomain();

    $member = Member::factory()->active()->create();

    expect(route('adhesion.card', $member->membership_code))
        ->toBe("https://www.alsar.sn/adhesion/carte/{$member->membership_code}");

    $this->get("/adhesion/carte/{$member->membership_code}")
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('ne laisse aucun domaine écrit en dur dans la génération des URLs', function () {
    // La preuve par le changement : si un domaine était figé quelque part, l'URL ne suivrait
    // pas APP_URL. C'est ce qui permet de déployer le même code en local et en production.
    config(['app.url' => 'http://exemple-de-controle.test']);
    URL::forceRootUrl('http://exemple-de-controle.test');

    expect(route('verifier', 'CODE123'))->toBe('http://exemple-de-controle.test/verifier/CODE123');
});

it('publie un sitemap XML qui annonce le même domaine que les pages', function () {
    $this->seed(PageContentSeeder::class);
    $article = Article::factory()->for(Category::factory())->create(['published_at' => now()->subDay()]);

    $response = $this->get('/sitemap.xml');

    $response->assertOk()->assertHeader('content-type', 'application/xml; charset=utf-8');

    $xml = $response->getContent();

    expect($xml)->toStartWith('<?xml version="1.0" encoding="UTF-8"?>')
        ->and($xml)->toContain('<loc>'.route('home').'</loc>')
        ->and($xml)->toContain('<loc>'.route('actualites.show', $article->slug).'</loc>')
        // Ni le back-office ni les URLs portant un identifiant d'adhérent.
        ->and($xml)->not->toContain('/admin')
        ->and($xml)->not->toContain('/verifier/')
        ->and($xml)->not->toContain('/adhesion/carte/');
});

it('n’annonce dans le sitemap que des pages réellement servies', function () {
    $this->seed(PageContentSeeder::class);

    $xml = $this->get('/sitemap.xml')->getContent();

    preg_match_all('#<loc>([^<]+)</loc>#', $xml, $matches);

    expect($matches[1])->not->toBeEmpty();

    foreach ($matches[1] as $url) {
        $path = parse_url($url, PHP_URL_PATH) ?: '/';
        expect($this->get($path)->getStatusCode())->toBe(200, $path);
    }
});

it('oriente les robots vers le sitemap du domaine officiel', function () {
    $robots = file_get_contents(public_path('robots.txt'));

    expect($robots)->toContain('Sitemap: https://www.alsar.sn/sitemap.xml')
        ->and($robots)->toContain('Disallow: /admin')
        // Les pages personnelles restent explorables pour que leur `noindex` soit lu.
        ->and($robots)->not->toContain('Disallow: /verifier');
});
