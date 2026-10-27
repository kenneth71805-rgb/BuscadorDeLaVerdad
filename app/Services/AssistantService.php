<?php

namespace App\Services;

use App\Models\Article;
use App\Models\CalendarEvent;
use App\Models\Category;
use App\Models\FaqEntry;
use App\Models\Issue;
use App\Models\Setting;

/**
 * The website helper. It is a plain rule-based FAQ matcher, NOT an AI model, and it says so.
 * It answers only from (1) FAQ entries that staff wrote and (2) information already public on the site.
 * It never reads drafts, staff accounts, assignments, audit logs or settings other than public contact details.
 */
class AssistantService
{
    private const STOP = ['a', 'an', 'the', 'is', 'are', 'was', 'be', 'do', 'does', 'did', 'i', 'me', 'my', 'we', 'you', 'your', 'to', 'of', 'in', 'on', 'for', 'and', 'or', 'it', 'this', 'that', 'can', 'how', 'what', 'where', 'when', 'who', 'why', 'there', 'any', 'about', 'with', 'at', 'have', 'has', 'please', 'want', 'like', 'get', 'send'];

    /** Built-in topics answered from public data. keyword => topic */
    private const TOPICS = [
        'sections' => ['section', 'category', 'topic', 'sport', 'feature', 'opinion', 'editorial'],
        'contact' => ['contact', 'email', 'address', 'reach', 'phone', 'office', 'facebook', 'instagram', 'social'],
        'events' => ['event', 'happening', 'schedule', 'calendar'],
        'latest' => ['latest', 'newest', 'recent', 'headline'],
        'issues' => ['issue', 'pdf', 'download', 'edition', 'magazine', 'print'],
        'search' => ['search', 'find', 'archive', 'old'],
        'about' => ['mission', 'vision', 'history', 'who'],
    ];

    /** @return list<string> */
    public static function tokens(string $text): array
    {
        $words = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $out = [];
        foreach ($words as $w) {
            if (in_array($w, self::STOP, true) || mb_strlen($w) < 2) {
                continue;
            }
            $out[] = (mb_strlen($w) > 3 && str_ends_with($w, 's')) ? mb_substr($w, 0, -1) : $w; // crude plural stripping
        }

        return array_values(array_unique($out));
    }

    /** @return array{answer:string,links:list<array{label:string,url:string}>,source:string}|null */
    public function answer(string $question): ?array
    {
        $tokens = self::tokens(mb_substr($question, 0, 200));
        if ($tokens === []) {
            return null;
        }

        return $this->fromFaq($tokens) ?? $this->fromSite($tokens);
    }

    private function fromFaq(array $tokens): ?array
    {
        $best = null;
        $bestScore = 0;

        foreach (FaqEntry::where('is_active', true)->orderBy('sort_order')->get() as $entry) {
            $score = 0;
            foreach (self::tokens((string) $entry->keywords) as $k) {
                $score += in_array($k, $tokens, true) ? 2 : 0; // keywords chosen by editors count double
            }
            foreach (self::tokens($entry->question) as $q) {
                $score += in_array($q, $tokens, true) ? 1 : 0;
            }
            if ($score > $bestScore) {
                [$best, $bestScore] = [$entry, $score];
            }
        }

        return $best && $bestScore >= 2 ? ['answer' => $best->answer, 'links' => [], 'source' => 'faq'] : null;
    }

    private function fromSite(array $tokens): ?array
    {
        $topic = null;
        $top = 0;
        foreach (self::TOPICS as $name => $words) {
            $hits = count(array_intersect($tokens, array_map(fn ($w) => self::tokens($w)[0] ?? $w, $words)));
            if ($hits > $top) {
                [$topic, $top] = [$name, $hits];
            }
        }

        return $topic ? $this->{'topic'.ucfirst($topic)}() : null;
    }

    private function reply(string $answer, array $links = []): array
    {
        return ['answer' => $answer, 'links' => $links, 'source' => 'site'];
    }

    private function topicSections(): array
    {
        $cats = Category::where('is_active', true)->where('show_in_nav', true)->orderBy('sort_order')->get();

        return $cats->isEmpty()
            ? $this->reply('No sections are published yet.')
            : $this->reply('The publication has these sections:', $cats->map(fn ($c) => ['label' => $c->name, 'url' => '/'.$c->slug])->all());
    }

    private function topicContact(): array
    {
        $s = Setting::allValues();
        $lines = array_filter([
            $s['contact_email'] ? "Email: {$s['contact_email']}" : null,
            $s['contact_address'] ? "Office: {$s['contact_address']}" : null,
        ]);
        $links = array_values(array_filter([
            ['label' => 'Contact form', 'url' => '/contact'],
            $s['facebook_url'] ? ['label' => 'Facebook', 'url' => $s['facebook_url']] : null,
            $s['instagram_url'] ? ['label' => 'Instagram', 'url' => $s['instagram_url']] : null,
        ]));

        return $this->reply($lines ? implode("\n", $lines) : 'The newsroom has not published a contact email yet. Please use the contact form.', $links);
    }

    private function topicEvents(): array
    {
        $events = CalendarEvent::where('is_public', true)->where('starts_at', '>=', now()->startOfDay())->orderBy('starts_at')->limit(3)->get();

        return $events->isEmpty()
            ? $this->reply('No public events are scheduled right now.', [['label' => 'Events page', 'url' => '/events']])
            : $this->reply("Upcoming events:\n".$events->map(fn ($e) => '• '.$e->title.' — '.$e->starts_at->format('M j, Y g:i A'))->implode("\n"), [['label' => 'All events', 'url' => '/events']]);
    }

    private function topicLatest(): array
    {
        $articles = Article::publiclyVisible()->with('category:id,slug')->latest('published_at')->limit(3)->get();

        return $articles->isEmpty()
            ? $this->reply('No stories have been published yet.')
            : $this->reply('The latest stories:', $articles->map(fn ($a) => ['label' => $a->title, 'url' => route('site.article', [$a->category->slug, $a->slug], false)])->all());
    }

    private function topicIssues(): array
    {
        $issues = Issue::publicOnly()->latest('issue_date')->limit(3)->get();

        return $issues->isEmpty()
            ? $this->reply('No issues have been published yet.')
            : $this->reply('Recent issues (you can download the PDF on each page):', $issues->map(fn ($i) => ['label' => $i->title, 'url' => route('site.issue', $i->slug, false)])->all());
    }

    private function topicSearch(): array
    {
        return $this->reply('Use the search box at the top of any page, or browse older stories by year in the Archives.', [['label' => 'Archives', 'url' => '/archives']]);
    }

    private function topicAbout(): array
    {
        $s = Setting::allValues();
        $text = collect([$s['about_mission'] ? "Mission: {$s['about_mission']}" : null, $s['about_vision'] ? "Vision: {$s['about_vision']}" : null])->filter()->implode("\n");

        return $this->reply($text ?: 'You can read about the publication and meet its editors on the About page.', [['label' => 'About Los Sueños', 'url' => '/about']]);
    }

    /** Remove things that look like contact details so unanswered questions are stored anonymously. */
    public static function scrub(string $question): string
    {
        $q = preg_replace('/[\w.+-]+@[\w-]+\.[\w.-]+/u', '[email]', $question);
        $q = preg_replace('/\d[\d\s().+-]{5,}\d/u', '[number]', (string) $q);

        return mb_substr(trim((string) $q), 0, 200);
    }
}
