<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query = Employee::query();

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

    public function create()
    {
        $locations = Location::orderBy('name')->get();
        return view('employees.create', compact('locations'));
    }

    private function buildIdNumber(string $suffix): string
    {
        return 'INF' . preg_replace('/^(INF-?)+/i', '', trim($suffix));
    }

    public function store(Request $request)
    {
        $request->merge(['id_number' => $this->buildIdNumber($request->input('id_number_suffix', ''))]);

        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'id_number'     => 'required|string|max:50|unique:employees,id_number',
            'work_location' => 'nullable|string|max:255',
            'email'         => 'required|email|unique:employees,email',
            'status'        => 'required|in:active,resigned',
        ]);

        Employee::create($validated);

        return redirect()->route('employees.index')->with('success', 'Employee added successfully.');
    }

    public function show(Employee $employee)
    {
        $employee->load(['assets.category', 'documents', 'digitalProducts.brand']);
        return view('employees.show', compact('employee'));
    }

    public function edit(Employee $employee)
    {
        $locations = Location::orderBy('name')->get();
        return view('employees.edit', compact('employee', 'locations'));
    }

    public function update(Request $request, Employee $employee)
    {
        $request->merge(['id_number' => $this->buildIdNumber($request->input('id_number_suffix', ''))]);

        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'id_number'     => 'required|string|max:50|unique:employees,id_number,' . $employee->id,
            'work_location' => 'nullable|string|max:255',
            'email'         => 'required|email|unique:employees,email,' . $employee->id,
            'status'        => 'required|in:active,resigned',
        ]);

        $employee->update($validated);

        return redirect()->route('employees.index')->with('success', 'Employee updated successfully.');
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
