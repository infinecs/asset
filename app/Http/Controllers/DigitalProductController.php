<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\DigitalProduct;
use App\Models\Employee;
use Illuminate\Http\Request;

class DigitalProductController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeStaff();

        $query = DigitalProduct::with(['brand', 'employees'])->withCount('employees');

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('plan', 'like', '%' . $search . '%')
                    ->orWhereHas('brand', fn ($b) => $b->where('name', 'like', '%' . $search . '%'));
            });
        }

        $digitalProducts = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('digital-products.index', compact('digitalProducts'));
    }

    public function create()
    {
        $this->authorizeAdmin();

        $brands = Brand::orderBy('name')->get();
        $employees = Employee::where('status', 'active')->orderBy('name')->get();

        return view('digital-products.create', compact('brands', 'employees'));
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $validated = $request->validate($this->productRules());

        if ($request->filled('bulk_prefix') && $request->filled('bulk_range_start') && $request->filled('bulk_range_end')) {
            return $this->storeBulk($request, $validated);
        }

        $digital_product = DigitalProduct::create($validated);
        $this->syncEmployees($request, $digital_product);

        return redirect()->route('digital-products.show', $digital_product)->with('success', 'Digital product created successfully.');
    }

    public function show(DigitalProduct $digital_product)
    {
        $this->authorizeStaff();

        $digital_product->load(['brand', 'employees' => fn ($q) => $q->orderBy('name')]);

        return view('digital-products.show', ['digitalProduct' => $digital_product]);
    }

    public function edit(DigitalProduct $digital_product)
    {
        $this->authorizeAdmin();

        $brands = Brand::orderBy('name')->get();
        $employees = Employee::where('status', 'active')->orderBy('name')->get();
        $assignedEmployeeIds = $digital_product->employees()->pluck('employees.id')->all();

        return view('digital-products.edit', [
            'digitalProduct' => $digital_product,
            'brands' => $brands,
            'employees' => $employees,
            'assignedEmployeeIds' => $assignedEmployeeIds,
        ]);
    }

    public function update(Request $request, DigitalProduct $digital_product)
    {
        $this->authorizeAdmin();

        $validated = $request->validate($this->productRules());

        $digital_product->update($validated);
        $this->syncEmployees($request, $digital_product);

        return redirect()->route('digital-products.show', $digital_product)->with('success', 'Digital product updated successfully.');
    }

    public function destroy(DigitalProduct $digital_product)
    {
        $this->authorizeAdmin();

        $digital_product->delete();

        return redirect()->route('digital-products.index')->with('success', 'Digital product deleted.');
    }

    private function authorizeStaff(): void
    {
        if (!auth()->user()->isStaff()) {
            abort(403);
        }
    }

    private function authorizeAdmin(): void
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }
    }

    private function productRules(): array
    {
        return [
            'name' => 'required_without:bulk_prefix|nullable|string|max:255',
            'brand_id' => 'nullable|exists:brands,id',
            'plan' => 'nullable|string|max:255',
            'purchase_date' => 'nullable|date',
            'purchase_cost' => 'nullable|numeric|min:0',
            'renewal_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'employee_ids' => 'nullable|array',
            'employee_ids.*' => 'integer|exists:employees,id',
            'bulk_prefix' => 'nullable|string|max:200',
            'bulk_range_start' => 'nullable|integer|min:0|required_with:bulk_prefix',
            'bulk_range_end' => 'nullable|integer|min:0|gte:bulk_range_start|required_with:bulk_prefix',
        ];
    }

    private function storeBulk(Request $request, array $validated)
    {
        $start = (int) $validated['bulk_range_start'];
        $end = (int) $validated['bulk_range_end'];

        if ($end - $start > 200) {
            return redirect()->back()->withInput()->with('error', 'Range too large — please create at most 200 products at a time.');
        }

        $shared = collect($validated)->only(['brand_id', 'plan', 'purchase_date', 'purchase_cost', 'renewal_date', 'notes'])->all();
        $created = 0;

        for ($n = $start; $n <= $end; $n++) {
            DigitalProduct::create($shared + ['name' => $validated['bulk_prefix'] . $n]);
            $created++;
        }

        return redirect()->route('digital-products.index')->with('success', $created . ' digital product(s) created.');
    }

    private function syncEmployees(Request $request, DigitalProduct $digital_product): void
    {
        $newIds = collect($request->input('employee_ids', []))->map(fn ($id) => (int) $id)->unique();
        $currentIds = $digital_product->employees()->pluck('employees.id');

        $toRemove = $currentIds->diff($newIds);
        $toAdd = $newIds->diff($currentIds);

        if ($toRemove->isNotEmpty()) {
            $digital_product->employees()->detach($toRemove);
        }

        if ($toAdd->isNotEmpty()) {
            $digital_product->employees()->attach($toAdd->mapWithKeys(fn ($id) => [$id => ['assigned_at' => now()]]));
        }
    }
}
