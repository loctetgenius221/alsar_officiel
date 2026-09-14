<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Retire le plafond artificiel de 255 caractères sur les champs texte libre édités depuis le
 * back-office (Actualités + Contenu des pages) — demande explicite de suppression des
 * validations de confort. `MODIFY COLUMN` en SQL brut plutôt que `Schema::table()->change()` :
 * ce dernier exige `doctrine/dbal`, absent du projet, pour une opération qu'une seule requête
 * SQL couvre (le projet cible MySQL en dev/prod, cf. CLAUDE.md).
 *
 * No-op sous SQLite (tests, `phpunit.xml`) : `ALTER TABLE ... MODIFY` n'existe pas dans sa
 * syntaxe, et c'est de toute façon inutile — SQLite n'applique aucune limite de longueur réelle
 * sur une colonne déclarée VARCHAR (typage dynamique), le plafond n'existait donc que côté MySQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE articles MODIFY title TEXT NOT NULL');
        DB::statement('ALTER TABLE articles MODIFY seo_title TEXT NULL');
        DB::statement('ALTER TABLE articles MODIFY seo_description TEXT NULL');

        DB::statement('ALTER TABLE page_contents MODIFY seo_title TEXT NULL');
        DB::statement('ALTER TABLE page_contents MODIFY seo_description TEXT NULL');
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE articles MODIFY title VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE articles MODIFY seo_title VARCHAR(255) NULL');
        DB::statement('ALTER TABLE articles MODIFY seo_description VARCHAR(255) NULL');

        DB::statement('ALTER TABLE page_contents MODIFY seo_title VARCHAR(255) NULL');
        DB::statement('ALTER TABLE page_contents MODIFY seo_description VARCHAR(255) NULL');
    }
};
