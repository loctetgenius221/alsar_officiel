<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('membership_code')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['homme', 'femme']);
            $table->string('phone');
            $table->string('email')->unique();
            // Une des 14 vraies régions (resources/js/lib/senegal-regions.ts) — pas de
            // référentiel département/commune complet, cf. plan Phase 2.
            $table->string('region');
            $table->string('commune');
            $table->string('profession')->nullable();
            $table->enum('section', ['standard', 'jeunes', 'femmes'])->default('standard');
            $table->string('referral_source')->nullable();
            $table->enum('status', ['pending', 'active', 'inactive'])->default('pending');
            $table->timestamp('consent_at');
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
