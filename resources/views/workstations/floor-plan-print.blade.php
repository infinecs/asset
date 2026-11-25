@php
    use App\Models\Workstation;

    $interval = Workstation::AUDIT_INTERVAL_DAYS;
    $soon = Workstation::AUDIT_DUE_SOON_DAYS;
    // Same buckets as the floor plan's "Audit" colouring.
    $auditState = fn (array $d) => $d['audit_age'] === null ? 'never' : ($d['audit_due'] ? 'overdue' : ($d['audit_age'] >= $interval - $soon ? 'soon' : 'ok'));
    $placed = $desks->filter(fn ($d) => $d['x'] !== null && $d['y'] !== null);
    $unplaced = $desks->reject(fn ($d) => $d['x'] !== null && $d['y'] !== null);
    $attention = $desks->filter(fn ($d) => count($d['warnings']) > 0)->count();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Floor Plan - {{ $location->name }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 24px;
            background: #f1f5f9;
            font-family: 'Segoe UI', Arial, sans-serif;
            color: #0f172a;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .toolbar {
            margin: 0 auto 16px;
            max-width: 1200px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px 16px;
            font-size: 14px;
            color: #334155;
        }
        .toolbar label { display: inline-flex; align-items: center; gap: 6px; cursor: pointer; }
        .toolbar select { font: inherit; padding: 6px 8px; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff; }
        .toolbar .spacer { flex: 1; }
        .toolbar button, .toolbar a {
            font-family: inherit;
            font-size: 14px;
            padding: 8px 16px;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #334155;
            cursor: pointer;
            text-decoration: none;
        }
        .toolbar .primary { background: #2563eb; border-color: #2563eb; color: #fff; }
        .toolbar .tip { width: 100%; font-size: 12px; color: #64748b; }

        .page { margin: 0 auto; max-width: 1200px; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(15, 23, 42, .1); }
        header { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 8px 24px; margin-bottom: 10px; }
        h1 { margin: 0; font-size: 20px; }
        .meta { font-size: 12px; color: #475569; }
        .legend { display: flex; flex-wrap: wrap; gap: 4px 14px; font-size: 11px; color: #334155; margin-bottom: 10px; }
        .legend span { display: inline-flex; align-items: center; gap: 5px; }
        .swatch { display: inline-block; width: 18px; height: 11px; border-radius: 3px; border: 1px solid; }
        .dot { display: inline-block; width: 9px; height: 9px; border-radius: 50%; }

        /* The plan: desks are placed by percentage, so the box must be exactly the image's size. */
        .plan { position: relative; width: fit-content; max-width: 100%; margin: 0 auto; }
        .plan img { display: block; max-width: 100%; max-height: 75vh; }
        .d {
            position: absolute;
            transform: translate(-50%, -50%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1px 4px;
            border: 1px solid;
            border-radius: 4px;
            font-family: 'Consolas', monospace;
            font-size: 9px;
            font-weight: 700;
            line-height: 11px;
            white-space: nowrap;
            text-align: center;
        }
        .d.sized { overflow: hidden; }
        .d .name { font-family: 'Segoe UI', Arial, sans-serif; font-size: 8px; font-weight: 500; line-height: 10px; max-width: 100%; overflow: hidden; text-overflow: ellipsis; }
        .hide-names .d .name { display: none; }
        .d .flag {
            position: absolute; top: -4px; right: -4px;
            width: 9px; height: 9px; border-radius: 50%;
            background: #f97316; color: #fff; font: 700 7px/9px Arial, sans-serif;
        }

        /* Occupancy colours */
        .by-occupancy .occ-assigned { background: #2563eb; border-color: #1d4ed8; color: #fff; }
        .by-occupancy .occ-hotdesk { background: #fff; border-color: #3b82f6; color: #1d4ed8; }
        .by-occupancy .occ-empty { background: rgba(255, 255, 255, .92); border-color: #94a3b8; border-style: dashed; color: #64748b; }
        /* Audit colours */
        .by-audit .aud-ok { background: #059669; border-color: #047857; color: #fff; }
        .by-audit .aud-soon { background: #fcd34d; border-color: #f59e0b; color: #451a03; }
        .by-audit .aud-overdue { background: #dc2626; border-color: #b91c1c; color: #fff; }
        .by-audit .aud-never { background: rgba(255, 255, 255, .92); border-color: #ef4444; border-style: dashed; color: #dc2626; }
        .by-audit .legend-occupancy, .by-occupancy .legend-audit { display: none !important; }

        .list { margin-top: 20px; }
        .no-list .list { display: none; }
        .list h2 { font-size: 15px; margin: 0 0 8px; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        th, td { text-align: left; padding: 4px 6px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        th { background: #f8fafc; font-weight: 600; color: #334155; }
        td.code { font-family: 'Consolas', monospace; font-weight: 700; white-space: nowrap; }
        .warn { color: #c2410c; }
        .muted { color: #94a3b8; }

        @page { size: landscape; margin: 10mm; }
        @media print {
            body { background: #fff; padding: 0; }
            .toolbar { display: none; }
            .page { max-width: none; padding: 0; box-shadow: none; border-radius: 0; }
            .plan img { max-height: 165mm; }
            .list { break-before: page; page-break-before: always; margin-top: 0; }
            tr { break-inside: avoid; page-break-inside: avoid; }
        }
    </style>
</head>
<body class="by-{{ $colour }} {{ $names ? '' : 'hide-names' }} {{ $list ? '' : 'no-list' }}">
    <div class="toolbar">
        <label>Colour desks by
            <select onchange="document.body.classList.remove('by-occupancy', 'by-audit'); document.body.classList.add('by-' + this.value)">
                <option value="occupancy" @selected($colour === 'occupancy')>Occupancy</option>
                <option value="audit" @selected($colour === 'audit')>Audit status</option>
            </select>
        </label>
        <label><input type="checkbox" @checked($names) onchange="document.body.classList.toggle('hide-names', !this.checked)"> Show who sits where</label>
        <label><input type="checkbox" @checked($list) onchange="document.body.classList.toggle('no-list', !this.checked)"> Add a desk list page</label>
        <span class="spacer"></span>
        <button type="button" class="primary" onclick="window.print()">Print / Save as PDF</button>
        <a href="{{ route('workstations.floor-plan', ['location' => $location->id]) }}">Back to Floor Plan</a>
        <span class="tip">To get a PDF, choose "Save as PDF" as the printer. Landscape fits most plans best.</span>
    </div>

    <div class="page">
        <header>
            <div>
                <h1>{{ $location->name }}: Floor Plan</h1>
                <div class="meta">
                    {{ $placed->count() }} of {{ $desks->count() }} desk(s) on the plan ·
                    {{ $desks->where('status', 'assigned')->count() }} assigned,
                    {{ $desks->where('status', 'hotdesk')->count() }} hot desk(s),
                    {{ $desks->where('status', 'empty')->count() }} empty
                    @if($attention) · <span class="warn">{{ $attention }} need attention</span>@endif
                </div>
            </div>
            <div class="meta">Printed {{ now()->format('d M Y, h:i A') }}</div>
        </header>

        <div class="legend">
            <span class="legend-occupancy"><span class="swatch" style="background:#2563eb;border-color:#1d4ed8"></span>Assigned</span>
            <span class="legend-occupancy"><span class="swatch" style="background:#fff;border-color:#3b82f6"></span>Hot desk</span>
            <span class="legend-occupancy"><span class="swatch" style="background:#fff;border-color:#94a3b8;border-style:dashed"></span>Empty (no equipment)</span>
            <span class="legend-audit"><span class="swatch" style="background:#059669;border-color:#047857"></span>Audited in the last {{ $interval - $soon }} days</span>
            <span class="legend-audit"><span class="swatch" style="background:#fcd34d;border-color:#f59e0b"></span>Due within {{ $soon }} days</span>
            <span class="legend-audit"><span class="swatch" style="background:#dc2626;border-color:#b91c1c"></span>Overdue (over {{ $interval }} days)</span>
            <span class="legend-audit"><span class="swatch" style="background:#fff;border-color:#ef4444;border-style:dashed"></span>Never audited</span>
            <span><span class="dot" style="background:#f97316"></span>Needs attention</span>
        </div>

        <div class="plan">
            <img src="{{ asset('storage/' . $location->floor_plan_path) }}" alt="Floor plan of {{ $location->name }}">
            @foreach($placed as $d)
            @php
                $sized = $d['w'] !== null && $d['h'] !== null;
                $style = "left: {$d['x']}%; top: {$d['y']}%;";
                if ($sized) {
                    $style .= " width: {$d['w']}%; height: {$d['h']}%;";
                }
                if ($d['r']) {
                    $style .= " transform: translate(-50%, -50%) rotate({$d['r']}deg);";
                }
            @endphp
            <div class="d occ-{{ $d['status'] }} aud-{{ $auditState($d) }} {{ $sized ? 'sized' : '' }}" style="{{ $style }}" title="{{ $d['code'] }}">
                <span>{{ $d['label'] }}</span>
                @if($d['occupant'])<span class="name">{{ $d['occupant'] }}</span>@endif
                @if(count($d['warnings']))<span class="flag">!</span>@endif
            </div>
            @endforeach
        </div>

        <section class="list">
            <h2>{{ $location->name }}: Desks</h2>
            <table>
                <thead>
                    <tr><th>Desk</th><th>Occupant</th><th>Equipment</th><th>Last audit</th><th>Needs attention</th></tr>
                </thead>
                <tbody>
                    @foreach($desks as $d)
                    <tr>
                        <td class="code">{{ $d['code'] }}@if($d['x'] === null)<div class="muted" style="font-weight:400">not on the plan</div>@endif</td>
                        <td>{{ $d['occupant'] ?? 'Hot desk' }}</td>
                        <td>{{ collect($d['assets'])->pluck('tag')->join(', ') ?: '—' }}</td>
                        <td>{{ $d['last_audit'] ?? 'Never' }}</td>
                        <td class="warn">{{ implode(' ', $d['warnings']) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @if($unplaced->isNotEmpty())
            <p class="meta">{{ $unplaced->count() }} desk(s) are not on the plan yet.</p>
            @endif
        </section>
    </div>
</body>
</html>
