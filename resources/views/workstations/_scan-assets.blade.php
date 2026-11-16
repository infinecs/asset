{{--
    "Scan equipment" on a desk page: scan each asset's QR label (or type its tag) to build a list,
    then place them all at this desk in one go. Opened by any button with data-scan-assets.
    Needs $workstation (assets loaded) and $placeableAssets (from WorkstationController::show()).
--}}
@include('workstations._qr-scanner')
@php
    $scanPlaceable = $placeableAssets->map(fn ($a) => [
        'id' => $a->id,
        'tag' => (string) $a->asset_tag,
        'name' => $a->name,
        'icon' => $a->type_icon,
        'from' => $a->workstation?->name,
    ])->values();
    $scanHere = $workstation->assets->map(fn ($a) => ['id' => $a->id, 'tag' => (string) $a->asset_tag])->values();
@endphp
@push('scripts')
<div class="hidden">
    <form id="scan-assets-panel" method="POST" action="{{ route('workstations.place-asset', $workstation) }}" class="space-y-3">
        @csrf
        <p data-list-empty class="mb-0 text-sm text-slate-500 dark:text-slate-400">Point the camera at each asset's label. They'll be listed here, then you place them all at once.</p>
        <ul data-list class="divide-y divide-slate-200 rounded-lg border border-slate-200 dark:divide-slate-800 dark:border-slate-800"></ul>
        <div class="flex gap-2">
            <input data-tag-input type="text" class="field-input uppercase" placeholder="Or type an asset tag" autocomplete="off" autocapitalize="characters" spellcheck="false" aria-label="Asset tag">
            <button type="button" data-tag-add class="btn btn-outline shrink-0 px-4">Add</button>
        </div>
        <input type="text" name="notes" class="field-input" maxlength="500" placeholder="Note (optional)">
        <button data-submit class="btn btn-primary w-full py-3 text-base" disabled><i class="bi bi-box-arrow-in-down"></i><span>Place here</span></button>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const scanner = window.qrScanner;
        const placeable = @json($scanPlaceable);
        const here = @json($scanHere);
        const deskCode = @json($workstation->name);
        const maxItems = {{ \App\Http\Controllers\WorkstationController::MAX_PLACE_AT_ONCE }};

        const panel = document.getElementById('scan-assets-panel');
        const $ = function (sel) { return panel.querySelector(sel); };
        const list = $('[data-list]');
        const empty = $('[data-list-empty]');
        const tagInput = $('[data-tag-input]');
        const submit = $('[data-submit]');
        const chosen = new Map();

        const normalize = function (tag) { return String(tag).trim().toUpperCase().replace(/\s/g, ''); };
        const byId = function (items, id) { return items.find(function (a) { return a.id === id; }) || null; };
        const byTag = function (items, tag) { return items.find(function (a) { return normalize(a.tag) === tag; }) || null; };

        const refresh = function () {
            const count = chosen.size;
            empty.classList.toggle('hidden', count > 0);
            list.classList.toggle('hidden', count === 0);
            submit.disabled = count === 0;
            submit.querySelector('span').textContent = count > 1 ? 'Place ' + count + ' assets here' : 'Place here';
        };

        const remove = function (id) {
            const row = chosen.get(id);
            if (row) {
                row.remove();
                chosen.delete(id);
                refresh();
            }
        };

        const addRow = function (asset) {
            const row = document.createElement('li');
            row.className = 'flex items-center gap-3 px-3 py-2';
            row.innerHTML =
                '<i class="bi text-lg text-slate-400"></i>' +
                '<div class="min-w-0 flex-1"><div class="font-semibold" data-tag></div>' +
                '<div class="truncate text-xs text-slate-500 dark:text-slate-400" data-meta></div></div>' +
                '<button type="button" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-red-600 dark:hover:bg-slate-800" aria-label="Remove"><i class="bi bi-x-lg"></i></button>' +
                '<input type="hidden" name="asset_ids[]">';
            row.querySelector('i').classList.add('bi-' + asset.icon);
            row.querySelector('[data-tag]').textContent = asset.tag;
            row.querySelector('[data-meta]').textContent = asset.name + ' · ' + (asset.from ? 'from ' + asset.from : 'not at a desk');
            row.querySelector('input').value = asset.id;
            row.querySelector('button').addEventListener('click', function () { remove(asset.id); });
            list.prepend(row);
            chosen.set(asset.id, row);
            refresh();
        };

        // Asset labels hold a link to the asset (…/assets/12); a typed tag works too.
        const add = function (text, fromCamera) {
            let asset = null;
            let atDesk = null;

            if (scanner.isLink(text)) {
                const id = scanner.linkId(text, 'assets');
                if (!id) {
                    scanner.setError(scanner.linkId(text, 'workstations')
                        ? 'That\'s a desk label. Scan the label on the asset.'
                        : 'That QR code isn\'t an asset label.');
                    return false;
                }
                atDesk = byId(here, id);
                asset = byId(placeable, id);
                if (!asset && !atDesk) {
                    scanner.setError('That asset can\'t be placed at a desk. Only desktops, monitors and speakerphones that are in service can.');
                    return false;
                }
            } else {
                const tag = normalize(text);
                atDesk = byTag(here, tag);
                asset = byTag(placeable, tag);
                if (!asset && !atDesk) {
                    scanner.setError('No desktop, monitor or speakerphone in service has the tag "' + tag + '".');
                    return false;
                }
            }

            if (atDesk) {
                scanner.setError('');
                scanner.toast(atDesk.tag + ' is already at this desk');
                return true;
            }
            if (chosen.has(asset.id)) {
                scanner.setError('');
                scanner.toast(asset.tag + ' is already in the list');
                return true;
            }
            if (chosen.size >= maxItems) {
                scanner.setError('You can place up to ' + maxItems + ' assets at once. Place these first, then scan the rest.');
                return false;
            }

            scanner.setError('');
            addRow(asset);
            scanner.toast('Added ' + asset.tag);
            if (fromCamera) {
                scanner.buzz();
            }
            return true;
        };

        document.addEventListener('click', function (e) {
            if (!e.target.closest('[data-scan-assets]')) {
                return;
            }
            e.preventDefault();
            scanner.open({
                title: 'Scan equipment',
                subtitle: 'Placing at ' + deskCode,
                panel: panel,
                onCode: function (text) { add(text, true); },
            });
        });

        const addTyped = function () {
            if (tagInput.value.trim() && add(tagInput.value, false)) {
                tagInput.value = '';
            }
        };
        $('[data-tag-add]').addEventListener('click', addTyped);
        tagInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                addTyped();
            }
        });

        panel.addEventListener('submit', function () {
            submit.disabled = true;
            submit.querySelector('span').textContent = 'Placing…';
        });

        refresh();
    });
</script>
@endpush
