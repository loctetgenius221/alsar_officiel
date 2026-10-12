<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Blocage d'un compte back-office.
 *
 * Un horodatage plutôt qu'un booléen `is_blocked` : on veut pouvoir dire DEPUIS QUAND un accès
 * est suspendu (journal d'audit, litige interne), information qu'un booléen perd.
 *
 * Et un blocage plutôt qu'une suppression : les articles portent un `author_id`, et supprimer
 * un compte effacerait la paternité des publications (cf. `nullOnDelete` sur articles).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('blocked_at')->nullable()->after('email_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('blocked_at');
        });
    }
};
