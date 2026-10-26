<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Models\AuditLog;
use App\Models\Category;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function index()
    {
        return Inertia::render('admin/categories/index', [
            'categories' => Category::orderBy('sort_order')->orderBy('name')->withCount('articles')->get()
                ->map(fn (Category $c) => $c->only(['id', 'name', 'slug', 'sort_order', 'is_active', 'show_in_nav']) + ['articles_count' => $c->articles_count]),
        ]);
    }

    public function create()
    {
        return Inertia::render('admin/categories/create');
    }

    public function store(CategoryRequest $request)
    {
        $data = $request->validated();
        $category = Category::create($data + ['slug' => Str::slug($data['name'])]);
        AuditLog::record('category.created', $category, ['name' => $category->name], $request->user());

        return redirect()->route('admin.categories.index')->with('success', "Category “{$category->name}” created.");
    }

    public function edit(Category $category)
    {
        return Inertia::render('admin/categories/edit', [
            'category' => $category->only(['id', 'name', 'slug', 'description', 'sort_order', 'is_active', 'show_in_nav']),
        ]);
    }

    public function update(CategoryRequest $request, Category $category)
    {
        $category->update($request->validated());
        AuditLog::record('category.updated', $category, $category->getChanges(), $request->user());

        return redirect()->route('admin.categories.index')->with('success', 'Category saved.');
    }
}
