{{--
    "Scan desk label" for a move form (workstations._move-form): reads a desk's QR label, or the
    code typed in from the label, asks to confirm, then submits the form.

    Put a button with data-ws-scan inside the move form, or data-ws-scan="<form id>" anywhere else.
    The form needs a workstation picker (workstations._select) and may carry data-asset-label and
    data-current-workstation (id of the desk the asset is at now).
--}}
@include('workstations._qr-scanner')
@once
@push('scripts')
<div class="hidden">
    <div id="scan-desk-panel">
        {{-- Step 1: point at a label, or type its code --}}
        <form data-desk-manual class="space-y-2">
            <label class="field-label" for="scan-desk-code">Can't scan? Type the code on the label</label>
            <div class="flex gap-2">
                <input id="scan-desk-code" type="text" class="field-input uppercase" placeholder="e.g. INFP1ARAWS03" autocomplete="off" autocapitalize="characters" spellcheck="false">
                <button class="btn btn-outline shrink-0 px-4">Find</button>
            </div>
        </form>

        {{-- Step 2: confirm the desk --}}
        <div data-desk-found class="hidden space-y-3">
            <div class="flex items-start gap-3">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-green-100 text-xl text-green-700 dark:bg-green-900/40 dark:text-green-300">
                    <i class="bi bi-check-lg"></i>
                </div>
                <div class="min-w-0">
                    <div data-found-code class="break-all text-lg font-semibold"></div>
                    <div data-found-where class="text-sm text-slate-500 dark:text-slate-400"></div>
                    <div data-found-desk class="text-sm text-slate-500 dark:text-slate-400"></div>
                </div>
            </div>
            <input type="text" data-found-notes class="field-input" maxlength="500" placeholder="Note (optional)">
            <button type="button" data-found-confirm class="btn btn-primary w-full py-3 text-base"><i class="bi bi-arrows-move"></i><span>Move here</span></button>
            <button type="button" data-found-again class="btn btn-outline w-full py-3 text-base"><i class="bi bi-qr-code-scan"></i>Scan another</button>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const scanner = window.qrScanner;
        const panel = document.getElementById('scan-desk-panel');
        const $ = function (sel) { return panel.querySelector(sel); };
        const manualForm = $('[data-desk-manual]');
        const codeInput = $('#scan-desk-code');
        const foundBox = $('[data-desk-found]');
        const confirmBtn = $('[data-found-confirm]');
        const notesInput = $('[data-found-notes]');

        let form = null;
        let picker = null;
        let found = null;

        // Desk labels hold a link to the desk (…/workstations/12); a typed code works too.
        const resolve = function (text) {
            if (scanner.isLink(text)) {
                const id = scanner.linkId(text, 'workstations');
                if (id) {
                    return { id: id, found: picker.findById(id) };
                }
                return { error: scanner.linkId(text, 'assets')
                    ? 'That\'s an asset label. Scan the label on the desk.'
                    : 'That QR code isn\'t a desk label.' };
            }
            const byCode = picker.findByCode(text);
            return byCode
                ? { id: byCode.desk.id, found: byCode }
                : { error: 'No desk with code "' + String(text).trim().toUpperCase() + '".' };
        };

        const handle = function (text, fromCamera) {
            const result = resolve(text);
            if (result.id && result.id === Number(form.dataset.currentWorkstation || 0)) {
                result.error = 'It\'s already at this desk.';
            } else if (result.id && !result.found) {
                result.error = 'That desk isn\'t in the list (it may have been deleted).';
            }
            if (result.error) {
                scanner.setError(result.error);
                return;
            }

            found = result.found;
            scanner.pause(found.desk.code);
            scanner.setError('');
            if (fromCamera) {
                scanner.buzz();
            }
            $('[data-found-code]').textContent = found.desk.code;
            $('[data-found-where]').textContent = found.location.label + (found.room.room ? ' · Room ' + found.room.room : '');
            $('[data-found-desk]').textContent = found.desk.text;
            manualForm.classList.add('hidden');
            foundBox.classList.remove('hidden');
        };

        const showScanStep = function () {
            found = null;
            codeInput.value = '';
            notesInput.value = '';
            foundBox.classList.add('hidden');
            manualForm.classList.remove('hidden');
        };

        document.addEventListener('click', function (e) {
            const btn = e.target.closest('[data-ws-scan]');
            if (!btn) {
                return;
            }
            e.preventDefault();
            const target = btn.dataset.wsScan ? document.getElementById(btn.dataset.wsScan) : btn.closest('form');
            const pickerEl = target && target.querySelector('[data-ws-picker]');
            if (!pickerEl || !pickerEl.wsPicker) {
                return;
            }
            form = target;
            picker = pickerEl.wsPicker;
            confirmBtn.disabled = false;
            confirmBtn.querySelector('span').textContent = 'Move here';
            showScanStep();
            scanner.open({
                title: 'Scan desk label',
                subtitle: form.dataset.assetLabel ? 'Moving ' + form.dataset.assetLabel : '',
                panel: panel,
                onCode: function (text) { handle(text, true); },
            });
        });

        manualForm.addEventListener('submit', function (e) {
            e.preventDefault();
            if (codeInput.value.trim()) {
                handle(codeInput.value, false);
            }
        });

        $('[data-found-again]').addEventListener('click', function () {
            showScanStep();
            scanner.resume();
        });

        confirmBtn.addEventListener('click', function () {
            if (!found) {
                return;
            }
            picker.select(found);
            const notes = form.querySelector('input[name="notes"]');
            if (notes) {
                notes.value = notesInput.value;
            }
            confirmBtn.disabled = true;
            confirmBtn.querySelector('span').textContent = 'Moving…';
            form.submit();
        });
    });
</script>
@endpush
@endonce
