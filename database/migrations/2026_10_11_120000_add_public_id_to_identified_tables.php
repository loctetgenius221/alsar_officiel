<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Identifiant public opaque pour les trois modèles dont la clé primaire apparaissait dans une
 * URL du back-office (cf. App\Support\HasPublicId).
 *
 * `members` n'y figure pas : il possède déjà `membership_code`, aléatoire et unique, qui sert
 * désormais de clé de route — inutile de lui donner un second identifiant opaque.
 *
 * Nullable à dessein : rendre la colonne obligatoire imposerait un `change()` après le
 * remplissage, que SQLite (base des tests) traite en recréant la table avec ses index. Le
 * crochet `creating` du trait garantit la valeur à la création, et l'index unique garantit
 * qu'il n'y a jamais de doublon.
 */
return new class extends Migration
{
    /** @var list<string> */
    private array $tables = ['users', 'articles', 'membership_payments'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->uuid('public_id')->nullable()->unique()->after('id');
            });

            $this->backfill($table);
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropUnique([$blueprint->getTable().'_public_id_unique']);
                $blueprint->dropColumn('public_id');
            });
        }
    }

    /**
     * Les lignes déjà en base n'ont pas d'identifiant public : sans ce remplissage, leurs
     * fiches deviendraient introuvables (le lien ne résoudrait plus rien) dès le déploiement.
     */
    private function backfill(string $table): void
    {
        DB::table($table)
            ->whereNull('public_id')
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($table) {
                foreach ($rows as $row) {
                    DB::table($table)->where('id', $row->id)->update(['public_id' => (string) Str::uuid()]);
                }
            });
    }
};
