<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Label - {{ $asset->asset_tag }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 24px;
            background: #f1f5f9;
            font-family: 'Segoe UI', Arial, sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .toolbar {
            margin-bottom: 20px;
            display: flex;
            gap: 8px;
        }
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
        .toolbar .primary {
            background: #2563eb;
            border-color: #2563eb;
            color: #fff;
        }
        .label {
            width: 320px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            background: #fff;
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .label .qr {
            width: 110px;
            height: 110px;
            flex-shrink: 0;
        }
        .label .qr svg {
            width: 100%;
            height: 100%;
        }
        .label .info {
            min-width: 0;
        }
        .label .info .name {
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
            overflow-wrap: break-word;
        }
        .label .info .tag {
            font-family: 'Consolas', monospace;
            font-size: 13px;
            color: #2563eb;
            font-weight: 600;
            margin-top: 4px;
        }
        .label .info .meta {
            font-size: 11px;
            color: #64748b;
            margin-top: 6px;
        }

        @media print {
            body { background: #fff; padding: 0; }
            .toolbar { display: none; }
            .label { border: 1px solid #000; box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" class="primary" onclick="window.print()">Print Label</button>
        <a href="{{ route('assets.show', $asset) }}">Back to Asset</a>
    </div>

    <div class="label">
        <div class="qr">{!! $qrCode !!}</div>
        <div class="info">
            <div class="name">{{ $asset->name }}</div>
            <div class="tag">{{ $asset->asset_tag }}</div>
            <div class="meta">
                {{ $asset->brand_label !== '-' ? $asset->brand_label . ' ' : '' }}{{ $asset->model }}
                @if($asset->serial_number)
                    <br>S/N: {{ $asset->serial_number }}
                @endif
            </div>
        </div>
    </div>
</body>
</html>
