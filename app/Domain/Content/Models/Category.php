<?php

namespace App\Domain\Content\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug'])]
class Category extends Model
{
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return CategoryFactory::new();
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }
}
