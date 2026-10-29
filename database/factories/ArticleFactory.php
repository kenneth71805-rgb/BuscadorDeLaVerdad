<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/** @extends Factory<Article> */
class ArticleFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->sentence(5);

        return [
            'category_id' => Category::factory(),
            'author_id' => User::factory()->head(),
            'slug' => str($title)->slug()->toString(),
            'title' => $title,
            'body' => fake()->paragraphs(3, true),
        ];
    }

    // status/published_at are guarded, so states set them explicitly.
    public function status(string $status, string|\DateTimeInterface|null $publishedAt = null): static
    {
        return $this->afterMaking(function ($article) use ($status, $publishedAt) {
            $article->status = $status;
            $article->published_at = is_string($publishedAt) ? Carbon::parse($publishedAt) : $publishedAt;
        });
    }
}
