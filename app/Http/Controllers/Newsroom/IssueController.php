<?php

namespace App\Http\Controllers\Newsroom;

use App\Exceptions\InvalidPdf;
use App\Http\Controllers\Controller;
use App\Http\Requests\IssueRequest;
use App\Models\AuditLog;
use App\Models\Issue;
use App\Models\Media;
use App\Support\PdfValidator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class IssueController extends Controller
{
    public function index()
    {
        return Inertia::render('newsroom/issues/index', [
            'issues' => Issue::latest('issue_date')->get()->map(fn (Issue $i) => [
                'id' => $i->id, 'title' => $i->title, 'issue_date' => $i->issue_date->toDateString(),
                'is_public' => $i->is_public, 'has_pdf' => $i->pdf_path !== null,
            ]),
        ]);
    }

    public function create()
    {
        return Inertia::render('newsroom/issues/create', ['media' => $this->library()]);
    }

    public function store(IssueRequest $request)
    {
        $data = $request->validated();
        $pdf = $this->checkedPdf($request->file('pdf'));

        $issue = new Issue(['title' => $data['title'], 'description' => $data['description'] ?? null,
            'issue_date' => $data['issue_date'], 'cover_media_id' => $data['cover_media_id'] ?? null]);
        $issue->slug = $this->uniqueSlug($data['title']);
        $issue->is_public = (bool) $data['is_public'];
        $issue->created_by = $request->user()->id;
        $this->attachPdf($issue, $pdf);
        $issue->save();
        AuditLog::record('issue.created', $issue, ['public' => $issue->is_public], $request->user());

        return redirect()->route('newsroom.issues.index')->with('success', 'Issue saved.');
    }

    public function edit(Issue $issue)
    {
        return Inertia::render('newsroom/issues/edit', [
            'issue' => $issue->only(['id', 'title', 'description', 'cover_media_id', 'is_public']) + ['issue_date' => $issue->issue_date->toDateString(), 'has_pdf' => $issue->pdf_path !== null],
            'media' => $this->library(),
        ]);
    }

    public function update(IssueRequest $request, Issue $issue)
    {
        $data = $request->validated();
        $pdf = $request->hasFile('pdf') ? $this->checkedPdf($request->file('pdf')) : null;

        $issue->fill(['title' => $data['title'], 'description' => $data['description'] ?? null,
            'issue_date' => $data['issue_date'], 'cover_media_id' => $data['cover_media_id'] ?? null]); // slug stays fixed
        $issue->is_public = (bool) $data['is_public'];

        $old = $issue->pdf_path;
        if ($pdf) {
            $this->attachPdf($issue, $pdf);
        }
        $issue->save();
        if ($pdf && $old) {
            Storage::disk('local')->delete($old); // replaced files are removed
        }
        AuditLog::record('issue.updated', $issue, ['public' => $issue->is_public, 'pdf_replaced' => (bool) $pdf], $request->user());

        return redirect()->route('newsroom.issues.index')->with('success', 'Issue saved.');
    }

    private function checkedPdf(UploadedFile $file): UploadedFile
    {
        try {
            PdfValidator::assertSafe($file->getRealPath());
        } catch (InvalidPdf $e) {
            throw ValidationException::withMessages(['pdf' => $e->getMessage()]);
        }

        return $file;
    }

    private function attachPdf(Issue $issue, UploadedFile $file): void
    {
        $path = 'issues/'.Str::ulid().'.pdf'; // generated name; the uploaded file name is never used
        Storage::disk('local')->putFileAs('issues', $file, basename($path));
        $issue->pdf_path = $path;
        $issue->pdf_bytes = $file->getSize();
    }

    private function library(): array
    {
        return Media::latest('id')->limit(120)->get()
            ->map(fn (Media $m) => ['id' => $m->id, 'url' => $m->url('thumb'), 'alt' => $m->alt, 'is_public' => $m->is_public])->all();
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::limit(Str::slug($title) ?: 'issue', 80, '');
        $slug = $base;
        for ($i = 2; Issue::where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }
}
