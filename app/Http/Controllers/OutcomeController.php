<?php

namespace App\Http\Controllers;

use App\Models\OutcomeCategory;
use App\Models\OutcomeDepartment;
use App\Models\OutcomeTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OutcomeController extends Controller
{
    public function index()
    {
        $this->authorizeNormal();

        $tasks = OutcomeTask::where('user_id', auth()->id())
            ->orderByDesc('start_date')
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('outcome.index', [
            'tasks' => $tasks,
            'categories' => $this->categoriesByUsage(),
            'departments' => OutcomeDepartment::orderBy('name')->pluck('name'),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeNormal();

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'category' => ['required', Rule::in(OutcomeCategory::pluck('name'))],
            'department' => ['nullable', Rule::in(OutcomeDepartment::pluck('name'))],
            'job_description' => 'nullable|string',
            'man_hour' => 'required|numeric|min:0|max:9999.99',
        ]);

        OutcomeTask::create($validated + ['user_id' => auth()->id()]);

        return redirect()->route('outcome.index')->with('success', 'Task added.');
    }

    public function toggle(OutcomeTask $outcomeTask)
    {
        $this->authorizeNormal();

        if ($outcomeTask->user_id !== auth()->id()) {
            abort(403);
        }

        $outcomeTask->update([
            'is_completed' => !$outcomeTask->is_completed,
        ]);

        return back()->with('success', 'Outcome updated.');
    }

    /**
     * Most-clicked tasks (by this user) float to the top; ties broken alphabetically.
     */
    private function categoriesByUsage()
    {
        $usage = OutcomeTask::select('category', DB::raw('COUNT(*) as uses'))
            ->where('user_id', auth()->id())
            ->groupBy('category');

        return OutcomeCategory::leftJoinSub($usage, 'usage', function ($join) {
                $join->on('usage.category', '=', 'outcome_categories.name');
            })
            ->orderByDesc(DB::raw('COALESCE(usage.uses, 0)'))
            ->orderBy('outcome_categories.name')
            ->pluck('outcome_categories.name');
    }

    private function authorizeNormal(): void
    {
        if (!auth()->user()->isNormal()) {
            abort(403);
        }
    }
}
