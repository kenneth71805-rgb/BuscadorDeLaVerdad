<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Category;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function __invoke(Request $request, ReportService $reports)
    {
        $user = $request->user();
        $to = $this->day($request->query('to')) ?? now();
        $from = $this->day($request->query('from')) ?? $to->copy()->subDays(29);
        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }
        if ($from->diffInDays($to) > 366) {
            $from = $to->copy()->subDays(366);
        }

        // A Head only ever sees their own categories; editors may narrow down to one.
        $allowed = $user->hasRole(Role::CategoryHead) ? $user->categories()->pluck('categories.id')->all() : null;
        $chosen = $request->integer('category') ?: null;
        $scope = $allowed;
        if ($chosen && ($allowed === null || in_array($chosen, $allowed, true))) {
            $scope = [$chosen];
        }

        return Inertia::render('reports/index', $reports->build($from, $to, $scope) + [
            'category' => $chosen && $scope === [$chosen] ? $chosen : null,
            'categories' => Category::query()->when($allowed !== null, fn ($q) => $q->whereIn('id', $allowed))->orderBy('sort_order')->get(['id', 'name']),
            'overdue_days' => ReportService::OVERDUE_DAYS,
        ]);
    }

    private function day(mixed $value): ?Carbon
    {
        try {
            return $value ? Carbon::createFromFormat('!Y-m-d', (string) $value) ?: null : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
