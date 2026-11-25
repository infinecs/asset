@extends('layouts.app')
@section('title', 'Headcount & Turnover')
@section('page-title', 'Headcount')
@section('content')
@php
    use App\Http\Controllers\HeadcountController;

    $n = count($series);
    $periodLabel = $periodStart->format('M Y') . ' – ' . $today->format('M Y');

    // A round axis step for values up to $max, aiming for about $ticks steps.
    $niceStep = function (float $max, int $ticks = 4): float {
        if ($max <= 0) {
            return 1;
        }
        $raw = $max / $ticks;
        $pow = 10 ** floor(log10($raw));
        foreach ([1, 2, 5, 10] as $m) {
            if ($raw <= $m * $pow) {
                return max(1, $m * $pow);
            }
        }
        return max(1, 10 * $pow);
    };

    // Shared geometry: both charts use the same x positions, so months line up between them.
    $W = 720; $left = 48; $right = 20; $plotW = $W - $left - $right;
    $band = $plotW / max($n, 1);
    $xAt = fn (int $i) => $left + $band * ($i + 0.5);
    $labelEvery = (int) ceil($n / 12);

    // Headcount line
    $H1 = 220; $top1 = 18; $bottom1 = 28; $plotH1 = $H1 - $top1 - $bottom1;
    $maxHead = max(array_column($series, 'headcount') ?: [0]);
    $step1 = $niceStep($maxHead);
    $yMax1 = max($step1, ceil($maxHead / $step1) * $step1);
    $y1 = fn (float $v) => $top1 + $plotH1 - ($v / $yMax1) * $plotH1;
    $points = collect($series)->map(fn ($m, $i) => round($xAt($i), 1) . ',' . round($y1($m['headcount']), 1));
    $linePath = 'M' . $points->implode(' L');
    $areaPath = $linePath . ' L' . round($xAt($n - 1), 1) . ',' . ($top1 + $plotH1) . ' L' . round($xAt(0), 1) . ',' . ($top1 + $plotH1) . ' Z';

    // Joiners (up) and leavers (down) around one baseline, on one symmetric scale.
    $H2 = 200; $top2 = 12; $bottom2 = 28; $plotH2 = $H2 - $top2 - $bottom2;
    $maxFlow = max(max(array_column($series, 'joined') ?: [0]), max(array_column($series, 'left') ?: [0]), 1);
    $step2 = $niceStep($maxFlow, 2);
    $flowMax = max($step2, ceil($maxFlow / $step2) * $step2);
    $base2 = $top2 + $plotH2 / 2;
    $hFlow = fn (float $v) => ($v / $flowMax) * ($plotH2 / 2);
    $barW = min(24, $band * 0.6);
    // A column with a 4px rounded data end and a square end on the baseline.
    $column = function (float $cx, float $baseY, float $h, bool $up) use ($barW): string {
        if ($h <= 0) {
            return '';
        }
        $x0 = $cx - $barW / 2; $x1 = $cx + $barW / 2;
        $r = min(4, $h, $barW / 2);
        $gap = 1; // half of the 2px surface gap at the baseline
        if ($up) {
            $b = $baseY - $gap; $t = $baseY - $gap - $h;
            return "M{$x0},{$b} L{$x0}," . ($t + $r) . " Q{$x0},{$t} " . ($x0 + $r) . ",{$t} L" . ($x1 - $r) . ",{$t} Q{$x1},{$t} {$x1}," . ($t + $r) . " L{$x1},{$b} Z";
        }
        $t = $baseY + $gap; $b = $baseY + $gap + $h;
        return "M{$x0},{$t} L{$x0}," . ($b - $r) . " Q{$x0},{$b} " . ($x0 + $r) . ",{$b} L" . ($x1 - $r) . ",{$b} Q{$x1},{$b} {$x1}," . ($b - $r) . " L{$x1},{$t} Z";
    };
    $tip = fn (array $m) => json_encode(['title' => $m['label'], 'rows' => [['Headcount', $m['headcount'], null], ['Joined', $m['joined'], 's1'], ['Left', $m['left'], 's2']]]);
    $maxBreakdown = fn ($counts) => max($counts->max() ?: 0, 1);
@endphp
<style>
    /* Two series (joined, left), validated for colourblind separation on the light and dark card surfaces. */
    .hc-viz { --s1: #2a78d6; --s2: #e34948; --surface: #ffffff; --grid: #e2e8f0; --axis: #cbd5e1; }
    .dark .hc-viz { --s1: #3987e5; --s2: #e66767; --surface: #0f172a; --grid: #1e293b; --axis: #334155; }
    /* In viewBox units: the 720-wide charts render at roughly 500-700px, so this lands at about 10-13px. */
    .hc-viz svg text { font-size: 15px; }
    .hc-viz svg text.end-label { font-size: 17px; }
    .hc-viz .hit { fill: transparent; cursor: default; outline: none; }
    .hc-viz .hit:focus-visible { fill: var(--grid); fill-opacity: .5; }
    .hc-viz .bar-s1 { fill: var(--s1); }
    .hc-viz .bar-s2 { fill: var(--s2); }
    .hc-viz .meter { height: 8px; border-radius: 0 4px 4px 0; background: var(--s1); }
</style>

<div class="hc-viz">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h5 class="text-lg font-semibold text-slate-900 dark:text-white">Headcount &amp; Turnover</h5>
            <p class="mb-0 text-sm text-slate-500 dark:text-slate-400">Who is on staff, who joined and who left, from join and resignation dates</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('employees.calendar') }}" class="btn btn-outline"><i class="bi bi-calendar-event"></i>People Calendar</a>
            <a href="{{ route('employees.index') }}" class="btn btn-outline"><i class="bi bi-arrow-left"></i>Employees</a>
        </div>
    </div>

    {{-- Filters: one row, scoping everything below --}}
    <form method="GET" class="mb-4 flex flex-wrap items-center gap-2">
        <label for="hc-months" class="sr-only">Period</label>
        <select id="hc-months" name="months" class="field-input w-auto" onchange="this.form.submit()">
            @foreach(HeadcountController::PERIODS as $p)
            <option value="{{ $p }}" @selected($months === $p)>Last {{ $p }} months</option>
            @endforeach
        </select>
        <label for="hc-team" class="sr-only">Team</label>
        <select id="hc-team" name="team" class="field-input w-auto" onchange="this.form.submit()">
            <option value="">All teams</option>
            @foreach($filters['teams'] as $team)
            <option value="{{ $team->id }}" @selected(request('team') == $team->id)>{{ $team->name }}</option>
            @endforeach
        </select>
        <label for="hc-location" class="sr-only">Work location</label>
        <select id="hc-location" name="location" class="field-input w-auto" onchange="this.form.submit()">
            <option value="">All locations</option>
            @foreach($filters['locations'] as $location)
            <option value="{{ $location }}" @selected(request('location') === $location)>{{ $location }}</option>
            @endforeach
        </select>
        <noscript><button class="btn btn-primary btn-sm">Apply</button></noscript>
        <span class="text-xs text-slate-500 dark:text-slate-400">{{ $periodLabel }}</span>
    </form>

    {{-- KPI row --}}
    <div class="mb-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="card"><div class="card-body">
            <div class="text-sm text-slate-500 dark:text-slate-400">Headcount today</div>
            <div class="mt-1 text-3xl font-semibold text-slate-900 dark:text-white">{{ number_format($summary['headcount']) }}</div>
            <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                <i class="bi bi-{{ $summary['change'] > 0 ? 'arrow-up-right' : ($summary['change'] < 0 ? 'arrow-down-right' : 'dash') }}"></i>
                {{ $summary['change'] > 0 ? '+' : '' }}{{ $summary['change'] }} since {{ $periodStart->format('M Y') }}
            </div>
        </div></div>
        <div class="card"><div class="card-body">
            <div class="text-sm text-slate-500 dark:text-slate-400">Joined</div>
            <div class="mt-1 text-3xl font-semibold text-slate-900 dark:text-white">{{ number_format($summary['joined']) }}</div>
            <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                in the last {{ $months }} months
                @if($summary['starting'])
                · {{ $summary['starting'] }} starting soon
                @endif
            </div>
        </div></div>
        <div class="card"><div class="card-body">
            <div class="text-sm text-slate-500 dark:text-slate-400">Left</div>
            <div class="mt-1 text-3xl font-semibold text-slate-900 dark:text-white">{{ number_format($summary['left']) }}</div>
            <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">in the last {{ $months }} months</div>
        </div></div>
        <div class="card"><div class="card-body">
            <div class="text-sm text-slate-500 dark:text-slate-400">Turnover rate</div>
            <div class="mt-1 text-3xl font-semibold text-slate-900 dark:text-white">{{ $summary['turnover'] !== null ? $summary['turnover'] . '%' : '—' }}</div>
            <div class="mt-1 text-xs text-slate-500 dark:text-slate-400" title="Leavers divided by the average headcount over the period">
                leavers ÷ average headcount{{ $months !== 12 ? ', over ' . $months . ' months' : ', last 12 months' }}
            </div>
        </div></div>
    </div>

    <div class="mb-4 grid grid-cols-1 gap-4 xl:grid-cols-2">
        {{-- Headcount over time --}}
        <div class="card">
            <div class="card-header">
                <h6 class="text-sm font-semibold text-slate-800 dark:text-slate-100">Headcount at the end of each month</h6>
            </div>
            <div class="card-body">
                <div class="relative" data-chart>
                    <svg viewBox="0 0 {{ $W }} {{ $H1 }}" class="block h-auto w-full" role="img"
                         aria-label="Headcount at the end of each month, {{ $periodLabel }}: from {{ $series[0]['headcount'] }} to {{ $series[$n - 1]['headcount'] }}">
                        @for($v = 0; $v <= $yMax1; $v += $step1)
                        <line x1="{{ $left }}" x2="{{ $W - $right }}" y1="{{ $y1($v) }}" y2="{{ $y1($v) }}" stroke="{{ $v == 0 ? 'var(--axis)' : 'var(--grid)' }}" stroke-width="1" />
                        <text x="{{ $left - 8 }}" y="{{ $y1($v) + 4 }}" text-anchor="end" class="fill-slate-400" style="font-variant-numeric: tabular-nums">{{ number_format($v) }}</text>
                        @endfor
                        @foreach($series as $i => $m)
                        @if($i % $labelEvery === 0 || $i === $n - 1)
                        <text x="{{ $xAt($i) }}" y="{{ $H1 - 8 }}" text-anchor="middle" class="fill-slate-400">{{ $m['short'] }}{{ str_starts_with($m['label'], 'Jan') ? ' ' . substr($m['label'], -2) : '' }}</text>
                        @endif
                        @endforeach
                        <path d="{{ $areaPath }}" fill="var(--s1)" fill-opacity="0.1" />
                        <path d="{{ $linePath }}" fill="none" stroke="var(--s1)" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" />
                        <line data-crosshair x1="0" x2="0" y1="{{ $top1 }}" y2="{{ $top1 + $plotH1 }}" stroke="var(--axis)" stroke-width="1" visibility="hidden" />
                        {{-- End point and its value --}}
                        <circle cx="{{ $xAt($n - 1) }}" cy="{{ $y1($series[$n - 1]['headcount']) }}" r="4" fill="var(--s1)" stroke="var(--surface)" stroke-width="2" />
                        <text x="{{ $xAt($n - 1) - 8 }}" y="{{ $y1($series[$n - 1]['headcount']) - 10 }}" text-anchor="end" class="end-label fill-slate-700 dark:fill-slate-200" font-weight="600">{{ number_format($series[$n - 1]['headcount']) }}</text>
                        @foreach($series as $i => $m)
                        <rect class="hit" tabindex="0" x="{{ $xAt($i) - $band / 2 }}" y="{{ $top1 }}" width="{{ $band }}" height="{{ $plotH1 }}"
                              data-x="{{ $xAt($i) }}" data-tip="{{ $tip($m) }}" aria-label="{{ $m['label'] }}: headcount {{ $m['headcount'] }}, joined {{ $m['joined'] }}, left {{ $m['left'] }}" />
                        @endforeach
                    </svg>
                    <div data-tooltip class="pointer-events-none absolute z-10 hidden min-w-36 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs shadow-lg dark:border-slate-700 dark:bg-slate-900"></div>
                </div>
            </div>
        </div>

        {{-- Joiners and leavers --}}
        <div class="card">
            <div class="card-header">
                <h6 class="text-sm font-semibold text-slate-800 dark:text-slate-100">Joined and left each month</h6>
                <div class="flex items-center gap-3 text-xs text-slate-600 dark:text-slate-300">
                    <span class="flex items-center gap-1.5"><svg width="12" height="10" aria-hidden="true"><rect width="12" height="10" rx="2" class="bar-s1" /></svg>Joined</span>
                    <span class="flex items-center gap-1.5"><svg width="12" height="10" aria-hidden="true"><rect width="12" height="10" rx="2" class="bar-s2" /></svg>Left</span>
                </div>
            </div>
            <div class="card-body">
                <div class="relative" data-chart>
                    <svg viewBox="0 0 {{ $W }} {{ $H2 }}" class="block h-auto w-full" role="img"
                         aria-label="People who joined (above the line) and left (below) each month, {{ $periodLabel }}: {{ $summary['joined'] }} joined, {{ $summary['left'] }} left">
                        @foreach([$flowMax, $flowMax / 2] as $v)
                        @if($v == floor($v))
                        <line x1="{{ $left }}" x2="{{ $W - $right }}" y1="{{ $base2 - $hFlow($v) }}" y2="{{ $base2 - $hFlow($v) }}" stroke="var(--grid)" stroke-width="1" />
                        <line x1="{{ $left }}" x2="{{ $W - $right }}" y1="{{ $base2 + $hFlow($v) }}" y2="{{ $base2 + $hFlow($v) }}" stroke="var(--grid)" stroke-width="1" />
                        <text x="{{ $left - 8 }}" y="{{ $base2 - $hFlow($v) + 4 }}" text-anchor="end" class="fill-slate-400" style="font-variant-numeric: tabular-nums">{{ (int) $v }}</text>
                        <text x="{{ $left - 8 }}" y="{{ $base2 + $hFlow($v) + 4 }}" text-anchor="end" class="fill-slate-400" style="font-variant-numeric: tabular-nums">{{ (int) $v }}</text>
                        @endif
                        @endforeach
                        <text x="{{ $left - 8 }}" y="{{ $base2 + 4 }}" text-anchor="end" class="fill-slate-400">0</text>
                        @foreach($series as $i => $m)
                        <path d="{{ $column($xAt($i), $base2, $hFlow($m['joined']), true) }}" class="bar-s1" />
                        <path d="{{ $column($xAt($i), $base2, $hFlow($m['left']), false) }}" class="bar-s2" />
                        @if($i % $labelEvery === 0 || $i === $n - 1)
                        <text x="{{ $xAt($i) }}" y="{{ $H2 - 8 }}" text-anchor="middle" class="fill-slate-400">{{ $m['short'] }}{{ str_starts_with($m['label'], 'Jan') ? ' ' . substr($m['label'], -2) : '' }}</text>
                        @endif
                        @endforeach
                        <line x1="{{ $left }}" x2="{{ $W - $right }}" y1="{{ $base2 }}" y2="{{ $base2 }}" stroke="var(--axis)" stroke-width="1" />
                        @foreach($series as $i => $m)
                        <rect class="hit" tabindex="0" x="{{ $xAt($i) - $band / 2 }}" y="{{ $top2 }}" width="{{ $band }}" height="{{ $plotH2 }}"
                              data-tip="{{ $tip($m) }}" aria-label="{{ $m['label'] }}: joined {{ $m['joined'] }}, left {{ $m['left'] }}" />
                        @endforeach
                    </svg>
                    <div data-tooltip class="pointer-events-none absolute z-10 hidden min-w-36 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs shadow-lg dark:border-slate-700 dark:bg-slate-900"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Table view of both charts --}}
    <details class="card mb-4">
        <summary class="cursor-pointer select-none px-5 py-3 text-sm font-semibold text-slate-700 dark:text-slate-200"><i class="bi bi-table me-2 text-slate-400"></i>Month by month, as a table</summary>
        <div class="overflow-x-auto border-t border-slate-200 dark:border-slate-800">
            <table class="table-clean">
                <thead><tr><th>Month</th><th class="text-right">Joined</th><th class="text-right">Left</th><th class="text-right">Headcount at month end</th></tr></thead>
                <tbody>
                    @foreach(array_reverse($series) as $m)
                    <tr>
                        <td>{{ $m['label'] }}</td>
                        <td class="text-right tabular-nums">{{ $m['joined'] }}</td>
                        <td class="text-right tabular-nums">{{ $m['left'] }}</td>
                        <td class="text-right font-semibold tabular-nums">{{ $m['headcount'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </details>

    {{-- Who is on staff today --}}
    <div class="mb-4 grid grid-cols-1 gap-4 lg:grid-cols-2 xl:grid-cols-4">
        @foreach($breakdowns as $title => $counts)
        <div class="card">
            <div class="card-header"><h6 class="text-sm font-semibold text-slate-800 dark:text-slate-100">By {{ strtolower($title) }}</h6></div>
            <div class="card-body space-y-2.5">
                @forelse($counts as $name => $count)
                <div>
                    <div class="flex items-baseline justify-between gap-2 text-sm">
                        <span class="truncate text-slate-700 dark:text-slate-200" title="{{ $name }}">{{ $name }}</span>
                        <span class="shrink-0 tabular-nums font-semibold text-slate-800 dark:text-slate-100">{{ $count }}</span>
                    </div>
                    <div class="mt-1 rounded-r bg-slate-100 dark:bg-slate-800"><div class="meter" style="width: {{ round($count / $maxBreakdown($counts) * 100, 1) }}%"></div></div>
                </div>
                @empty
                <p class="text-sm text-slate-400">Nobody on staff.</p>
                @endforelse
            </div>
        </div>
        @endforeach
        <div class="card">
            <div class="card-header">
                <h6 class="text-sm font-semibold text-slate-800 dark:text-slate-100">Time with the company</h6>
                @if($averageTenure !== null)<span class="text-xs text-slate-500 dark:text-slate-400">average {{ $averageTenure }} years</span>@endif
            </div>
            <div class="card-body space-y-2.5">
                @foreach($tenure as $bandName => $count)
                <div>
                    <div class="flex items-baseline justify-between gap-2 text-sm">
                        <span class="text-slate-700 dark:text-slate-200">{{ $bandName }}</span>
                        <span class="tabular-nums font-semibold text-slate-800 dark:text-slate-100">{{ $count }}</span>
                    </div>
                    <div class="mt-1 rounded-r bg-slate-100 dark:bg-slate-800"><div class="meter" style="width: {{ round($count / $maxBreakdown($tenure) * 100, 1) }}%"></div></div>
                </div>
                @endforeach
                @if($caveats['noJoinDate'])
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $caveats['noJoinDate'] }} more with no join date.</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Who joined and who left --}}
    <div class="mb-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
        @foreach(['Joined' => $joiners, 'Left' => $leavers] as $title => $people)
        <div class="card">
            <div class="card-header">
                <h6 class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $title }} in the last {{ $months }} months</h6>
                <span class="badge badge-secondary">{{ $people->count() }}</span>
            </div>
            <div class="overflow-x-auto">
                <table class="table-clean">
                    <thead><tr><th>Name</th><th>Team</th><th>{{ $title === 'Joined' ? 'Joined' : 'Last day' }}</th>@if($title === 'Left')<th>Stayed</th>@endif</tr></thead>
                    <tbody>
                        @forelse($people->take(10) as $person)
                        <tr>
                            <td><a href="{{ route('employees.show', $person) }}" class="font-semibold text-slate-800 hover:text-primary-600 dark:text-slate-100 dark:hover:text-primary-400">{{ $person->name }}</a></td>
                            <td class="text-slate-500 dark:text-slate-400">{{ $person->team?->name ?? $person->managedTeams->first()?->name ?? '—' }}</td>
                            <td class="whitespace-nowrap text-slate-500 dark:text-slate-400">{{ ($title === 'Joined' ? $person->join_date : $person->resigned_date)->format('d M Y') }}</td>
                            @if($title === 'Left')
                            <td class="whitespace-nowrap text-slate-500 dark:text-slate-400">{{ $person->join_date ? $person->join_date->diffForHumans($person->resigned_date, \Carbon\CarbonInterface::DIFF_ABSOLUTE, false, 2) : '—' }}</td>
                            @endif
                        </tr>
                        @empty
                        <tr><td colspan="4" class="py-6 text-center text-sm text-slate-400">Nobody.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($people->count() > 10)
            <div class="card-footer text-xs text-slate-500 dark:text-slate-400">
                And {{ $people->count() - 10 }} more.
                @if($title === 'Left')
                <a href="{{ route('employees.index', ['resigned_from' => $periodStart->format('Y-m'), 'resigned_to' => $today->format('Y-m')]) }}" class="text-primary-600 hover:underline dark:text-primary-400">See them all</a>
                @endif
            </div>
            @endif
        </div>
        @endforeach
    </div>

    @if($caveats['noJoinDate'] || $caveats['noResignedDate'])
    <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-600 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400">
        <i class="bi bi-info-circle me-1"></i>
        @if($caveats['noJoinDate'])
        {{ $caveats['noJoinDate'] }} current employee(s) have no join date: they count in every month's headcount but never as joiners.
        <a href="{{ route('employees.index', ['issue' => 'no_join_date']) }}" class="text-primary-600 hover:underline dark:text-primary-400">Fix them</a>.
        @endif
        @if($caveats['noResignedDate'])
        {{ $caveats['noResignedDate'] }} former employee(s) have no resignation date and are left out entirely.
        <a href="{{ route('employees.index', ['issue' => 'no_resigned_date']) }}" class="text-primary-600 hover:underline dark:text-primary-400">Fix them</a>.
        @endif
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    // Hover / focus readout for both charts. Values come from data-tip and go in with textContent.
    document.querySelectorAll('.hc-viz [data-chart]').forEach((chart) => {
        const tooltip = chart.querySelector('[data-tooltip]');
        const crosshair = chart.querySelector('[data-crosshair]');
        const svg = chart.querySelector('svg');

        const show = (hit) => {
            const data = JSON.parse(hit.dataset.tip);
            tooltip.replaceChildren();
            const title = document.createElement('div');
            title.className = 'mb-1 font-semibold text-slate-500 dark:text-slate-400';
            title.textContent = data.title;
            tooltip.appendChild(title);
            data.rows.forEach(([label, value, key]) => {
                const row = document.createElement('div');
                row.className = 'flex items-center gap-2';
                const keyMark = document.createElement('span');
                keyMark.className = 'inline-block h-0.5 w-3 rounded';
                keyMark.style.background = key ? 'var(--' + key + ')' : 'transparent';
                const v = document.createElement('span');
                v.className = 'font-semibold tabular-nums text-slate-900 dark:text-white';
                v.textContent = value;
                const l = document.createElement('span');
                l.className = 'text-slate-500 dark:text-slate-400';
                l.textContent = label;
                row.append(keyMark, v, l);
                tooltip.appendChild(row);
            });

            const box = chart.getBoundingClientRect();
            const rect = hit.getBoundingClientRect();
            const scale = box.width / svg.viewBox.baseVal.width;
            if (crosshair && hit.dataset.x) {
                crosshair.setAttribute('x1', hit.dataset.x);
                crosshair.setAttribute('x2', hit.dataset.x);
                crosshair.setAttribute('visibility', 'visible');
            }
            tooltip.classList.remove('hidden');
            const cx = rect.left - box.left + rect.width / 2;
            const left = Math.min(box.width - tooltip.offsetWidth, Math.max(0, cx + 12 > box.width - tooltip.offsetWidth ? cx - tooltip.offsetWidth - 12 : cx + 12));
            tooltip.style.left = left + 'px';
            tooltip.style.top = (8 * scale) + 'px';
        };
        const hide = () => {
            tooltip.classList.add('hidden');
            crosshair?.setAttribute('visibility', 'hidden');
        };

        chart.querySelectorAll('.hit').forEach((hit) => {
            hit.addEventListener('pointerenter', () => show(hit));
            hit.addEventListener('focus', () => show(hit));
            hit.addEventListener('pointerleave', hide);
            hit.addEventListener('blur', hide);
        });
    });
</script>
@endpush
