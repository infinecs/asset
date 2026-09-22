<?php

namespace App\Http\Controllers;

use App\Mail\BirthdayGiftCardMail;
use App\Models\Employee;
use App\Models\GiftCard;
use App\Models\GiftCardSetting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Mail;

class GiftCardController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeStaff();

        $view = in_array($request->input('view'), ['sent', 'opted_out'], true) ? $request->input('view') : 'upcoming';
        $windowDays = $request->input('range') === 'all' ? 366 : 60;

        if ($view === 'sent') {
            $rows = $this->sentThisYearRows($request);
        } else {
            $rows = $this->employeeRows($request, optedOut: $view === 'opted_out', windowDays: $windowDays);
        }

        $perPage = 15;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $rowsPaginator = new LengthAwarePaginator(
            $rows->forPage($page, $perPage),
            $rows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $setting = GiftCardSetting::current();

        return view('gift-cards.index', [
            'rows' => $rowsPaginator,
            'setting' => $setting,
            'picCount' => $setting->personsInCharge()->count(),
            'view' => $view,
        ]);
    }

    private function employeeRows(Request $request, bool $optedOut, int $windowDays)
    {
        $employees = Employee::where('status', 'active')
            ->whereNotNull('date_of_birth')
            ->where('gift_card_opt_out', $optedOut)
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = trim((string) $request->search);
                $q->where('name', 'like', "%{$search}%");
            })
            ->get();

        $giftCards = GiftCard::whereIn('employee_id', $employees->pluck('id'))
            ->get()
            ->keyBy(fn (GiftCard $gc) => $gc->employee_id . '-' . $gc->occasion_year);

        $rows = $employees->map(function (Employee $employee) use ($giftCards) {
            $occurrence = $employee->nextBirthdayOccurrence();

            return [
                'employee' => $employee,
                'occurrence' => $occurrence,
                'days_until' => (int) Carbon::today()->diffInDays($occurrence),
                'gift_card' => $giftCards->get($employee->id . '-' . $occurrence->year),
            ];
        });

        return $optedOut
            ? $rows->sortBy(fn ($row) => $row['employee']->name)->values()
            : $rows->filter(fn ($row) => $row['days_until'] <= $windowDays)->sortBy('days_until')->values();
    }

    /**
     * Gift cards already sent for the current calendar year — kept visible here instead of
     * disappearing from the "Upcoming" list once the employee's birthday has passed.
     */
    private function sentThisYearRows(Request $request)
    {
        $currentYear = Carbon::today()->year;

        $giftCards = GiftCard::with('employee')
            ->where('status', 'sent')
            ->where('occasion_year', $currentYear)
            ->whereHas('employee', function ($q) use ($request) {
                if ($request->filled('search')) {
                    $search = trim((string) $request->search);
                    $q->where('name', 'like', "%{$search}%");
                }
            })
            ->orderByDesc('sent_at')
            ->get();

        return $giftCards->map(fn (GiftCard $giftCard) => [
            'employee' => $giftCard->employee,
            'occurrence' => $giftCard->birthday_date,
            'days_until' => 0,
            'gift_card' => $giftCard,
        ])->values();
    }

    public function updateCode(Request $request, Employee $employee)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'occasion_year' => 'required|integer',
            'gift_card_code' => 'nullable|string|max:255',
        ]);

        if ($employee->gift_card_opt_out) {
            return redirect()->route('gift-cards.index')->with('error', $employee->name . ' has opted out of the gift card program.');
        }

        $requestedYear = (int) $validated['occasion_year'];
        $occurrence = $employee->nextBirthdayOccurrence();
        $isCurrentUpcoming = $occurrence && $occurrence->year === $requestedYear;

        $giftCard = GiftCard::firstOrNew([
            'employee_id' => $employee->id,
            'occasion_year' => $requestedYear,
        ]);

        // Allow editing an existing record (e.g. correcting an already-sent card) even though it
        // no longer matches the employee's currently-computed next occurrence; only block attempts
        // to fabricate a brand new record for a year that isn't actually due.
        if (!$giftCard->exists && !$isCurrentUpcoming) {
            return redirect()->route('gift-cards.index')->with('error', 'That birthday occurrence is no longer current — please refresh and try again.');
        }

        if ($isCurrentUpcoming) {
            $giftCard->birthday_date = $occurrence;
        }
        $giftCard->gift_card_code = $validated['gift_card_code'] ?: null;

        if ($giftCard->status !== 'sent') {
            $giftCard->status = $giftCard->gift_card_code ? 'ready' : 'pending';
        }

        $giftCard->save();

        return redirect()->route('gift-cards.index')->with('success', "Touch 'n Go eWallet reload updated for " . $employee->name . '.');
    }

    public function testSend(Request $request, Employee $employee)
    {
        $this->authorizeTestSend();

        $validated = $request->validate([
            'occasion_year' => 'required|integer',
        ]);

        if ($employee->gift_card_opt_out) {
            return redirect()->route('gift-cards.index')->with('error', $employee->name . ' has opted out of the gift card program.');
        }

        $giftCard = GiftCard::where('employee_id', $employee->id)
            ->where('occasion_year', (int) $validated['occasion_year'])
            ->first();

        if (!$giftCard || !$giftCard->gift_card_code) {
            return redirect()->route('gift-cards.index')->with('error', "Set a Touch 'n Go eWallet reload reference for " . $employee->name . ' before sending a test.');
        }

        $recipient = auth()->user()->email;

        Mail::to($recipient)->send(new BirthdayGiftCardMail($employee, $giftCard, isTest: true));

        return redirect()->route('gift-cards.index')->with('success', "Test email sent to {$recipient}.");
    }

    public function previewBirthdayTemplate()
    {
        $this->authorizeAdmin();

        $employee = new Employee([
            'name' => 'Jane Doe',
            'id_number' => 'INF0000',
            'email' => 'jane.doe@example.com',
        ]);

        $giftCard = new GiftCard([
            'gift_card_code' => 'TNG-RELOAD-RM50-SAMPLE',
            'birthday_date' => Carbon::today(),
        ]);

        return response((new BirthdayGiftCardMail($employee, $giftCard))->render());
    }

    public function settings()
    {
        $this->authorizeAdmin();

        $setting = GiftCardSetting::current();
        $employees = Employee::where('status', 'active')->orderBy('name')->get();
        $assignedIds = $setting->personsInCharge()->pluck('employees.id')->all();

        return view('gift-cards.settings', compact('setting', 'employees', 'assignedIds'));
    }

    public function updateSettings(Request $request)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'person_in_charge_ids' => 'nullable|array',
            'person_in_charge_ids.*' => 'integer|exists:employees,id',
        ]);

        $setting = GiftCardSetting::current();
        $setting->personsInCharge()->sync($validated['person_in_charge_ids'] ?? []);

        return redirect()->route('gift-cards.index')->with('success', 'Gift card settings updated.');
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

    private function authorizeTestSend(): void
    {
        $this->authorizeAdmin();

        if (auth()->user()->email !== 'faris.razhi@infinecs.com') {
            abort(403);
        }
    }
}
