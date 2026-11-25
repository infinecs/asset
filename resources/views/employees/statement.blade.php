@php
    $clearance = $type === 'clearance';
    $title = $clearance ? 'Offboarding Clearance' : 'Equipment & Access Statement';
    $desks = $employee->workstations;
    $deskAssets = $desks->flatMap->assets;
    $team = $employee->team?->name ?? $employee->managedTeams->pluck('name')->join(', ');
    $total = $employee->assets->count() + $desks->count() + $employee->digitalProducts->count();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} - {{ $employee->name }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 24px; background: #f1f5f9; font-family: 'Segoe UI', Arial, sans-serif; color: #0f172a; }
        .toolbar { margin: 0 auto 16px; max-width: 860px; display: flex; flex-wrap: wrap; align-items: center; gap: 8px 16px; font-size: 14px; color: #334155; }
        .toolbar .spacer { flex: 1; }
        .toolbar button, .toolbar a { font-family: inherit; font-size: 14px; padding: 8px 16px; border-radius: 6px; border: 1px solid #cbd5e1; background: #fff; color: #334155; cursor: pointer; text-decoration: none; }
        .toolbar .primary { background: #2563eb; border-color: #2563eb; color: #fff; }
        .toolbar .current { background: #e2e8f0; font-weight: 600; }
        .toolbar .tip { width: 100%; font-size: 12px; color: #64748b; }

        .sheet { margin: 0 auto; max-width: 860px; background: #fff; padding: 32px 36px; border-radius: 8px; box-shadow: 0 1px 3px rgba(15, 23, 42, .1); }
        header { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; border-bottom: 2px solid #0f172a; padding-bottom: 12px; margin-bottom: 16px; }
        h1 { margin: 0 0 2px; font-size: 22px; }
        .muted { color: #64748b; }
        .small { font-size: 12px; }
        .details { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px 20px; margin-bottom: 20px; font-size: 13px; }
        .details dt { color: #64748b; font-size: 11px; text-transform: uppercase; letter-spacing: .04em; }
        .details dd { margin: 1px 0 0; font-weight: 600; }
        h2 { font-size: 14px; margin: 20px 0 6px; display: flex; justify-content: space-between; align-items: baseline; }
        h2 span { font-weight: 400; font-size: 12px; color: #64748b; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { text-align: left; padding: 6px 8px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        th { background: #f8fafc; font-weight: 600; color: #334155; font-size: 11px; }
        td.code { font-family: 'Consolas', monospace; font-weight: 700; white-space: nowrap; }
        td.box { width: 70px; text-align: center; }
        .tick { display: inline-block; width: 14px; height: 14px; border: 1.5px solid #334155; border-radius: 2px; }
        .empty { padding: 10px 8px; font-size: 12px; color: #94a3b8; border-bottom: 1px solid #e2e8f0; }
        .confirm { margin-top: 24px; font-size: 13px; line-height: 1.5; padding: 12px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; }
        .signatures { display: grid; grid-template-columns: 1fr 1fr; gap: 32px; margin-top: 36px; font-size: 12px; }
        .signatures .line { border-bottom: 1px solid #0f172a; height: 40px; }
        .signatures .role { font-weight: 600; margin-top: 6px; }
        .signatures .fields { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 16px; }
        .signatures .fields div { border-bottom: 1px solid #94a3b8; padding-bottom: 14px; color: #64748b; font-size: 11px; }
        footer { margin-top: 28px; font-size: 10px; color: #94a3b8; display: flex; justify-content: space-between; }

        @page { size: A4 portrait; margin: 14mm; }
        @media print {
            body { background: #fff; padding: 0; }
            .toolbar { display: none; }
            .sheet { max-width: none; padding: 0; box-shadow: none; border-radius: 0; }
            tr, .signatures, .confirm { break-inside: avoid; page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <a href="{{ route('employees.statement', [$employee, 'type' => 'holdings']) }}" class="{{ $clearance ? '' : 'current' }}">Statement</a>
        <a href="{{ route('employees.statement', [$employee, 'type' => 'clearance']) }}" class="{{ $clearance ? 'current' : '' }}">Clearance form</a>
        <span class="spacer"></span>
        <button type="button" class="primary" onclick="window.print()">Print / Save as PDF</button>
        <a href="{{ route('employees.show', $employee) }}">Back to {{ $employee->name }}</a>
        <span class="tip">To get a PDF, choose "Save as PDF" as the printer.</span>
    </div>

    <div class="sheet">
        <header>
            <div>
                <h1>{{ $title }}</h1>
                <div class="muted small">
                    {{ $clearance ? 'Everything below must be returned or handed over before the last day.' : 'Everything the company has issued to this employee, as recorded on ' . now()->format('d M Y') . '.' }}
                </div>
            </div>
            <div class="small muted" style="text-align:right">Generated {{ now()->format('d M Y, h:i A') }}<br>{{ $total }} item(s)</div>
        </header>

        <dl class="details">
            <div><dt>Name</dt><dd>{{ $employee->name }}</dd></div>
            <div><dt>ID number</dt><dd>{{ $employee->id_number }}</dd></div>
            <div><dt>Email</dt><dd>{{ $employee->email }}</dd></div>
            <div><dt>Role</dt><dd>{{ $employee->role?->name ?? '—' }}</dd></div>
            <div><dt>Team</dt><dd>{{ $team ?: '—' }}</dd></div>
            <div><dt>Manager</dt><dd>{{ $employee->manager?->name ?? '—' }}</dd></div>
            <div><dt>Joined</dt><dd>{{ $employee->join_date?->format('d M Y') ?? '—' }}</dd></div>
            <div><dt>Status</dt><dd>{{ $employee->status_label }}</dd></div>
            <div><dt>{{ $clearance ? 'Last day' : 'Work location' }}</dt><dd>{{ $clearance ? ($employee->resigned_date?->format('d M Y') ?? '—') : ($employee->work_location ?? '—') }}</dd></div>
        </dl>

        <h2>Assigned equipment <span>{{ $employee->assets->count() }} item(s)</span></h2>
        @if($employee->assets->isEmpty())
        <div class="empty">No equipment assigned.</div>
        @else
        <table>
            <thead>
                <tr><th>Asset tag</th><th>Item</th><th>Category</th><th>Serial number</th><th>Agreement</th>@if($clearance)<th>Condition / notes</th><th class="box">Returned</th>@endif</tr>
            </thead>
            <tbody>
                @foreach($employee->assets->sortBy('asset_tag') as $asset)
                <tr>
                    <td class="code">{{ $asset->asset_tag ?? '—' }}</td>
                    <td>{{ $asset->name }}</td>
                    <td>{{ $asset->category?->name ?? ucfirst((string) $asset->type) }}</td>
                    <td>{{ $asset->serial_number ?? $asset->service_tag ?? '—' }}</td>
                    <td>{{ $asset->agreement_signed_at ? 'Signed ' . $asset->agreement_signed_at->format('d M Y') : 'Not signed' }}</td>
                    @if($clearance)<td></td><td class="box"><span class="tick"></span></td>@endif
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <h2>Desk <span>{{ $desks->count() }} desk(s), {{ $deskAssets->count() }} item(s) of equipment on it</span></h2>
        @if($desks->isEmpty())
        <div class="empty">Not seated at a desk.</div>
        @else
        <table>
            <thead>
                <tr><th>Desk</th><th>Location</th><th>Equipment at the desk</th>@if($clearance)<th class="box">Cleared</th>@endif</tr>
            </thead>
            <tbody>
                @foreach($desks as $desk)
                <tr>
                    <td class="code">{{ $desk->name }}</td>
                    <td>{{ $desk->location?->name ?? '—' }}</td>
                    <td>{{ $desk->assets->map(fn ($a) => $a->asset_tag . ' (' . $a->name . ')')->join(', ') ?: 'None' }}</td>
                    @if($clearance)<td class="box"><span class="tick"></span></td>@endif
                </tr>
                @endforeach
            </tbody>
        </table>
        <p class="muted small">Desk equipment belongs to the desk and stays there; it's listed so it can be checked.</p>
        @endif

        <h2>Software licences <span>{{ $employee->digitalProducts->count() }} licence(s)</span></h2>
        @if($employee->digitalProducts->isEmpty())
        <div class="empty">No licences assigned.</div>
        @else
        <table>
            <thead>
                <tr><th>Product</th><th>Plan</th><th>Assigned on</th>@if($clearance)<th class="box">Revoked</th>@endif</tr>
            </thead>
            <tbody>
                @foreach($employee->digitalProducts->sortBy('name') as $product)
                <tr>
                    <td>{{ $product->name }}</td>
                    <td>{{ $product->plan ?? '—' }}</td>
                    <td>{{ $product->pivot->assigned_at ? \Illuminate\Support\Carbon::parse($product->pivot->assigned_at)->format('d M Y') : '—' }}</td>
                    @if($clearance)<td class="box"><span class="tick"></span></td>@endif
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <div class="confirm">
            @if($clearance)
            I confirm that I have returned all company equipment listed above, in the condition noted, and that I no longer use the software licences listed.
            The company confirms that the items ticked have been received and the licences revoked.
            @else
            I confirm that the equipment and licences listed above have been issued to me, that I will look after them,
            and that I will return them when asked or when I leave the company.
            @endif
        </div>

        <div class="signatures">
            <div>
                <div class="line"></div>
                <div class="role">Employee: {{ $employee->name }}</div>
                <div class="fields"><div>Date</div><div>{{ $clearance ? 'Personal email (for records)' : 'ID number: ' . $employee->id_number }}</div></div>
            </div>
            <div>
                <div class="line"></div>
                <div class="role">{{ $clearance ? 'Received by (IT / Admin)' : 'Issued by (IT / Admin)' }}</div>
                <div class="fields"><div>Name</div><div>Date</div></div>
            </div>
        </div>

        <footer>
            <span>{{ $employee->name }} · {{ $employee->id_number }}</span>
            <span>{{ $title }} · {{ now()->format('d M Y') }}</span>
        </footer>
    </div>
</body>
</html>
