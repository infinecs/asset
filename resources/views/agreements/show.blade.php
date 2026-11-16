<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipment Agreement - {{ $asset->asset_tag }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 24px 16px;
            background: #f1f5f9;
            font-family: 'Segoe UI', Arial, sans-serif;
            color: #334155;
            display: flex;
            justify-content: center;
        }
        .sheet {
            width: 100%;
            max-width: 640px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(15,23,42,0.08);
            overflow: hidden;
        }
        .header {
            padding: 24px 32px;
            border-bottom: 3px solid #4f46e5;
            text-align: center;
        }
        .header img { height: 32px; }
        .body { padding: 28px 32px; }
        h1 {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 4px;
        }
        .sub {
            font-size: 13px;
            color: #64748b;
            margin: 0 0 20px;
        }
        .details {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 16px 20px;
            margin-bottom: 20px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px 16px;
        }
        .details div label {
            display: block;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 2px;
        }
        .details div span {
            font-size: 14px;
            font-weight: 600;
            color: #1e293b;
        }
        .terms {
            font-size: 13px;
            line-height: 1.7;
            color: #475569;
            max-height: 220px;
            overflow-y: auto;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 16px 18px;
            margin-bottom: 20px;
        }
        .terms ol { margin: 0; padding-left: 18px; }
        .terms li { margin-bottom: 8px; }
        .field { margin-bottom: 18px; }
        .field label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 6px;
        }
        .field input[type=text] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
        }
        .sig-wrap {
            border: 1px dashed #cbd5e1;
            border-radius: 10px;
            background: #f8fafc;
            position: relative;
        }
        .sig-wrap canvas {
            width: 100%;
            height: 160px;
            display: block;
            touch-action: none;
            cursor: crosshair;
        }
        .sig-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 6px;
        }
        .sig-actions button {
            font-family: inherit;
            font-size: 12px;
            color: #64748b;
            background: none;
            border: none;
            cursor: pointer;
            text-decoration: underline;
        }
        .sig-tabs {
            display: flex;
            gap: 4px;
            padding: 4px;
            background: #f1f5f9;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        .sig-tabs button {
            flex: 1;
            font-family: inherit;
            font-size: 13px;
            font-weight: 600;
            color: #64748b;
            background: none;
            border: none;
            border-radius: 6px;
            padding: 8px 10px;
            cursor: pointer;
        }
        .sig-tabs button.active {
            background: #fff;
            color: #4f46e5;
            box-shadow: 0 1px 2px rgba(15,23,42,0.08);
        }
        .sig-upload {
            border: 1px dashed #cbd5e1;
            border-radius: 10px;
            background: #f8fafc;
            min-height: 160px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 16px;
            text-align: center;
            cursor: pointer;
            font-size: 13px;
            color: #64748b;
        }
        .sig-upload:hover { border-color: #4f46e5; }
        .sig-upload strong { color: #4f46e5; }
        .sig-upload .hint { font-size: 11px; color: #94a3b8; }
        .sig-upload img {
            max-width: 100%;
            max-height: 140px;
            background: #fff;
            border-radius: 6px;
        }
        .sig-upload input[type=file] { display: none; }
        [hidden] { display: none !important; }
        .checkbox-row {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            font-size: 13px;
            color: #475569;
            margin-bottom: 20px;
        }
        .checkbox-row input { margin-top: 3px; }
        .submit-btn {
            width: 100%;
            padding: 12px;
            background: #4f46e5;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
        }
        .submit-btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .errors {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 13px;
            margin-bottom: 18px;
        }
        .flash-success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #15803d;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 13px;
            margin-bottom: 18px;
        }
        .signed-state {
            text-align: center;
            padding: 12px 0 4px;
        }
        .signed-state i {
            font-size: 40px;
            color: #16a34a;
        }
        .signed-state .sig-img {
            max-width: 260px;
            max-height: 100px;
            margin: 16px auto;
            display: block;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #fff;
            padding: 8px;
        }
        .footer-note {
            text-align: center;
            font-size: 11px;
            color: #94a3b8;
            padding: 16px 32px 24px;
        }
        .toolbar {
            display: flex;
            justify-content: center;
            margin-top: 8px;
        }
        .btn-secondary {
            font-family: inherit;
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 10px 20px;
            cursor: pointer;
        }
        .btn-secondary:hover { background: #e2e8f0; }

        .preview-banner {
            background: #fffbeb;
            border-bottom: 1px solid #fde68a;
            color: #92400e;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
            padding: 8px 16px;
        }

        @media print {
            body { background: #fff; padding: 0; display: block; }
            .sheet { max-width: 100%; border-radius: 0; box-shadow: none; }
            .terms { max-height: none; overflow: visible; border: none; padding: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    @php $preview = $preview ?? false; @endphp
    <div class="sheet">
        @if($preview)
        <div class="preview-banner no-print">Preview mode: this is what the employee sees. Signing is disabled and nothing is saved.</div>
        @endif
        <div class="header">
            <img src="{{ asset('images/Infinecs-with-slogan-small.png') }}" alt="Infinecs">
        </div>
        <div class="body">
            @if($asset->agreement_signed_at)
                <div class="signed-state">
                    <i class="bi">✔</i>
                    <h1 style="margin-top:12px;">Agreement Signed</h1>
                    <p class="sub">
                        Signed by <strong>{{ $asset->agreement_signed_name }}</strong>
                        on {{ $asset->agreement_signed_at->format('d M Y, h:i A') }}
                    </p>
                    @if($asset->agreement_signature_path)
                    <img class="sig-img" src="{{ asset('storage/' . $asset->agreement_signature_path) }}" alt="Signature">
                    @endif
                </div>

                <div class="details" style="margin-top:8px;">
                    <div><label>Asset Tag</label><span>{{ $asset->asset_tag }}</span></div>
                    <div><label>Item</label><span>{{ $asset->name }}</span></div>
                    <div><label>Brand / Model</label><span>{{ trim(($asset->brand_label !== '-' ? $asset->brand_label : '') . ' ' . $asset->model) ?: '-' }}</span></div>
                    <div><label>Serial Number</label><span>{{ $asset->serial_number ?? '-' }}</span></div>
                    <div><label>Assigned To</label><span>{{ $asset->assignedEmployee?->name ?? '-' }}</span></div>
                    <div><label>Signed</label><span>{{ $asset->agreement_signed_at->format('d M Y, h:i A') }}</span></div>
                </div>

                @include('agreements._terms')

                <div class="toolbar no-print">
                    <button type="button" class="btn-secondary" onclick="window.print()">
                        🖨 Print / Save as PDF
                    </button>
                </div>
            @else
                @if($errors->any())
                <div class="errors">{{ $errors->first() }}</div>
                @endif

                <h1>Equipment Agreement</h1>
                <p class="sub">Please review the details below and sign to confirm receipt of this equipment.</p>

                <div class="details">
                    <div><label>Asset Tag</label><span>{{ $asset->asset_tag }}</span></div>
                    <div><label>Item</label><span>{{ $asset->name }}</span></div>
                    <div><label>Brand / Model</label><span>{{ trim(($asset->brand_label !== '-' ? $asset->brand_label : '') . ' ' . $asset->model) ?: '-' }}</span></div>
                    <div><label>Serial Number</label><span>{{ $asset->serial_number ?? '-' }}</span></div>
                    <div><label>Assigned To</label><span>{{ $asset->assignedEmployee?->name ?? '-' }}</span></div>
                    <div><label>Date</label><span>{{ now()->format('d M Y') }}</span></div>
                </div>

                @include('agreements._terms')

                <form method="POST" action="{{ $preview ? '#' : route('agreements.store', $asset->agreement_token) }}" id="agreement-form" class="no-print" enctype="multipart/form-data" data-preview="{{ $preview ? '1' : '0' }}">
                    @csrf

                    <div class="field">
                        <label for="signer_name">Full Name</label>
                        <input type="text" id="signer_name" name="signer_name" value="{{ old('signer_name', $asset->assignedEmployee?->name) }}" required>
                    </div>

                    <div class="field">
                        <label>Signature</label>
                        @php $sigMethod = old('signature_method') === 'upload' ? 'upload' : 'draw'; @endphp
                        <input type="hidden" name="signature_method" id="signature-method" value="{{ $sigMethod }}">

                        <div class="sig-tabs" role="tablist">
                            <button type="button" data-method="draw" class="{{ $sigMethod === 'draw' ? 'active' : '' }}">Draw signature</button>
                            <button type="button" data-method="upload" class="{{ $sigMethod === 'upload' ? 'active' : '' }}">Upload photo</button>
                        </div>

                        <div id="sig-draw-panel" @if($sigMethod !== 'draw') hidden @endif>
                            <div class="sig-wrap">
                                <canvas id="sig-pad"></canvas>
                            </div>
                            <div class="sig-actions">
                                <button type="button" id="sig-clear">Clear</button>
                            </div>
                            <input type="hidden" name="signature" id="signature-input">
                        </div>

                        <div id="sig-upload-panel" @if($sigMethod !== 'upload') hidden @endif>
                            <label class="sig-upload" for="signature-file">
                                <div id="sig-upload-empty">
                                    <div><strong>Choose a photo</strong> of your signature</div>
                                    <div class="hint">Sign on white paper and take a clear photo. JPG, PNG or WEBP, up to 5 MB.</div>
                                </div>
                                <img id="sig-upload-preview" alt="Signature preview" hidden>
                                <input type="file" name="signature_file" id="signature-file" accept="image/jpeg,image/png,image/webp">
                            </label>
                            <div class="sig-actions">
                                <button type="button" id="sig-upload-clear" hidden>Remove photo</button>
                            </div>
                        </div>
                    </div>

                    <label class="checkbox-row">
                        <input type="checkbox" name="agree" value="1" required>
                        <span>I have read and agree to the terms stated above.</span>
                    </label>

                    <button type="submit" class="submit-btn" id="submit-btn">Sign Agreement</button>
                </form>
            @endif
        </div>
        <div class="footer-note">
            Infinecs Asset &amp; Employee Management System. This is a secure, one-time signing link.
        </div>
    </div>

    @unless($asset->agreement_signed_at)
    <script>
        (function () {
            const canvas = document.getElementById('sig-pad');
            const ctx = canvas.getContext('2d');
            const clearBtn = document.getElementById('sig-clear');
            const form = document.getElementById('agreement-form');
            const signatureInput = document.getElementById('signature-input');
            const methodInput = document.getElementById('signature-method');
            const drawPanel = document.getElementById('sig-draw-panel');
            const uploadPanel = document.getElementById('sig-upload-panel');
            const fileInput = document.getElementById('signature-file');
            const preview = document.getElementById('sig-upload-preview');
            const uploadEmpty = document.getElementById('sig-upload-empty');
            const uploadClear = document.getElementById('sig-upload-clear');
            const MAX_UPLOAD_BYTES = 5 * 1024 * 1024;
            let drawing = false;
            let hasDrawn = false;

            function setMethod(method) {
                methodInput.value = method;
                drawPanel.hidden = method !== 'draw';
                uploadPanel.hidden = method !== 'upload';
                document.querySelectorAll('.sig-tabs button').forEach(function (btn) {
                    btn.classList.toggle('active', btn.dataset.method === method);
                });
                if (method === 'draw' && !hasDrawn) {
                    resize();
                }
            }

            document.querySelectorAll('.sig-tabs button').forEach(function (btn) {
                btn.addEventListener('click', function () { setMethod(btn.dataset.method); });
            });

            function clearUpload() {
                fileInput.value = '';
                if (preview.src) URL.revokeObjectURL(preview.src);
                preview.removeAttribute('src');
                preview.hidden = true;
                uploadEmpty.hidden = false;
                uploadClear.hidden = true;
            }

            fileInput.addEventListener('change', function () {
                const file = fileInput.files[0];
                if (!file) { clearUpload(); return; }
                if (!/^image\/(jpeg|png|webp)$/.test(file.type)) {
                    alert('Please choose a JPG, PNG or WEBP image.');
                    clearUpload();
                    return;
                }
                if (file.size > MAX_UPLOAD_BYTES) {
                    alert('The photo is larger than 5 MB. Please choose a smaller image.');
                    clearUpload();
                    return;
                }
                if (preview.src) URL.revokeObjectURL(preview.src);
                preview.src = URL.createObjectURL(file);
                preview.hidden = false;
                uploadEmpty.hidden = true;
                uploadClear.hidden = false;
            });

            uploadClear.addEventListener('click', clearUpload);

            function resize() {
                const ratio = window.devicePixelRatio || 1;
                const rect = canvas.getBoundingClientRect();
                if (!rect.width) return;
                canvas.width = rect.width * ratio;
                canvas.height = rect.height * ratio;
                ctx.scale(ratio, ratio);
                ctx.lineWidth = 2;
                ctx.lineCap = 'round';
                ctx.strokeStyle = '#1e293b';
            }
            resize();

            function pos(e) {
                const rect = canvas.getBoundingClientRect();
                const point = e.touches ? e.touches[0] : e;
                return { x: point.clientX - rect.left, y: point.clientY - rect.top };
            }

            function start(e) {
                e.preventDefault();
                drawing = true;
                hasDrawn = true;
                const p = pos(e);
                ctx.beginPath();
                ctx.moveTo(p.x, p.y);
            }

            function move(e) {
                if (!drawing) return;
                e.preventDefault();
                const p = pos(e);
                ctx.lineTo(p.x, p.y);
                ctx.stroke();
            }

            function end() {
                drawing = false;
            }

            canvas.addEventListener('mousedown', start);
            canvas.addEventListener('mousemove', move);
            window.addEventListener('mouseup', end);
            canvas.addEventListener('touchstart', start, { passive: false });
            canvas.addEventListener('touchmove', move, { passive: false });
            canvas.addEventListener('touchend', end);

            clearBtn.addEventListener('click', function () {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                hasDrawn = false;
            });

            form.addEventListener('submit', function (e) {
                if (methodInput.value === 'upload') {
                    if (!fileInput.files.length) {
                        e.preventDefault();
                        alert('Please upload a photo of your signature before submitting.');
                        return;
                    }
                    // Don't send a drawn signature alongside the photo.
                    signatureInput.value = '';
                    return;
                }

                if (!hasDrawn) {
                    e.preventDefault();
                    alert('Please draw your signature before submitting.');
                    return;
                }
                // Don't upload a photo that was picked and then abandoned for drawing.
                fileInput.value = '';
                signatureInput.value = canvas.toDataURL('image/png');
            });

            if (form.dataset.preview === '1') {
                // Registered after the validation handler, so it only runs once the signature checks pass.
                form.addEventListener('submit', function (e) {
                    const blocked = e.defaultPrevented;
                    e.preventDefault();
                    if (!blocked) {
                        alert('Preview mode: the signature checks passed. On the real page the agreement would now be signed.');
                    }
                });
            }
        })();
    </script>
    @endunless
</body>
</html>
