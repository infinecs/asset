<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetHistory;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\Location;
use App\Models\Role;
use App\Models\Team;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query = Employee::with(['role', 'team']);

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('id_number', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('work_location', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $employees = $query->orderByRaw('CAST(SUBSTRING(id_number, 4) AS UNSIGNED) ASC')->paginate(15)->withQueryString();

        return view('employees.index', compact('employees'));
    }

    public function bulkEditBirthdays(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }

        $query = Employee::where('status', 'active');

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('id_number', 'like', '%' . $search . '%');
            });
        }

        if ($request->input('missing') === '1') {
            $query->whereNull('date_of_birth');
        }

        $employees = $query->orderByRaw('CAST(SUBSTRING(id_number, 4) AS UNSIGNED) ASC')->paginate(50)->withQueryString();

        return view('employees.bulk-edit-birthdays', compact('employees'));
    }

    public function updateBirthdays(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'birthdays' => 'nullable|array',
            'birthdays.*' => 'nullable|date_format:d/m/Y',
        ]);

        $submitted = $validated['birthdays'] ?? [];
        $employees = Employee::whereIn('id', array_keys($submitted))->get()->keyBy('id');

        $updated = 0;
        foreach ($submitted as $employeeId => $date) {
            $employee = $employees->get((int) $employeeId);
            if (!$employee) {
                continue;
            }

            $newDate = $date ? Carbon::createFromFormat('d/m/Y', $date)->format('Y-m-d') : null;
            if ($employee->date_of_birth?->format('Y-m-d') !== $newDate) {
                $employee->date_of_birth = $newDate;
                $employee->save();
                $updated++;
            }
        }

        return redirect()->back()->with('success', "{$updated} birthday(s) updated.");
    }

    public function create()
    {
        $locations = Location::orderBy('name')->get();
        $roles = Role::orderBy('name')->get();
        $teams = Team::orderBy('name')->get();
        $managers = Employee::where('is_manager', true)->orderBy('name')->get();
        $employees = Employee::orderBy('name')->get();

        return view('employees.create', compact('locations', 'roles', 'teams', 'managers', 'employees'));
    }

    private function buildIdNumber(string $suffix): string
    {
        return 'INF' . preg_replace('/^(INF-?)+/i', '', trim($suffix));
    }

    public function store(Request $request)
    {
        $request->merge(['id_number' => $this->buildIdNumber($request->input('id_number_suffix', ''))]);

        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'id_number'        => 'required|string|max:50|unique:employees,id_number',
            'work_location'    => 'nullable|string|max:255',
            'email'            => 'required|email|unique:employees,email',
            'status'           => 'required|in:active,resigned',
            'date_of_birth'    => 'nullable|date',
            'role_id'          => 'nullable|exists:roles,id',
            'manager_id'       => 'nullable|exists:employees,id',
            'subordinate_ids'   => 'nullable|array',
            'subordinate_ids.*' => 'integer|exists:employees,id',
            'managed_team_ids'   => 'nullable|array',
            'managed_team_ids.*' => 'integer|exists:teams,id',
        ]);
        $validated['gift_card_opt_out'] = $request->boolean('gift_card_opt_out');
        $validated['is_manager'] = $request->boolean('is_manager');
        $subordinateIds = $validated['is_manager'] ? ($validated['subordinate_ids'] ?? []) : [];
        $managedTeamIds = $validated['is_manager'] ? ($validated['managed_team_ids'] ?? []) : [];
        unset($validated['subordinate_ids'], $validated['managed_team_ids']);

        $employee = Employee::create($validated);

        $this->syncSubordinates($employee, $subordinateIds);
        $this->syncManagedTeams($employee, $managedTeamIds);

        return redirect()->route('employees.index')->with('success', 'Employee added successfully.');
    }

    public function show(Employee $employee)
    {
        $employee->load(['assets.category', 'documents', 'digitalProducts.brand', 'role', 'team', 'manager', 'subordinates', 'managedTeams']);
        return view('employees.show', compact('employee'));
    }

    public function edit(Employee $employee)
    {
        $locations = Location::orderBy('name')->get();
        $roles = Role::orderBy('name')->get();
        $teams = Team::orderBy('name')->get();
        $managers = Employee::where('is_manager', true)->where('id', '!=', $employee->id)->orderBy('name')->get();
        $employees = Employee::where('id', '!=', $employee->id)->orderBy('name')->get();
        $employee->load(['subordinates', 'managedTeams']);

        return view('employees.edit', compact('employee', 'locations', 'roles', 'teams', 'managers', 'employees'));
    }

    public function update(Request $request, Employee $employee)
    {
        $request->merge(['id_number' => $this->buildIdNumber($request->input('id_number_suffix', ''))]);

        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'id_number'        => 'required|string|max:50|unique:employees,id_number,' . $employee->id,
            'work_location'    => 'nullable|string|max:255',
            'email'            => 'required|email|unique:employees,email,' . $employee->id,
            'status'           => 'required|in:active,resigned',
            'date_of_birth'    => 'nullable|date',
            'role_id'          => 'nullable|exists:roles,id',
            'manager_id'       => 'nullable|exists:employees,id',
            'subordinate_ids'   => 'nullable|array',
            'subordinate_ids.*' => 'integer|exists:employees,id',
            'managed_team_ids'   => 'nullable|array',
            'managed_team_ids.*' => 'integer|exists:teams,id',
        ]);
        $validated['gift_card_opt_out'] = $request->boolean('gift_card_opt_out');
        $validated['is_manager'] = $request->boolean('is_manager');
        $subordinateIds = $validated['is_manager'] ? ($validated['subordinate_ids'] ?? []) : [];
        $managedTeamIds = $validated['is_manager'] ? ($validated['managed_team_ids'] ?? []) : [];
        unset($validated['subordinate_ids'], $validated['managed_team_ids']);

        if ($employee->wouldCreateCycle($validated['manager_id'] ?? null)) {
            return back()->withErrors(['manager_id' => 'Invalid manager selection: this would create a reporting loop.'])->withInput();
        }

        foreach ($subordinateIds as $subordinateId) {
            $candidate = Employee::find($subordinateId);
            if ($candidate && $candidate->wouldCreateCycle($employee->id)) {
                return back()->withErrors(['subordinate_ids' => 'One of the selected subordinates would create a reporting loop.'])->withInput();
            }
        }

        $employee->update($validated);

        $this->syncSubordinates($employee, $subordinateIds);
        $this->syncManagedTeams($employee, $managedTeamIds);

        $returnTo = $request->input('return');
        $redirectUrl = ($returnTo && str_starts_with($returnTo, '/employees'))
            ? $returnTo
            : route('employees.index');

        return redirect($redirectUrl)->with('success', 'Employee updated successfully.');
    }

    /**
     * Assign the given employees as direct reports of $employee, and release any previous
     * direct reports that are no longer selected.
     */
    private function syncSubordinates(Employee $employee, array $subordinateIds): void
    {
        Employee::where('manager_id', $employee->id)
            ->whereNotIn('id', $subordinateIds)
            ->update(['manager_id' => null]);

        if (!empty($subordinateIds)) {
            Employee::whereIn('id', $subordinateIds)
                ->where('id', '!=', $employee->id)
                ->update(['manager_id' => $employee->id]);
        }
    }

    /**
     * Assign the given teams to be managed by $employee, and release any previously managed
     * teams that are no longer selected. An employee can manage several teams at once.
     */
    private function syncManagedTeams(Employee $employee, array $teamIds): void
    {
        Team::where('manager_id', $employee->id)
            ->whereNotIn('id', $teamIds)
            ->update(['manager_id' => null]);

        if (!empty($teamIds)) {
            Team::whereIn('id', $teamIds)->update(['manager_id' => $employee->id]);
        }
    }

    public function orgChart()
    {
        $employees = Employee::with(['role', 'team'])->where('status', 'active')->orderBy('name')->get();

        // Employees whose role is flagged as top-level (e.g. CEO) always sit at the very top of
        // the chart, above every other manager — regardless of their manager_id.
        $topLevel = $employees->filter(fn (Employee $employee) => (bool) $employee->role?->is_top_level)->sortBy('name');
        $topLevelIds = $topLevel->pluck('id');
        $chiefId = $topLevel->first()?->id;

        $byManager = $employees->groupBy(function (Employee $employee) use ($topLevelIds, $chiefId) {
            if ($topLevelIds->contains($employee->id)) {
                return null;
            }

            if (is_null($employee->manager_id) && $chiefId) {
                return $chiefId;
            }

            return $employee->manager_id;
        });
        $roots = $byManager->get(null, collect());

        return view('employees.org-chart', compact('roots', 'byManager'));
    }

    public function updateStatus(Request $request, Employee $employee)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => 'required|in:active,resigned',
        ]);

        $employee->update($validated);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'status' => $employee->fresh()->status_label]);
        }

        return redirect()->back()->with('success', 'Employee status updated.');
    }

    public function reclaimAssets(Request $request, Employee $employee)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'asset_ids' => 'required|array|min:1',
            'asset_ids.*' => 'integer|exists:assets,id',
            'status' => 'required|in:available,under_maintenance,retired,lost',
        ]);

        $assets = Asset::where('assigned_to', $employee->id)
            ->whereIn('id', $validated['asset_ids'])
            ->get();

        foreach ($assets as $asset) {
            $oldStatus = $asset->status;

            $asset->update([
                'assigned_to' => null,
                'status' => $validated['status'],
                'last_seen_at' => now(),
            ]);

            AssetHistory::create([
                'asset_id' => $asset->id,
                'user_id' => auth()->id(),
                'action' => 'reclaimed',
                'notes' => 'Reclaimed from ' . $employee->name . ' (' . $employee->status_label . ')',
                'changes' => [
                    'assigned_to' => ['old' => $employee->id, 'new' => null],
                    'status' => ['old' => $oldStatus, 'new' => $validated['status']],
                ],
            ]);
        }

        return redirect()->route('employees.show', $employee)->with('success', $assets->count() . ' asset(s) reclaimed.');
    }

    public function destroy(Employee $employee)
    {
        $employee->delete();
        return redirect()->route('employees.index')->with('success', 'Employee deleted.');
    }

    public function uploadDocument(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'document_type' => 'required|string|max:50',
            'file' => 'required|file|max:10240|mimes:pdf,doc,docx,jpg,jpeg,png,txt',
            'description' => 'nullable|string|max:500',
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('employee-documents/' . $employee->id, 'local');

            EmployeeDocument::create([
                'employee_id' => $employee->id,
                'document_type' => $validated['document_type'],
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'description' => $validated['description'] ?? null,
            ]);

            return redirect()->route('employees.show', $employee)->with('success', 'Document uploaded successfully.');
        }

        return redirect()->route('employees.show', $employee)->with('error', 'Failed to upload document.');
    }

    public function deleteDocument(Employee $employee, EmployeeDocument $document)
    {
        if ($document->employee_id !== $employee->id) {
            return redirect()->route('employees.show', $employee)->with('error', 'Unauthorized action.');
        }

        if (Storage::disk('local')->exists($document->file_path)) {
            Storage::disk('local')->delete($document->file_path);
        }

        $document->delete();

        return redirect()->route('employees.show', $employee)->with('success', 'Document deleted successfully.');
    }

    public function downloadDocument(Employee $employee, EmployeeDocument $document)
    {
        if ($document->employee_id !== $employee->id) {
            abort(403, 'Unauthorized action.');
        }

        if (!Storage::disk('local')->exists($document->file_path)) {
            abort(404, 'File not found.');
        }

        return Storage::disk('local')->download($document->file_path, $document->file_name);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $handle = fopen($request->file('file')->getPathname(), 'r');
        $imported = 0;
        $skipped  = [];
        $row      = 0;

        while (($data = fgetcsv($handle)) !== false) {
            $row++;
            if ($row === 1) continue; // skip header

            $data = array_map('trim', $data);

            if (count($data) < 4) {
                $skipped[] = "Row {$row}: not enough columns";
                continue;
            }

            [$name, $idSuffix, $workLocation, $email] = $data;

            if (empty($name) || empty($email)) {
                $skipped[] = "Row {$row}: name and email are required";
                continue;
            }

            $idNumber = $this->buildIdNumber($idSuffix);

            if (Employee::where('id_number', $idNumber)->orWhere('email', $email)->exists()) {
                $skipped[] = "Row {$row} ({$name}): duplicate ID or email";
                continue;
            }

            Employee::create([
                'name'          => $name,
                'id_number'     => $idNumber,
                'work_location' => $workLocation ?: null,
                'email'         => $email,
            ]);

            $imported++;
        }

        fclose($handle);

        $msg = "{$imported} employee(s) imported.";
        if (!empty($skipped)) {
            $msg .= ' Skipped — ' . implode('; ', $skipped) . '.';
        }

        return redirect()->route('employees.index')->with('success', $msg);
    }

    public function downloadTemplate()
    {
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="employees_template.csv"',
        ];

        $callback = function () {
            $h = fopen('php://output', 'w');
            fputcsv($h, ['Name', 'ID Suffix (after INF)', 'Work Location', 'Email']);
            fputcsv($h, ['Ahmad Razif', '001', 'Kuala Lumpur', 'ahmad.razif@infinecs.com']);
            fputcsv($h, ['Siti Noor', '002', 'Selangor', 'siti.noor@infinecs.com']);
            fclose($h);
        };

        return response()->stream($callback, 200, $headers);
    }
}
