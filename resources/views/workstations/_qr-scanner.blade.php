{{--
    Full-screen phone camera QR scanner, shared by the desk and asset scanning flows
    (workstations._scan-desk, workstations._scan-assets). Rendered once per page.

    window.qrScanner.open({ title, subtitle, panel, onCode(text), onClose })
        panel   element shown in the bottom sheet while open (moved back out on close)
        onCode  called with each QR read; the same code held in view is reported once every 2.5s
    Also: close(), pause(message) (camera off, message shown), resume(), setError(text),
          toast(text) (brief note over the camera), buzz() (short vibration where supported).
--}}
@once
@push('scripts')
<div id="qr-scanner" class="fixed inset-0 z-[60] hidden flex-col bg-black text-white" role="dialog" aria-modal="true">
    <div class="flex items-center justify-between gap-3 px-4 pb-3 pt-[max(0.75rem,env(safe-area-inset-top))]">
        <div class="min-w-0">
            <div data-qr-title class="text-base font-semibold"></div>
            <div data-qr-subtitle class="truncate text-xs text-white/60"></div>
        </div>
        <button type="button" data-qr-close class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-white/10 text-xl hover:bg-white/20" aria-label="Close">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <div class="relative min-h-0 flex-1 overflow-hidden">
        <video data-qr-video class="absolute inset-0 h-full w-full object-cover" playsinline muted></video>
        <div data-qr-frame class="pointer-events-none absolute inset-0 flex items-center justify-center">
            <div class="aspect-square w-3/5 max-w-[16rem] rounded-2xl border-4 border-white/80 shadow-[0_0_0_9999px_rgba(0,0,0,0.45)]"></div>
        </div>
        <div data-qr-message class="absolute inset-0 hidden items-center justify-center p-6 text-center text-sm text-white/80"></div>
        <div data-qr-toast class="pointer-events-none absolute inset-x-0 top-3 hidden justify-center px-4">
            <span class="rounded-full bg-green-600 px-4 py-1.5 text-sm font-semibold shadow-lg"></span>
        </div>
    </div>

    <div class="max-h-[60vh] overflow-y-auto rounded-t-2xl bg-white px-4 pb-[max(1rem,env(safe-area-inset-bottom))] pt-4 text-slate-900 dark:bg-slate-900 dark:text-slate-100">
        <p data-qr-error class="mb-3 hidden rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700 dark:bg-red-950 dark:text-red-300"></p>
        <div data-qr-panel></div>
    </div>
</div>

<script>
    window.qrScanner = (function () {
        const overlay = document.getElementById('qr-scanner');
        const $ = function (sel) { return overlay.querySelector(sel); };
        const video = $('[data-qr-video]');
        const frame = $('[data-qr-frame]');
        const message = $('[data-qr-message]');
        const errorBox = $('[data-qr-error]');
        const panelSlot = $('[data-qr-panel]');
        const toastBox = $('[data-qr-toast]');

        let options = null;
        let panelHome = null;
        let stream = null;
        let timer = null;
        let toastTimer = null;
        let detect = null;
        let paused = false;
        let last = { text: null, at: 0 };

        const isOpen = function () { return !overlay.classList.contains('hidden'); };

        const setMessage = function (text) {
            message.textContent = text || '';
            message.classList.toggle('hidden', !text);
            message.classList.toggle('flex', !!text);
            frame.classList.toggle('hidden', !!text);
        };

        const setError = function (text) {
            errorBox.textContent = text || '';
            errorBox.classList.toggle('hidden', !text);
        };

        const stopCamera = function () {
            clearTimeout(timer);
            timer = null;
            if (stream) {
                stream.getTracks().forEach(function (t) { t.stop(); });
                stream = null;
            }
            video.srcObject = null;
        };

        // QR decoder: the browser's own where it has one (Android Chrome), otherwise jsQR (iPhone).
        const loadDecoder = function () {
            if (detect) {
                return Promise.resolve();
            }
            if ('BarcodeDetector' in window) {
                try {
                    const detector = new BarcodeDetector({ formats: ['qr_code'] });
                    detect = function () {
                        return detector.detect(video).then(function (codes) { return codes.length ? codes[0].rawValue : null; });
                    };
                    return Promise.resolve();
                } catch (e) { /* fall through to jsQR */ }
            }
            return new Promise(function (resolve, reject) {
                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js';
                script.onload = function () {
                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d', { willReadFrequently: true });
                    detect = function () {
                        const scale = Math.min(1, 640 / (video.videoWidth || 640));
                        canvas.width = Math.round(video.videoWidth * scale);
                        canvas.height = Math.round(video.videoHeight * scale);
                        if (!canvas.width || !canvas.height) {
                            return Promise.resolve(null);
                        }
                        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                        const img = ctx.getImageData(0, 0, canvas.width, canvas.height);
                        const code = jsQR(img.data, img.width, img.height, { inversionAttempts: 'dontInvert' });
                        return Promise.resolve(code ? code.data : null);
                    };
                    resolve();
                };
                script.onerror = function () { reject(new Error('decoder')); };
                document.head.appendChild(script);
            });
        };

        const scanLoop = function () {
            if (!stream) {
                return;
            }
            detect().then(function (text) {
                const now = Date.now();
                if (text && (text !== last.text || now - last.at > 2500)) {
                    last = { text: text, at: now };
                    options.onCode(text);
                } else if (text) {
                    last.at = now;
                }
            }).catch(function () { /* frame not ready */ }).then(function () {
                if (stream) {
                    timer = setTimeout(scanLoop, 200);
                }
            });
        };

        const startCamera = function () {
            if (stream) {
                return;
            }
            if (!window.isSecureContext || !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                setMessage('The camera only works when this site is opened over https. Type the code printed on the label below instead.');
                return;
            }
            setMessage('Starting camera…');
            navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } }, audio: false })
                .then(function (s) {
                    if (!isOpen() || paused) {
                        s.getTracks().forEach(function (t) { t.stop(); });
                        return;
                    }
                    stream = s;
                    video.srcObject = s;
                    return video.play().then(loadDecoder).then(function () {
                        setMessage('');
                        scanLoop();
                    });
                })
                .catch(function (e) {
                    stopCamera();
                    setMessage(e && e.name === 'NotAllowedError'
                        ? 'Camera access was blocked. Allow it in your browser settings, or type the code below.'
                        : 'Couldn\'t start the camera. Type the code printed on the label below instead.');
                });
        };

        const api = {
            open: function (opts) {
                if (isOpen()) {
                    api.close();
                }
                options = opts;
                paused = false;
                last = { text: null, at: 0 };
                $('[data-qr-title]').textContent = opts.title || 'Scan label';
                $('[data-qr-subtitle]').textContent = opts.subtitle || '';
                overlay.setAttribute('aria-label', opts.title || 'Scan label');
                panelHome = opts.panel.parentNode;
                panelSlot.appendChild(opts.panel);
                setError('');
                overlay.classList.remove('hidden');
                overlay.classList.add('flex');
                document.body.style.overflow = 'hidden';
                startCamera();
            },
            close: function () {
                if (!isOpen()) {
                    return;
                }
                stopCamera();
                overlay.classList.add('hidden');
                overlay.classList.remove('flex');
                document.body.style.overflow = '';
                if (panelHome) {
                    panelHome.appendChild(options.panel);
                }
                const onClose = options.onClose;
                options = null;
                if (onClose) {
                    onClose();
                }
            },
            pause: function (text) {
                paused = true;
                stopCamera();
                setMessage(text || '');
            },
            resume: function () {
                paused = false;
                last = { text: null, at: 0 };
                setError('');
                startCamera();
            },
            setError: setError,
            toast: function (text) {
                toastBox.querySelector('span').textContent = text;
                toastBox.classList.remove('hidden');
                toastBox.classList.add('flex');
                clearTimeout(toastTimer);
                toastTimer = setTimeout(function () {
                    toastBox.classList.add('hidden');
                    toastBox.classList.remove('flex');
                }, 1500);
            },
            buzz: function () {
                if (navigator.vibrate) {
                    navigator.vibrate(60);
                }
            },
            // The id in a label link such as https://…/assets/12 for kind "assets", else null.
            linkId: function (text, kind) {
                try {
                    const match = new URL(String(text).trim()).pathname.match(new RegExp('/' + kind + '/(\\d+)(?:/|$)'));
                    return match ? Number(match[1]) : null;
                } catch (e) {
                    return null;
                }
            },
            isLink: function (text) {
                try {
                    new URL(String(text).trim());
                    return true;
                } catch (e) {
                    return false;
                }
            },
        };

        $('[data-qr-close]').addEventListener('click', api.close);
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && isOpen()) {
                api.close();
            }
        });

        // Don't keep the camera on in a background tab.
        document.addEventListener('visibilitychange', function () {
            if (document.hidden) {
                stopCamera();
            } else if (isOpen() && !paused) {
                startCamera();
            }
        });

        return api;
    })();
</script>
@endpush
@endonce
