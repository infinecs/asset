<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeHistory;
use App\Models\Workstation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The new joiner checklist's actions. The checklist itself is worked out from the employee's
 * records (Employee::onboardingSteps()), so most steps are done on their own pages.
 */
class EmployeeOnboardingController extends Controller
{
    /**
     * Seat the employee at a free desk. If they already sit somewhere, they move and the old
     * desk becomes a hot desk.
     */
    public function seat(Request $request, Employee $employee)
    {
        $this->authorizeAdmin();

        $validated = $request->validate(['workstation_id' => 'required|integer|exists:workstations,id']);
        $desk = Workstation::with('occupant')->findOrFail($validated['workstation_id']);

        if ($employee->status !== 'active') {
            throw ValidationException::withMessages(['workstation_id' => "{$employee->name} is not an active employee."]);
        }

        if ($desk->occupant_id && $desk->occupant_id !== $employee->id) {
            throw ValidationException::withMessages(['workstation_id' => "{$desk->occupant->name} already sits at {$desk->name}. Pick a free desk."]);
        }

        $previous = $employee->workstations()->whereKeyNot($desk->id)->get();

        DB::transaction(function () use ($employee, $desk, $previous) {
            $previous->each(fn (Workstation $w) => $w->update(['occupant_id' => null]));
            $desk->update(['occupant_id' => $employee->id]);

            EmployeeHistory::create([
                'employee_id' => $employee->id,
                'user_id' => auth()->id(),
                'action' => 'updated',
                'notes' => "Seated at {$desk->name}" . ($previous->isNotEmpty() ? ' (moved from ' . $previous->pluck('name')->join(', ') . ')' : ''),
            ]);
        });

        return redirect()->route('employees.show', $employee)->with('success', "{$employee->name} now sits at {$desk->name}.");
    }

    /**
     * Mark the new joiner as set up, which takes them off the onboarding list. Steps that
     * aren't done are noted in their history.
     */
    public function complete(Employee $employee)
    {
        $this->authorizeAdmin();

        $employee->load(['assets', 'workstations', 'digitalProducts', 'role']);
        $open = collect($employee->onboardingSteps())->reject(fn ($step) => $step['done'])->pluck('label');

        $employee->update(['onboarded_at' => now()]);

        EmployeeHistory::create([
            'employee_id' => $employee->id,
            'user_id' => auth()->id(),
            'action' => 'onboarded',
            'notes' => 'Onboarding marked complete' . ($open->isNotEmpty() ? '. Not done: ' . $open->join(', ') . '.' : '.'),
        ]);

        return redirect()->route('employees.show', $employee)->with('success', "Onboarding for {$employee->name} marked complete.");
    }

    private function authorizeAdmin(): void
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }
    }
}
