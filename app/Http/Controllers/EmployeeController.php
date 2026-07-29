<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Location;
use Illuminate\Http\Request;

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

        $employees = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('employees.index', compact('employees'));
    }

    public function create()
    {
        $locations = Location::orderBy('name')->get();
        return view('employees.create', compact('locations'));
    }

    public function store(Request $request)
    {
        $request->merge(['id_number' => $this->normalizeIdNumber($request->input('id_number_suffix', ''))]);

        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'id_number'     => 'required|string|max:50|unique:employees,id_number',
            'work_location' => 'nullable|string|max:255',
            'email'         => 'required|email|unique:employees,email',
        ]);

        Employee::create($validated);

        return redirect()->route('employees.index')->with('success', 'Employee added successfully.');
    }

    public function show(Employee $employee)
    {
        $employee->load(['assets.category']);
        return view('employees.show', compact('employee'));
    }

    public function edit(Employee $employee)
    {
        $locations = Location::orderBy('name')->get();
        return view('employees.edit', compact('employee', 'locations'));
    }

    public function update(Request $request, Employee $employee)
    {
        $request->merge(['id_number' => $this->normalizeIdNumber($request->input('id_number_suffix', ''))]);

        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'id_number'     => 'required|string|max:50|unique:employees,id_number,' . $employee->id,
            'work_location' => 'nullable|string|max:255',
            'email'         => 'required|email|unique:employees,email,' . $employee->id,
        ]);

        $employee->update($validated);

        return redirect()->route('employees.index')->with('success', 'Employee updated successfully.');
    }

    public function destroy(Employee $employee)
    {
        $employee->delete();
        return redirect()->route('employees.index')->with('success', 'Employee deleted.');
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

            $idNumber = $this->normalizeIdNumber($idSuffix);

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

    private function normalizeIdNumber(string $value): string
    {
        $suffix = preg_replace('/^(?:INF-?)+/i', '', trim($value));

        return 'INF' . $suffix;
    }
}
