<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->enum('tier', ['standard', 'soutien', 'bienfaiteur']);
            // Montant recalculé serveur depuis config('adhesion.tiers') — jamais depuis le
            // client, cf. plan Phase 2 (sécurité).
            $table->unsignedInteger('amount');
            $table->string('currency', 3)->default('XOF');
            $table->string('gateway');
            $table->string('gateway_reference')->nullable()->unique();
            $table->enum('status', ['pending', 'success', 'failed', 'refunded'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->string('receipt_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_payments');
    }
};
