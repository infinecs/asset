<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function index()
    {
        $this->authorizeAdmin();

        $brands = Brand::withCount('assets')->orderBy('name')->get();
        return view('brands.index', compact('brands'));
    }

    public function create()
    {
        $this->authorizeAdmin();

        return view('brands.create');
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:brands,name',
        ]);

        Brand::create($validated);
        return redirect()->route('brands.index')->with('success', 'Brand created successfully.');
    }

    public function edit(Brand $brand)
    {
        $this->authorizeAdmin();

        return view('brands.edit', compact('brand'));
    }

    public function update(Request $request, Brand $brand)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:brands,name,' . $brand->id,
        ]);

        $brand->update($validated);
        return redirect()->route('brands.index')->with('success', 'Brand updated successfully.');
    }

    public function destroy(Brand $brand)
    {
        $this->authorizeAdmin();

        $brand->delete();
        return redirect()->route('brands.index')->with('success', 'Brand deleted.');
    }

    public function bulkDestroy(Request $request)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:brands,id',
        ]);

        $deleted = Brand::whereIn('id', $validated['ids'])->delete();

        return redirect()->back()->with('success', $deleted . ' brand(s) deleted successfully.');
    }

    public function destroyAll()
    {
        $this->authorizeAdmin();

        $deleted = Brand::query()->delete();

        return redirect()->back()->with('success', $deleted . ' brand(s) deleted successfully.');
    }

    private function authorizeAdmin(): void
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }
    }
}
