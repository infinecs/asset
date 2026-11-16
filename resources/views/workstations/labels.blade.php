<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Workstation Labels{{ $workstations->count() === 1 ? ' - ' . $workstations->first()->name : '' }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 24px;
            background: #f1f5f9;
            font-family: 'Segoe UI', Arial, sans-serif;
            color: #0f172a;
        }
        .toolbar {
            margin: 0 auto 20px;
            max-width: 1000px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
        }
        .toolbar .count { flex: 1; font-size: 14px; color: #475569; }
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
        .notice {
            margin: 0 auto 16px;
            max-width: 1000px;
            padding: 10px 14px;
            border-radius: 6px;
            background: #fef3c7;
            color: #92400e;
            font-size: 13px;
        }
        .sheet {
            margin: 0 auto;
            max-width: 1000px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 12px;
        }
        .label {
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            background: #fff;
            padding: 12px 14px;
            display: flex;
            align-items: center;
            gap: 14px;
            break-inside: avoid;
            page-break-inside: avoid;
        }
        .label .qr { width: 96px; height: 96px; flex-shrink: 0; }
        .label .qr svg { width: 100%; height: 100%; }
        .label .info { min-width: 0; }
        .label .code {
            font-family: 'Consolas', monospace;
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 0.5px;
            overflow-wrap: anywhere;
        }
        .label .meta { font-size: 12px; color: #475569; margin-top: 4px; }
        .label .hint { font-size: 10px; color: #94a3b8; margin-top: 6px; }

        @media print {
            body { background: #fff; padding: 0; }
            .toolbar, .notice { display: none; }
            .sheet { max-width: none; grid-template-columns: repeat(2, 1fr); gap: 8px; }
            .label { border: 1px solid #000; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <span class="count">{{ $workstations->count() }} label(s)</span>
        <button type="button" class="primary" onclick="window.print()">Print Labels</button>
        @if($workstations->count() === 1)
        <a href="{{ route('workstations.show', $workstations->first()) }}">Back to Workstation</a>
        @else
        <a href="{{ route('workstations.index', request()->query()) }}">Back to Workstations</a>
        @endif
    </div>

    @if($truncatedAt)
    <div class="notice">Only the first {{ $truncatedAt }} workstations are shown. Filter by location or room to print the rest.</div>
    @endif

    <div class="sheet">
        @foreach($workstations as $ws)
        <div class="label">
            <div class="qr">{!! $qrCodes[$ws->id] !!}</div>
            <div class="info">
                <div class="code">{{ $ws->name }}</div>
                <div class="meta">
                    {{ $ws->location?->name }}@if($ws->room) · Room {{ $ws->room }}@endif · Desk {{ str_pad((string) $ws->number, 2, '0', STR_PAD_LEFT) }}
                </div>
                <div class="hint">Scan to see or audit this desk's equipment</div>
            </div>
        </div>
        @endforeach
    </div>
</body>
</html>
