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
            'image' => self::image($a, 'medium'),
        ];
    }

    /** Only photos an editor approved for public release are ever exposed. */
    public static function image(Article $a, string $variant): ?array
    {
        $m = $a->leadMedia;

        return $m && $m->is_public
            ? ['url' => $m->url($variant), 'alt' => $m->alt, 'caption' => $m->caption, 'credit' => $m->credit, 'width' => $m->width, 'height' => $m->height]
            : null;
    }
}
