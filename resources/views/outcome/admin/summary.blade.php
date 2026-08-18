@extends('layouts.app')

@section('title', 'Outcome Summary - IT Asset Management')
@section('page-title', 'Outcome Summary')

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h5 class="mb-1 text-lg font-semibold text-slate-900 dark:text-white">Summary Report</h5>
        <p class="mb-0 text-sm text-slate-500 dark:text-slate-400">Individual and accumulated outcome totals, in man days.</p>
    </div>
    <div class="inline-flex overflow-hidden rounded-lg border border-slate-300 dark:border-slate-700">
        <a href="{{ route('outcome.summary', array_filter(['view' => 'monthly', 'user_id' => request('user_id')])) }}" class="px-4 py-2 text-sm font-medium {{ $view === 'monthly' ? 'bg-primary-600 text-white' : 'bg-white text-slate-600 hover:bg-slate-50 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800' }}">Monthly</a>
        <a href="{{ route('outcome.summary', array_filter(['view' => 'yearly', 'user_id' => request('user_id')])) }}" class="border-l border-slate-300 px-4 py-2 text-sm font-medium dark:border-slate-700 {{ $view === 'yearly' ? 'bg-primary-600 text-white' : 'bg-white text-slate-600 hover:bg-slate-50 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800' }}">Yearly</a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('outcome.summary') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <input type="hidden" name="view" value="{{ $view }}">
            @if($view === 'monthly')
            <div>
                <label class="field-label">Month</label>
                <input type="month" name="month" class="field-input" value="{{ $selectedMonth }}">
            </div>
            @else
            <div>
                <label class="field-label">Year</label>
                <input type="number" name="year" class="field-input" value="{{ $selectedYear }}" min="2000" max="2100">
            </div>
            @endif
            <div>
                <label class="field-label">User</label>
                <select name="user_id" class="field-input">
                    <option value="">All Users</option>
                    @foreach($contingentWorkers as $contingentWorker)
                    <option value="{{ $contingentWorker->id }}" {{ (string) request('user_id') === (string) $contingentWorker->id ? 'selected' : '' }}>{{ $contingentWorker->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="field-label invisible">Action</label>
                <div class="flex gap-2">
                    <button type="submit" class="btn btn-primary !flex-1 !rounded !px-3 !py-2 !text-sm !shadow-none">Filter</button>
                    <a href="{{ route('outcome.summary', ['view' => $view]) }}" class="btn btn-outline !flex-1 !rounded !px-3 !py-2 !text-sm !shadow-none">Clear</a>
                </div>
            </div>
        </form>
    </div>
</div>

@if($view === 'monthly')
<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <p class="mb-0 text-sm text-slate-500 dark:text-slate-400">Showing: <span class="font-semibold text-slate-800 dark:text-slate-100">{{ $monthLabel }}</span></p>
    <div class="inline-flex overflow-hidden rounded-lg border border-slate-300 dark:border-slate-700">
        <a href="{{ route('outcome.summary', array_filter(['view' => 'monthly', 'month' => $selectedMonth, 'mode' => 'category', 'user_id' => request('user_id')])) }}" class="px-3 py-1.5 text-xs font-medium {{ $monthlyMode === 'category' ? 'bg-slate-800 text-white' : 'bg-white text-slate-600 hover:bg-slate-50 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800' }}">By Task Category</a>
        <a href="{{ route('outcome.summary', array_filter(['view' => 'monthly', 'month' => $selectedMonth, 'mode' => 'daily', 'user_id' => request('user_id')])) }}" class="border-l border-slate-300 px-3 py-1.5 text-xs font-medium dark:border-slate-700 {{ $monthlyMode === 'daily' ? 'bg-slate-800 text-white' : 'bg-white text-slate-600 hover:bg-slate-50 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800' }}">By Daily</a>
    </div>
</div>
@else
<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <p class="mb-0 text-sm text-slate-500 dark:text-slate-400">Showing: <span class="font-semibold text-slate-800 dark:text-slate-100">{{ $selectedYear }}</span></p>
    <div class="inline-flex overflow-hidden rounded-lg border border-slate-300 dark:border-slate-700">
        <a href="{{ route('outcome.summary', array_filter(['view' => 'yearly', 'year' => $selectedYear, 'mode' => 'category', 'user_id' => request('user_id')])) }}" class="px-3 py-1.5 text-xs font-medium {{ $yearlyMode === 'category' ? 'bg-slate-800 text-white' : 'bg-white text-slate-600 hover:bg-slate-50 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800' }}">By Task Category</a>
        <a href="{{ route('outcome.summary', array_filter(['view' => 'yearly', 'year' => $selectedYear, 'mode' => 'daily', 'user_id' => request('user_id')])) }}" class="border-l border-slate-300 px-3 py-1.5 text-xs font-medium dark:border-slate-700 {{ $yearlyMode === 'daily' ? 'bg-slate-800 text-white' : 'bg-white text-slate-600 hover:bg-slate-50 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800' }}">By Daily</a>
    </div>
</div>
@endif

<div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
    <div class="card">
        <div class="card-body">
            <div class="text-sm text-slate-500 dark:text-slate-400">Accumulated Tasks</div>
            <div class="text-2xl font-bold text-slate-900 dark:text-white">{{ $accumulated['task_count'] }}</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="text-sm text-slate-500 dark:text-slate-400">Accumulated Completed</div>
            <div class="text-2xl font-bold text-slate-900 dark:text-white">{{ $accumulated['completed_count'] }}</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="text-sm text-slate-500 dark:text-slate-400">Accumulated Man Days</div>
            <div class="text-2xl font-bold text-slate-900 dark:text-white">{{ rtrim(rtrim(number_format((float) $accumulated['total_man_days'], 2), '0'), '.') }}</div>
        </div>
    </div>
</div>

@php($fmt = fn($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.'))

@if($view === 'monthly')
    @if($monthlyMode === 'daily')
    @forelse($byUser as $user)
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $user['user_name'] }}</h6>
            <div class="flex gap-2">
                <button type="button" class="btn btn-sm btn-outline" onclick="previewUserSummary('userTable-{{ $loop->index }}', '{{ $user['user_name'] }}', '{{ $monthLabel }}')">
                    <i class="bi bi-eye"></i>Print Preview
                </button>
                <a href="{{ route('outcome.summary.export', ['view' => 'monthly', 'mode' => 'daily', 'month' => $selectedMonth, 'user_id' => $user['user_id']]) }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-file-earmark-spreadsheet"></i>Export CSV
                </a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-sm" id="userTable-{{ $loop->index }}">
                <thead>
                    <tr class="text-left">
                        <th class="border-b border-slate-200 p-3 text-slate-500 dark:border-slate-800 dark:text-slate-400">Date</th>
                        <th class="border-b border-slate-200 p-3 text-slate-500 dark:border-slate-800 dark:text-slate-400">Task</th>
                        <th class="border-b border-slate-200 p-3 text-slate-500 dark:border-slate-800 dark:text-slate-400">Department</th>
                        <th class="border-b border-slate-200 p-3 text-slate-500 dark:border-slate-800 dark:text-slate-400">Man Day</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($user['rows'] as $row)
                    <tr class="border-b border-slate-100 last:border-b-0 dark:border-slate-800">
                        <td class="p-3 text-slate-700 dark:text-slate-300">{{ $row->start_date->format('d-m-Y') }}</td>
                        <td class="p-3 font-semibold text-slate-800 dark:text-slate-100">{{ $row->category }}</td>
                        <td class="p-3 text-slate-700 dark:text-slate-300">{{ $row->department ?: '-' }}</td>
                        <td class="p-3 text-slate-700 dark:text-slate-300">{{ $fmt($row->man_hour / 8) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-slate-300 font-semibold dark:border-slate-700">
                        <td class="p-3 text-slate-900 dark:text-white" colspan="3">Total Man Day</td>
                        <td class="p-3 text-slate-900 dark:text-white">{{ $fmt($user['total_man_days']) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @empty
    <div class="card">
        <div class="p-8 text-center text-sm text-slate-500 dark:text-slate-400">No tasks logged for this month.</div>
    </div>
    @endforelse
    @else
    @forelse($byUserMonthlyCategory as $user)
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $user['user_name'] }}</h6>
            <div class="flex gap-2">
                <button type="button" class="btn btn-sm btn-outline" onclick="previewUserSummary('userTable-{{ $loop->index }}', '{{ $user['user_name'] }}', '{{ $monthLabel }}')">
                    <i class="bi bi-eye"></i>Print Preview
                </button>
                <a href="{{ route('outcome.summary.export', ['view' => 'monthly', 'mode' => 'category', 'month' => $selectedMonth, 'user_id' => $user['user_id']]) }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-file-earmark-spreadsheet"></i>Export CSV
                </a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-sm" id="userTable-{{ $loop->index }}">
                <thead>
                    <tr class="text-left">
                        <th class="border-b border-slate-200 p-3 text-slate-500 dark:border-slate-800 dark:text-slate-400">Task</th>
                        <th class="border-b border-slate-200 p-3 text-slate-500 dark:border-slate-800 dark:text-slate-400">Man Day</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($user['tasks'] as $task)
                    <tr class="border-b border-slate-100 last:border-b-0 dark:border-slate-800">
                        <td class="p-3 font-semibold text-slate-800 dark:text-slate-100">{{ $task['category'] }}</td>
                        <td class="p-3 text-slate-700 dark:text-slate-300">{{ $fmt($task['man_days']) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-slate-300 font-semibold dark:border-slate-700">
                        <td class="p-3 text-slate-900 dark:text-white">Total</td>
                        <td class="p-3 text-slate-900 dark:text-white">{{ $fmt($user['total_man_days']) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @empty
    <div class="card">
        <div class="p-8 text-center text-sm text-slate-500 dark:text-slate-400">No tasks logged for this month.</div>
    </div>
    @endforelse
    @endif
@else
    @if($yearlyMode === 'category')
    @forelse($byUserYearly as $user)
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $user['user_name'] }}</h6>
            <div class="flex gap-2">
                <button type="button" class="btn btn-sm btn-outline" onclick="previewUserSummary('userTable-{{ $loop->index }}', '{{ $user['user_name'] }}', '{{ $selectedYear }}')">
                    <i class="bi bi-eye"></i>Print Preview
                </button>
                <a href="{{ route('outcome.summary.export', ['view' => 'yearly', 'mode' => 'category', 'year' => $selectedYear, 'user_id' => $user['user_id']]) }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-file-earmark-spreadsheet"></i>Export CSV
                </a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-sm" id="userTable-{{ $loop->index }}">
                <thead>
                    <tr class="text-left">
                        <th class="border-b border-slate-200 p-3 text-slate-500 dark:border-slate-800 dark:text-slate-400">Task</th>
                        <th class="border-b border-slate-200 p-3 text-slate-500 dark:border-slate-800 dark:text-slate-400">{{ $selectedYear }} YTD</th>
                        @foreach($monthLabels as $label)
                        <th class="border-b border-slate-200 p-3 text-slate-500 dark:border-slate-800 dark:text-slate-400">{{ $label }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($user['tasks'] as $task)
                    <tr class="border-b border-slate-100 last:border-b-0 dark:border-slate-800">
                        <td class="p-3 font-semibold text-slate-800 dark:text-slate-100">{{ $task['category'] }}</td>
                        <td class="p-3 font-semibold text-slate-700 dark:text-slate-300">{{ $fmt($task['ytd']) }}</td>
                        @foreach($monthLabels as $num => $label)
                        <td class="p-3 text-slate-700 dark:text-slate-300">{{ $fmt($task['months'][$num]) }}</td>
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-slate-300 font-semibold dark:border-slate-700">
                        <td class="p-3 text-slate-900 dark:text-white">Total</td>
                        <td class="p-3 text-slate-900 dark:text-white">{{ $fmt($user['ytd']) }}</td>
                        @foreach($monthLabels as $num => $label)
                        <td class="p-3 text-slate-900 dark:text-white">{{ $fmt($user['monthly_totals'][$num]) }}</td>
                        @endforeach
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @empty
    <div class="card">
        <div class="p-8 text-center text-sm text-slate-500 dark:text-slate-400">No tasks logged for this year.</div>
    </div>
    @endforelse
    @else
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('outcome.summary') }}" class="space-y-3">
                <input type="hidden" name="view" value="yearly">
                <input type="hidden" name="mode" value="daily">
                <input type="hidden" name="year" value="{{ $selectedYear }}">
                <input type="hidden" name="user_id" value="{{ request('user_id') }}">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="field-label">From Month</label>
                        <input type="month" name="daily_from" class="field-input" value="{{ $dailyRange['from'] }}" min="{{ $selectedYear }}-01" max="{{ $selectedYear }}-12" required>
                    </div>
                    <div>
                        <label class="field-label">To Month</label>
                        <input type="month" name="daily_to" class="field-input" value="{{ $dailyRange['to'] }}" min="{{ $selectedYear }}-01" max="{{ $selectedYear }}-12" required>
                    </div>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p class="mb-0 text-xs text-slate-500 dark:text-slate-400">Pick the same month for a single month, or a wider range to cover several months.</p>
                    <button type="submit" class="btn btn-primary !rounded !px-3 !py-2 !text-sm !shadow-none">Show Daily Entries</button>
                </div>
            </form>
        </div>
    </div>

    @if(!$dailyRange['selected'])
    <div class="card">
        <div class="p-8 text-center text-sm text-slate-500 dark:text-slate-400">Choose a month or month range above, then click "Show Daily Entries" to view the daily log.</div>
    </div>
    @else
    @forelse($byUserYearlyDaily as $user)
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $user['user_name'] }}</h6>
            <div class="flex gap-2">
                <button type="button" class="btn btn-sm btn-outline" onclick="previewUserSummary('userTable-{{ $loop->index }}', '{{ $user['user_name'] }}', '{{ $selectedYear }}')">
                    <i class="bi bi-eye"></i>Print Preview
                </button>
                <a href="{{ route('outcome.summary.export', ['view' => 'yearly', 'mode' => 'daily', 'year' => $selectedYear, 'daily_from' => $dailyRange['from'], 'daily_to' => $dailyRange['to'], 'user_id' => $user['user_id']]) }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-file-earmark-spreadsheet"></i>Export CSV
                </a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-sm" id="userTable-{{ $loop->index }}">
                <thead>
                    <tr class="text-left">
                        <th class="border-b border-slate-200 p-3 text-slate-500 dark:border-slate-800 dark:text-slate-400">Date</th>
                        <th class="border-b border-slate-200 p-3 text-slate-500 dark:border-slate-800 dark:text-slate-400">Task</th>
                        <th class="border-b border-slate-200 p-3 text-slate-500 dark:border-slate-800 dark:text-slate-400">Department</th>
                        <th class="border-b border-slate-200 p-3 text-slate-500 dark:border-slate-800 dark:text-slate-400">Man Day</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($user['rows'] as $row)
                    <tr class="border-b border-slate-100 last:border-b-0 dark:border-slate-800">
                        <td class="p-3 text-slate-700 dark:text-slate-300">{{ $row->start_date->format('d-m-Y') }}</td>
                        <td class="p-3 font-semibold text-slate-800 dark:text-slate-100">{{ $row->category }}</td>
                        <td class="p-3 text-slate-700 dark:text-slate-300">{{ $row->department ?: '-' }}</td>
                        <td class="p-3 text-slate-700 dark:text-slate-300">{{ $fmt($row->man_hour / 8) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-slate-300 font-semibold dark:border-slate-700">
                        <td class="p-3 text-slate-900 dark:text-white" colspan="3">Total Man Day</td>
                        <td class="p-3 text-slate-900 dark:text-white">{{ $fmt($user['total_man_days']) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @empty
    <div class="card">
        <div class="p-8 text-center text-sm text-slate-500 dark:text-slate-400">No tasks logged in this range.</div>
    </div>
    @endforelse
    @endif
    @endif
@endif
@endsection

@push('scripts')
<script>
    function previewUserSummary(tableId, userName, periodLabel) {
        const tableHtml = document.getElementById(tableId).outerHTML;
        const previewWindow = window.open('', '_blank', 'width=1000,height=700');
        previewWindow.document.write(
            '<html><head><title>' + userName + ' - Outcome Summary</title><style>' +
            'body{font-family:Arial,Helvetica,sans-serif;padding:24px;color:#1e293b;}' +
            'h1{font-size:18px;margin-bottom:4px;}' +
            'p{margin-top:0;color:#64748b;font-size:13px;margin-bottom:16px;}' +
            'table{width:100%;border-collapse:collapse;margin-bottom:20px;}' +
            'th,td{border:1px solid #cbd5e1;padding:6px 8px;text-align:left;font-size:12px;}' +
            'th{background:#f1f5f9;}' +
            'tfoot td{font-weight:bold;border-top:2px solid #94a3b8;}' +
            '.print-btn{background:#4f46e5;color:#fff;border:none;padding:8px 16px;border-radius:6px;font-size:14px;cursor:pointer;margin-bottom:16px;}' +
            '@media print{.print-btn{display:none;}}' +
            '</style></head><body>' +
            '<button class="print-btn" onclick="window.print()">Print</button>' +
            '<h1>' + userName + ' - Outcome Summary</h1>' +
            '<p>' + periodLabel + '</p>' +
            tableHtml +
            '</body></html>'
        );
        previewWindow.document.close();
        previewWindow.focus();
    }
</script>
@endpush
