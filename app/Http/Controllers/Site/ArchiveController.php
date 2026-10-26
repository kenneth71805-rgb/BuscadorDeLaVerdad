<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use App\Support\PublicData;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class ArchiveController extends Controller
{
    public function index()
    {
        $first = Article::publiclyVisible()->min('published_at');
        $last = Article::publiclyVisible()->max('published_at');
        $years = [];

        if ($first && $last) {
            for ($y = Carbon::parse($last)->year; $y >= Carbon::parse($first)->year; $y--) {
                $count = $this->inYear($y)->count();
                if ($count > 0) {
                    $years[] = ['year' => $y, 'count' => $count];
                }
            }
        }

        return Inertia::render('site/archives', ['nav' => PublicData::nav(), 'years' => $years]);
    }

    public function year(Request $request, int $year)
    {
        $category = Category::where('is_active', true)->where('slug', (string) $request->query('category'))->first();

        $query = $this->inYear($year);
        abort_unless($query->exists(), 404);

        $articles = $this->inYear($year)->with(['category:id,name,slug', 'author:id,name', 'leadMedia'])
            ->when($category, fn ($q) => $q->where('category_id', $category->id))
            ->latest('published_at')->paginate(20)->withQueryString()
            ->through(fn (Article $a) => PublicData::card($a));

        return Inertia::render('site/archive-year', [
            'nav' => PublicData::nav(),
            'year' => $year,
            'category' => $category?->slug,
            'articles' => $articles,
        ]);
    }

    private function inYear(int $year)
    {
        $start = Carbon::create($year, 1, 1)->startOfYear();

        return Article::publiclyVisible()->whereBetween('published_at', [$start, $start->copy()->endOfYear()]);
    }
}
