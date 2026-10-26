<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use App\Support\PublicData;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function __invoke(string $categorySlug)
    {
        $category = Category::where('slug', $categorySlug)->where('is_active', true)->firstOrFail();

        $articles = Article::publiclyVisible()->with(['category:id,name,slug', 'author:id,name'])
            ->where('category_id', $category->id)->latest('published_at')
            ->paginate(12)->withQueryString()
            ->through(fn (Article $a) => PublicData::card($a));

        return Inertia::render('site/category', [
            'nav' => PublicData::nav(),
            'category' => ['name' => $category->name, 'slug' => $category->slug, 'description' => $category->description],
            'articles' => $articles,
        ]);
    }
}
