<?php

namespace App\Http\Controllers\Site;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Issue;
use App\Support\PublicData;
use App\Support\Seo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class IssueController extends Controller
{
    public function index()
    {
        return Inertia::render('site/issues', [
            'meta' => Seo::page('Issues', 'Download the printed editions of Los Sueños as PDF.', Seo::paged('/issues')),
            'nav' => PublicData::nav(),
            'issues' => Issue::publicOnly()->with('cover')->latest('issue_date')->paginate(12)->through(fn (Issue $i) => self::card($i)),
        ]);
    }

    public function show(string $slug)
    {
        $issue = Issue::publicOnly()->with('cover')->where('slug', $slug)->firstOrFail();

        return Inertia::render('site/issue', [
            'meta' => Seo::page($issue->title, $issue->description ?: "Issue: {$issue->title}", route('site.issue', $issue->slug, false), ['image' => $issue->cover && $issue->cover->is_public ? url($issue->cover->url('large')) : null]),
            'nav' => PublicData::nav(),
            'issue' => self::card($issue) + ['description' => $issue->description, 'download_url' => route('site.issue.download', $issue->slug, false),
                'size_mb' => round(($issue->pdf_bytes ?? 0) / 1048576, 1)],
        ]);
    }

    public function download(Request $request, string $slug)
    {
        $issue = Issue::whereNotNull('pdf_path')->where('slug', $slug)->firstOrFail();

        if (! $issue->is_public) {
            $user = $request->user();
            abort_unless($user && $user->hasRole(Role::Admin, Role::EditorInChief), 404); // staff may preview unpublished issues
        }

        $disk = Storage::disk('local');
        abort_unless($disk->exists($issue->pdf_path), 404);

        // Always a download, never rendered inline by the site.
        return response()->download($disk->path($issue->pdf_path), "{$issue->slug}.pdf", [
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public static function card(Issue $i): array
    {
        return [
            'title' => $i->title, 'slug' => $i->slug, 'issue_date' => $i->issue_date->toDateString(),
            'url' => route('site.issue', $i->slug, false),
            'cover' => $i->cover && $i->cover->is_public ? ['url' => $i->cover->url('medium'), 'alt' => $i->cover->alt] : null,
        ];
    }
}
