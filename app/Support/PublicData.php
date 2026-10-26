<?php

namespace App\Support;

use App\Models\Article;
use App\Models\Category;

/** Shapes data for public pages. Callers must pass already-public articles only. */
class PublicData
{
    /** @return list<array{name:string,slug:string}> */
    public static function nav(): array
    {
        return Category::query()->where('is_active', true)->where('show_in_nav', true)
            ->orderBy('sort_order')->get(['name', 'slug'])
            ->map(fn (Category $c) => ['name' => $c->name, 'slug' => $c->slug])->all();
    }

    public static function card(Article $a): array
    {
        return [
            'title' => $a->title,
            'subtitle' => $a->subtitle,
            'excerpt' => $a->excerpt(),
            'category' => ['name' => $a->category->name, 'slug' => $a->category->slug],
            'author' => $a->author->name,
            'published_at' => $a->published_at?->toIso8601String(),
            'minutes' => $a->readingMinutes(),
            'url' => route('site.article', [$a->category->slug, $a->slug], false),
        ];
    }
}
