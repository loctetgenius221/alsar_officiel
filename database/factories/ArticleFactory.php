<?php

namespace Database\Factories;

use App\Domain\Content\Models\Article;
use App\Domain\Content\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        $title = rtrim(fake()->sentence(6), '.');

        return [
            'category_id' => Category::factory(),
            'author_id' => null,
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1000, 9999),
            'excerpt' => fake()->sentence(20),
            'body' => '<p>'.implode('</p><p>', fake()->paragraphs(3)).'</p>',
            'cover_image' => null,
            'seo_title' => null,
            'seo_description' => null,
            'published_at' => now(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['published_at' => null]);
    }

    public function scheduled(): static
    {
        return $this->state(fn () => ['published_at' => now()->addWeek()]);
    }
}
