<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('profession');
            $table->string('address')->nullable()->after('commune');
            // `text` plutôt que `string` : la valeur chiffrée (cast `encrypted`) est bien plus
            // longue que le numéro en clair.
            $table->text('id_document_number')->nullable()->after('address');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn(['photo_path', 'address', 'id_document_number']);
        });
    }
};
