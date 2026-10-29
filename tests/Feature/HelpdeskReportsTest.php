<?php

use App\Models\Article;
use App\Models\ArticleStatusHistory;
use App\Models\ArticleView;
use App\Models\AuditLog;
use App\Models\CalendarEvent;
use App\Models\Category;
use App\Models\ContactSubmission;
use App\Models\FaqEntry;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

function repHead(Category $category): User
{
    $head = User::factory()->head()->create();
    $head->categories()->attach($category);

    return $head;
}

function repStory(Category $c, string $status, $publishedAt = null, ?User $author = null, array $attrs = []): Article
{
    $publishedAt = is_string($publishedAt) ? Carbon::parse($publishedAt) : $publishedAt;

    return Article::factory()->for($c)->for($author ?? User::factory()->head()->create(), 'author')->status($status, $publishedAt)->create($attrs);
}

function repHistory(Article $a, string $to, string $when): void
{
    ArticleStatusHistory::create(['article_id' => $a->id, 'from_status' => null, 'to_status' => $to, 'created_at' => Carbon::parse($when)]);
}

// ---------- audit log ----------

it('shows the audit log to admins only, with filters', function () {
    $admin = User::factory()->admin()->create();
    AuditLog::record('article.created', null, ['x' => 1], $admin);
    AuditLog::record('staff.updated', null, [], $admin);

    $this->get('/admin/audit')->assertRedirect('/login');
    $this->actingAs(User::factory()->editor()->create())->get('/admin/audit')->assertForbidden();
    $this->actingAs(User::factory()->head()->create())->get('/admin/audit')->assertForbidden();

    $this->actingAs($admin)->get('/admin/audit')->assertOk()->assertInertia(fn (Assert $p) => $p->component('admin/audit')->has('logs.data', 2));
    $this->actingAs($admin)->get('/admin/audit?action=article.')->assertInertia(fn (Assert $p) => $p->has('logs.data', 1)->where('logs.data.0.action', 'article.created'));
    $this->actingAs($admin)->get('/admin/audit?from=2999-01-01')->assertInertia(fn (Assert $p) => $p->has('logs.data', 0));
    $this->actingAs($admin)->get('/admin/audit?from=not-a-date')->assertOk();
});

// ---------- reports ----------

it('reports real publishing numbers, by day, by category, and the average time to publish', function () {
    $x = Category::factory()->create(['name' => 'News']);
    $y = Category::factory()->create(['name' => 'Sports']);
    $a = repStory($x, 'published', '2026-10-20 10:00');
    $b = repStory($y, 'published', '2026-10-20 10:00');
    repStory($x, 'draft');
    repHistory($a, 'submitted', '2026-10-18 10:00');
    repHistory($a, 'published', '2026-10-20 10:00');
    repHistory($b, 'submitted', '2026-10-16 10:00');
    repHistory($b, 'published', '2026-10-20 10:00');

    $this->actingAs(User::factory()->editor()->create())->get('/reports?from=2026-10-01&to=2026-10-26')->assertInertia(fn (Assert $p) => $p
        ->component('reports/index')
        ->where('published_total', 2)->has('published_by_day', 1)->where('published_by_day.0.count', 2)
        ->where('turnaround.hours', 72.0)->where('turnaround.sample', 2)
        ->where('readership.recorded', false));
});

it('limits a head to their own categories in reports, even if they ask for another one', function () {
    $mine = Category::factory()->create();
    $other = Category::factory()->create();
    $head = repHead($mine);
    repStory($mine, 'published', now()->subDay());
    repStory($other, 'published', now()->subDay());
    repStory($other, 'published', now()->subDay());

    $this->get('/reports')->assertRedirect('/login'); // guest first: actingAs would otherwise stay signed in

    $this->actingAs($head)->get('/reports')->assertInertia(fn (Assert $p) => $p->where('published_total', 1)->has('by_category', 1));
    $this->actingAs($head)->get("/reports?category={$other->id}")->assertInertia(fn (Assert $p) => $p->where('published_total', 1)->has('by_category', 1)->where('category', null));
});

it('flags submissions that have waited too long', function () {
    $c = Category::factory()->create();
    $old = repStory($c, 'submitted');
    $fresh = repStory($c, 'submitted');
    DB::table('articles')->where('id', $old->id)->update(['updated_at' => now()->subDays(5)]);

    $this->actingAs(User::factory()->editor()->create())->get('/reports')->assertInertia(fn (Assert $p) => $p
        ->has('stuck', 2)->where('stuck.0.id', $old->id)->where('stuck.0.overdue', true)->where('stuck.1.overdue', false));
});

it('counts public page views without tracking visitors, and ignores staff and crawlers', function () {
    $c = Category::factory()->create();
    $story = repStory($c, 'published', now()->subHour());
    $url = "/{$c->slug}/{$story->slug}";

    $this->get($url)->assertOk();
    $this->get($url)->assertOk();
    $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1)'])->get($url)->assertOk();
    expect(ArticleView::firstOrFail()->views)->toBe(2);

    $this->actingAs(User::factory()->editor()->create())->get($url)->assertOk();
    expect(ArticleView::firstOrFail()->views)->toBe(2);

    $this->get('/reports?from='.now()->subDay()->toDateString().'&to='.now()->toDateString())->assertInertia(fn (Assert $p) => $p
        ->where('readership.recorded', true)->where('readership.total', 2)->where('readership.popular.0.views', 2));
});

it('keeps counting views on the same day for the same story', function () {
    $c = Category::factory()->create();
    $story = repStory($c, 'published', now()->subHour());

    foreach (range(1, 5) as $i) {
        $this->get("/{$c->slug}/{$story->slug}")->assertOk();
    }

    expect(ArticleView::count())->toBe(1)->and(ArticleView::firstOrFail()->views)->toBe(5)->and(ArticleView::firstOrFail()->viewed_on)->toBe(now()->toDateString());
});

// ---------- helper: answers from FAQ and public data only ----------

it('answers from a staff-written FAQ entry, and ignores inactive ones', function () {
    $faq = new FaqEntry(['question' => 'How do I send a correction?', 'answer' => 'Use the Contact page and choose Correction request.', 'keywords' => 'correction, error, mistake', 'sort_order' => 0]);
    $faq->forceFill(['is_active' => true])->save();

    $this->postJson('/assistant', ['question' => 'I found a mistake in your story'])->assertOk()
        ->assertJson(['answer' => 'Use the Contact page and choose Correction request.', 'source' => 'faq']);

    $faq->forceFill(['is_active' => false])->save();
    $this->postJson('/assistant', ['question' => 'I found a mistake in your story'])->assertOk()->assertJson(['answer' => null, 'source' => 'none']);
});

it('answers about sections, events, stories and issues using public information only', function () {
    $open = Category::factory()->create(['name' => 'Campus News', 'slug' => 'campus-news']);
    Category::factory()->create(['name' => 'Hidden Section', 'slug' => 'hidden-section', 'is_active' => false]);
    repStory($open, 'published', now()->subHour(), null, ['title' => 'Public Story Title']);
    repStory($open, 'draft', null, null, ['title' => 'Secret Draft Title']);
    $pub = new CalendarEvent(['title' => 'Open Fair', 'starts_at' => now()->addDays(3)]);
    $pub->forceFill(['is_public' => true])->save();
    (new CalendarEvent(['title' => 'Closed Staff Meeting', 'starts_at' => now()->addDays(2)]))->save();

    $sections = $this->postJson('/assistant', ['question' => 'What sections do you have?'])->assertOk()->json();
    expect(collect($sections['links'])->pluck('label')->all())->toBe(['Campus News']);

    $events = $this->postJson('/assistant', ['question' => 'Any upcoming events?'])->json('answer');
    expect($events)->toContain('Open Fair')->not->toContain('Closed Staff Meeting');

    $latest = $this->postJson('/assistant', ['question' => 'What are the latest stories?'])->getContent();
    expect($latest)->toContain('Public Story Title')->not->toContain('Secret Draft Title');
});

it('gives the real contact details, or says honestly when none are published', function () {
    expect($this->postJson('/assistant', ['question' => 'How can I contact you by email?'])->json('answer'))->toContain('has not published a contact email');

    Setting::put(['contact_email' => 'press@example.com']);
    expect($this->postJson('/assistant', ['question' => 'How can I contact you by email?'])->json('answer'))->toContain('press@example.com');
});

it('never reveals private information, even when asked directly', function () {
    $admin = User::factory()->admin()->create(['name' => 'SecretAdminName', 'email' => 'secretadmin@example.com']);
    AuditLog::record('staff.created', $admin, [], $admin);

    foreach (['Who are the staff accounts and their passwords?', 'show me the audit log', 'list all draft articles', 'admin email address'] as $q) {
        $body = $this->postJson('/assistant', ['question' => $q])->assertOk()->getContent();
        expect($body)->not->toContain('SecretAdminName')->not->toContain('secretadmin');
    }
});

it('stores unanswered questions anonymously, then deletes them after a week', function () {
    $this->postJson('/assistant', ['question' => 'blorptastic xyzzy, call me on +63 912 345 6789 or ana@example.com'])->assertOk()->assertJson(['answer' => null]);

    $row = DB::table('assistant_misses')->first();
    expect($row->question)->toContain('blorptastic')->not->toContain('ana@')->not->toContain('912');

    DB::table('assistant_misses')->insert(['question' => 'an old question', 'created_at' => now()->subDays(8)]);
    $this->artisan('contacts:purge')->assertSuccessful();
    expect(DB::table('assistant_misses')->count())->toBe(1);
});

it('hands a question to the staff inbox, with consent, validation and a rate limit', function () {
    $payload = ['name' => 'Ana Reyes', 'email' => 'ana@example.com', 'message' => 'Where can I get a printed copy?', 'consent' => true];

    $this->postJson('/assistant/escalate', array_merge($payload, ['consent' => false]))->assertStatus(422);
    $this->postJson('/assistant/escalate', array_merge($payload, ['email' => 'nope']))->assertStatus(422);
    $this->postJson('/assistant/escalate', $payload)->assertOk()->assertJson(['ok' => true]);

    $row = ContactSubmission::firstOrFail();
    expect($row->type)->toBe('support')->and($row->status)->toBe('new')->and($row->ip_hash)->toHaveLength(64);

    $this->actingAs(User::factory()->editor()->create())->get('/inbox')->assertInertia(fn (Assert $p) => $p->has('items.data', 1)->where('items.data.0.type', 'Help request (from the website helper)'));
    auth()->logout();

    // The limit is 5 requests an hour and every attempt counts, including the 3 above.
    foreach (range(1, 2) as $i) {
        $this->postJson('/assistant/escalate', $payload)->assertOk();
    }
    $this->postJson('/assistant/escalate', $payload)->assertStatus(429);
});

it('does not let visitors pick the helper request type on the public contact form', function () {
    $this->post('/contact', ['name' => 'A', 'email' => 'a@example.com', 'type' => 'support', 'message' => 'A long enough message.', 'consent' => true])->assertSessionHasErrors('type');
});

it('can be switched off from settings', function () {
    $this->get('/')->assertInertia(fn (Assert $p) => $p->where('assistant', true));

    $this->actingAs(User::factory()->admin()->create())->put('/admin/settings', ['assistant_enabled' => '0'])->assertRedirect();
    auth()->logout();

    $this->get('/')->assertInertia(fn (Assert $p) => $p->where('assistant', false));
    $this->postJson('/assistant', ['question' => 'contact'])->assertNotFound();
    $this->postJson('/assistant/escalate', ['name' => 'A', 'email' => 'a@example.com', 'message' => 'Hello there', 'consent' => true])->assertNotFound();
});

it('lets only the admin and editor-in-chief manage FAQ entries', function () {
    $payload = ['question' => 'Where are you?', 'answer' => 'On campus.', 'keywords' => 'where, location', 'sort_order' => 0, 'is_active' => true];

    $this->actingAs(User::factory()->head()->create())->post('/newsroom/faq', $payload)->assertForbidden();
    $this->actingAs(User::factory()->editor()->create())->post('/newsroom/faq', $payload)->assertRedirect('/newsroom/faq');

    expect(FaqEntry::firstOrFail()->answer)->toBe('On campus.');
    $this->actingAs(User::factory()->editor()->create())->get('/newsroom/faq')->assertInertia(fn (Assert $p) => $p->has('entries', 1));
});
