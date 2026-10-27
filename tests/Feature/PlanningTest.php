<?php

use App\Models\Article;
use App\Models\Assignment;
use App\Models\CalendarEvent;
use App\Models\Category;
use App\Models\User;
use App\Services\Notifier;
use Inertia\Testing\AssertableInertia as Assert;

function planHead(Category $category, bool $review = true): User
{
    $head = User::factory()->head()->create();
    $head->categories()->attach($category, ['can_review' => $review]);

    return $head;
}

function planAssignment(Category $c, User $assignee, User $creator, $due, string $status = 'open', string $title = 'Cover the fair'): Assignment
{
    $a = new Assignment(['category_id' => $c->id, 'assigned_to' => $assignee->id, 'title' => $title, 'due_at' => $due]);
    $a->forceFill(['created_by' => $creator->id, 'status' => $status])->save();

    return $a;
}

function planPayload(Category $c, User $assignee, array $o = []): array
{
    return array_merge([
        'title' => 'Cover the science fair', 'description' => 'Two photos and 400 words.',
        'category_id' => $c->id, 'assigned_to' => $assignee->id, 'due_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
    ], $o);
}

// ---------- assignments ----------

it('lets a head create an assignment in their own category and notifies the assignee only', function () {
    $cat = Category::factory()->create();
    $head = planHead($cat);
    $editor = User::factory()->editor()->create();

    $this->actingAs($head)->post('/assignments', planPayload($cat, $editor))->assertRedirect('/assignments');

    $a = Assignment::firstOrFail();
    expect($a->created_by)->toBe($head->id)->and($a->status)->toBe('open');
    expect($editor->notifications()->count())->toBe(1)->and($editor->notifications()->first()->data['message'])->toContain('Cover the science fair');
    expect($head->notifications()->count())->toBe(0);
});

it('stops a head creating assignments in other categories, for deadlines in the past, or for inactive staff', function () {
    $mine = Category::factory()->create();
    $other = Category::factory()->create();
    $head = planHead($mine);
    $editor = User::factory()->editor()->create();
    $gone = User::factory()->editor()->create();
    $gone->forceFill(['is_active' => false])->save();

    $this->actingAs($head)->post('/assignments', planPayload($other, $editor))->assertSessionHasErrors('category_id');
    $this->actingAs($head)->post('/assignments', planPayload($mine, $editor, ['due_at' => now()->subDay()->format('Y-m-d\TH:i')]))->assertSessionHasErrors('due_at');
    $this->actingAs($head)->post('/assignments', planPayload($mine, $gone))->assertSessionHasErrors('assigned_to');
    expect(Assignment::count())->toBe(0);
});

it('hides other categories assignments from a head, in the list and by address', function () {
    $mine = Category::factory()->create();
    $other = Category::factory()->create();
    $head = planHead($mine);
    $foreign = planAssignment($other, User::factory()->editor()->create(), User::factory()->editor()->create(), now()->addDay());
    planAssignment($mine, $head, $head, now()->addDay());

    $this->actingAs($head)->get('/assignments')->assertInertia(fn (Assert $p) => $p->has('items.data', 1));
    $this->actingAs($head)->get("/assignments/{$foreign->id}/edit")->assertForbidden();
    $this->actingAs($head)->put("/assignments/{$foreign->id}/status", ['status' => 'done'])->assertForbidden();
});

it('lets the assignee update status but not details, and tells the creator when it is done', function () {
    $cat = Category::factory()->create();
    $creator = planHead($cat);
    $assignee = planHead(Category::factory()->create()); // different category: can act only because it is assigned to them
    $a = planAssignment($cat, $assignee, $creator, now()->addDay());

    $this->actingAs($assignee)->get("/assignments/{$a->id}/edit")->assertOk();
    $this->actingAs($assignee)->put("/assignments/{$a->id}", planPayload($cat, $assignee, ['title' => 'Changed']))->assertForbidden();
    $this->actingAs($assignee)->put("/assignments/{$a->id}/status", ['status' => 'done'])->assertRedirect();

    expect($a->fresh()->status)->toBe('done')->and($creator->notifications()->count())->toBe(1);
    $this->actingAs($assignee)->put("/assignments/{$a->id}/status", ['status' => 'nonsense'])->assertSessionHasErrors('status');
});

it('lets the editor-in-chief reassign any assignment and notifies the new assignee', function () {
    $cat = Category::factory()->create();
    $head = planHead($cat);
    $a = planAssignment($cat, $head, $head, now()->addDay());
    $new = User::factory()->editor()->create();

    $this->actingAs(User::factory()->editor()->create())->put("/assignments/{$a->id}", planPayload($cat, $new, ['title' => $a->title]))->assertRedirect('/assignments');

    expect($a->fresh()->assigned_to)->toBe($new->id)->and($new->notifications()->count())->toBe(1);
});

// ---------- calendar ----------

it('builds the calendar from real records, scoped to what each person may see', function () {
    $a = Category::factory()->create();
    $b = Category::factory()->create();
    $head = planHead($a);
    $editor = User::factory()->editor()->create();

    $event = new CalendarEvent(['title' => 'Foundation Day', 'starts_at' => '2026-03-12 10:00']);
    $event->save();
    planAssignment($a, $head, $editor, '2026-03-15 17:00', 'open', 'A deadline');
    planAssignment($b, $editor, $editor, '2026-03-16 17:00', 'open', 'B deadline');
    $article = Article::factory()->for($b)->for($editor, 'author')->status('scheduled')->create();
    $article->forceFill(['scheduled_for' => '2026-03-17 08:00'])->save();

    $this->get('/calendar')->assertRedirect('/login'); // guest first: actingAs would otherwise stay signed in

    $this->actingAs($editor)->get('/calendar?month=2026-03')->assertInertia(fn (Assert $p) => $p
        ->component('calendar/index')->where('label', 'March 2026')->where('prev', '2026-02')->where('next', '2026-04')->has('days', 4));
    $this->actingAs($head)->get('/calendar?month=2026-03')->assertInertia(fn (Assert $p) => $p->has('days', 2));
    $this->actingAs($head)->get('/calendar?month=garbage')->assertOk();
});

// ---------- events ----------

it('lets only the admin and editor-in-chief manage events, and shows only public upcoming ones on the website', function () {
    $editor = User::factory()->editor()->create();
    $base = ['description' => null, 'ends_at' => null, 'location' => 'Main gym', 'category_id' => null];

    $this->actingAs(User::factory()->head()->create())->post('/newsroom/events', $base + ['title' => 'X', 'starts_at' => now()->addDay()->format('Y-m-d H:i'), 'is_public' => true])->assertForbidden();

    $this->actingAs($editor)->post('/newsroom/events', $base + ['title' => 'Public fair', 'starts_at' => now()->addDays(5)->format('Y-m-d H:i'), 'is_public' => true])->assertRedirect('/newsroom/events');
    $this->actingAs($editor)->post('/newsroom/events', $base + ['title' => 'Staff meeting', 'starts_at' => now()->addDays(3)->format('Y-m-d H:i'), 'is_public' => false]);
    $past = new CalendarEvent(['title' => 'Old fair', 'starts_at' => now()->subDays(3)]);
    $past->forceFill(['is_public' => true])->save();
    auth()->logout();

    $this->get('/events')->assertInertia(fn (Assert $p) => $p->has('events.data', 1)->where('events.data.0.title', 'Public fair'));
    $this->get('/')->assertInertia(fn (Assert $p) => $p->has('events', 1));
});

it('rejects an event that ends before it starts', function () {
    $this->actingAs(User::factory()->editor()->create())->post('/newsroom/events', [
        'title' => 'Bad', 'starts_at' => '2026-12-02 10:00', 'ends_at' => '2026-12-01 10:00', 'is_public' => false,
    ])->assertSessionHasErrors('ends_at');
});

// ---------- notifications from editorial decisions ----------

it('notifies reviewers on submission, the editor on category approval, and the author on a returned article', function () {
    $cat = Category::factory()->create();
    $author = planHead($cat, false);
    $reviewer = planHead($cat);
    $editor = User::factory()->editor()->create();
    $article = Article::factory()->for($cat)->for($author, 'author')->status('draft')->create(['title' => 'Fair opens']);

    $move = fn (User $u, string $to, array $x = []) => $this->actingAs($u)->post("/articles/{$article->id}/transition", ['to' => $to] + $x)->assertRedirect();

    $move($author, 'submitted');
    expect($reviewer->notifications()->count())->toBe(1)->and($editor->notifications()->count())->toBe(0)->and($author->notifications()->count())->toBe(0);

    $move($reviewer, 'category_approved');
    expect($editor->notifications()->count())->toBe(1);

    $move($editor, 'revision_requested', ['reason' => 'Add a second source.']);
    expect($author->notifications()->count())->toBe(1)->and($author->notifications()->first()->data['message'])->toContain('Add a second source.');
});

it('tells the editor-in-chief about a submission when the category has no reviewer', function () {
    $cat = Category::factory()->create();
    $author = planHead($cat, false);
    $editor = User::factory()->editor()->create();
    $article = Article::factory()->for($cat)->for($author, 'author')->status('draft')->create();

    $this->actingAs($author)->post("/articles/{$article->id}/transition", ['to' => 'submitted'])->assertRedirect();

    expect($editor->notifications()->count())->toBe(1);
});

// ---------- notification inbox ----------

it('shows each person only their own notifications and lets them mark them read', function () {
    $me = User::factory()->editor()->create();
    $you = User::factory()->editor()->create();
    Notifier::send([$me], 'For me', '/articles', 'test');
    Notifier::send([$you], 'For you', '/articles', 'test');
    $mine = $me->notifications()->first();
    $yours = $you->notifications()->first();

    $this->actingAs($me)->get('/notifications')->assertInertia(fn (Assert $p) => $p->has('items.data', 1)->where('items.data.0.message', 'For me')->where('unread', 1));

    $this->actingAs($me)->put("/notifications/{$yours->id}/read")->assertRedirect();
    expect($yours->fresh()->read_at)->toBeNull(); // cannot touch someone else's

    $this->actingAs($me)->put("/notifications/{$mine->id}/read")->assertRedirect();
    expect($mine->fresh()->read_at)->not->toBeNull();

    Notifier::send([$me, $you], 'Another', '/articles', 'test');
    $this->actingAs($me)->put('/notifications/read-all')->assertRedirect();
    expect($me->unreadNotifications()->count())->toBe(0)->and($you->unreadNotifications()->count())->toBe(2);
});

it('does not notify the person who caused the event, or inactive staff', function () {
    $actor = User::factory()->editor()->create();
    $gone = User::factory()->editor()->create();
    $gone->forceFill(['is_active' => false])->save();

    expect(Notifier::send([$actor, $gone], 'Hello', '/x', 'test', $actor))->toBe(0);
});

// ---------- reminders ----------

it('sends each deadline reminder and overdue notice only once, and ignores finished work', function () {
    $cat = Category::factory()->create();
    $creator = planHead($cat);
    $assignee = User::factory()->editor()->create();
    planAssignment($cat, $assignee, $creator, now()->addHours(5), 'open', 'Soon');
    planAssignment($cat, $assignee, $creator, now()->addHours(5), 'done', 'Finished');
    planAssignment($cat, $assignee, $creator, now()->addDays(5), 'open', 'Far away');
    planAssignment($cat, $assignee, $creator, now()->subHours(2), 'in_progress', 'Late');

    $this->artisan('assignments:remind')->assertSuccessful();
    $messages = $assignee->notifications->map(fn ($n) => $n->data['message'])->all();
    expect($assignee->notifications()->count())->toBe(2)->and(implode(' ', $messages))->toContain('Due soon')->toContain('Overdue');
    expect($creator->notifications()->count())->toBe(1); // overdue notice only

    $this->artisan('assignments:remind')->assertSuccessful();
    expect($assignee->notifications()->count())->toBe(2)->and($creator->notifications()->count())->toBe(1);
});

// ---------- dashboards ----------

it('shows upcoming deadlines on the dashboards, limited to what the person may see', function () {
    $mine = Category::factory()->create();
    $other = Category::factory()->create();
    $head = planHead($mine);
    $editor = User::factory()->editor()->create();
    planAssignment($mine, $head, $editor, now()->addDay());
    planAssignment($other, $editor, $editor, now()->addDay());

    $this->actingAs($head)->get('/category')->assertInertia(fn (Assert $p) => $p->has('deadlines', 1));
    $this->actingAs($editor)->get('/editor')->assertInertia(fn (Assert $p) => $p->has('deadlines', 2));
    $this->actingAs(User::factory()->admin()->create())->get('/admin')->assertInertia(fn (Assert $p) => $p->has('deadlines', 2));
});
