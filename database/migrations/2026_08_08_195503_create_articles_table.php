<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            // Nullable + nullOnDelete : un article ne doit pas disparaître si son auteur
            // (compte admin) est supprimé plus tard.
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('body')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('seo_title')->nullable();
            $table->string('seo_description')->nullable();
            // NULL = brouillon ; date future = programmé ; date passée/présente = publié —
            // un seul champ suffit à dériver les 3 états, pas besoin d'enum status séparé.
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
