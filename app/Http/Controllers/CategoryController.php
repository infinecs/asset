<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        $this->authorizeAdmin();

        $categories = Category::withCount('assets')->orderBy('name')->get();
        return view('categories.index', compact('categories'));
    }

    public function create()
    {
        $this->authorizeAdmin();

        return view('categories.create');
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'description' => 'nullable|string',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        Category::create($validated);
        return redirect()->route('categories.index')->with('success', 'Category created successfully.');
    }

    public function edit(Category $category)
    {
        $this->authorizeAdmin();

        return view('categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
            'description' => 'nullable|string',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $category->update($validated);
        return redirect()->route('categories.index')->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category)
    {
        $this->authorizeAdmin();

        $category->delete();
        return redirect()->route('categories.index')->with('success', 'Category deleted.');
    }

    public function bulkDestroy(Request $request)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:categories,id',
        ]);

        $deleted = Category::whereIn('id', $validated['ids'])->delete();

        return redirect()->back()->with('success', $deleted . ' category(ies) deleted successfully.');
    }

    public function destroyAll()
    {
        $this->authorizeAdmin();

        $deleted = Category::query()->delete();

        return redirect()->back()->with('success', $deleted . ' category(ies) deleted successfully.');
    }

    private function authorizeAdmin(): void
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }
    }
}
