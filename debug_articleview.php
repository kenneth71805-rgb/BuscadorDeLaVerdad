<?php

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use App\Models\Article;
use App\Models\ArticleView;
use App\Models\Category;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;

ArticleView::query()->delete();
Article::query()->delete();
Category::query()->delete();

$c = Category::factory()->create();
$story = Article::factory()->for($c)->for(User::factory()->head()->create(), 'author')->status('published', now()->subHour())->create();

foreach (['Mozilla/5.0', 'Mozilla/5.0', 'Mozilla/5.0 (compatible; Googlebot/2.1)'] as $ua) {
    $request = Request::create('/'.$c->slug.'/'.$story->slug, 'GET');
    $request->server->set('HTTP_USER_AGENT', $ua);

    $query = ArticleView::query()->where('article_id', $story->id)->where('viewed_on', now()->toDateString());
    $before = $query->first()?->views ?? 0;
    $inc = $query->increment('views');
    echo "BEFORE={$before} INC={$inc} UA={$ua}\n";
    dump(ArticleView::query()->get()->toArray());

    if ($inc === 0) {
        ArticleView::create(['article_id' => $story->id, 'viewed_on' => now()->toDateString(), 'views' => 1]);
    }
}

echo 'FINAL='.(string) ArticleView::firstOrFail()->views."\n";
