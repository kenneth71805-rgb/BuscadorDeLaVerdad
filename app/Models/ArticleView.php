<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;

class ArticleView extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    // viewed_on is deliberately NOT cast: it must stay a plain 'Y-m-d' string so lookups match on every database.

    /** Counts a public page view. Staff and obvious crawlers are not counted; nothing about the visitor is stored. */
    public static function record(Article $article, Request $request): void
    {
        if ($request->user() || ! $request->isMethod('GET')
            || preg_match('/bot|crawl|spider|slurp|preview|facebookexternalhit|curl|wget/i', (string) $request->userAgent())) {
            return;
        }

        $day = now()->toDateString();
        $query = static::query()->where('article_id', $article->id)->where('viewed_on', $day);

        if ($query->increment('views') === 0) {
            try {
                static::create(['article_id' => $article->id, 'viewed_on' => $day, 'views' => 1]);
            } catch (UniqueConstraintViolationException) {
                $query->increment('views'); // another visitor created today's row a moment earlier
            }
        }
    }
}
