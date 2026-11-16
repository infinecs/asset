@extends('layouts.app')
@section('title', 'Floor Plan' . ($location ? ' - ' . $location->name : ''))
@section('page-title', 'Floor Plan')
@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h5 class="text-lg font-semibold text-slate-900 dark:text-white">Floor Plan</h5>
        <p class="mb-0 text-sm text-slate-500 dark:text-slate-400">See where each desk is in the office, who sits there and what equipment is on it</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        @if($locations->isNotEmpty())
        <label for="floor-plan-location" class="sr-only">Location</label>
        <select id="floor-plan-location" class="field-input w-auto min-w-48"
                onchange="if (!window.floorPlanDirty || confirm('You have unsaved layout changes. Leave without saving?')) { window.floorPlanDirty = false; window.location = '{{ route('workstations.floor-plan') }}?location=' + this.value; } else { this.value = '{{ $location?->id }}'; }">
            @foreach($locations as $loc)
            <option value="{{ $loc->id }}" @selected($location && $loc->id === $location->id)>{{ $loc->name }} ({{ $loc->workstations_count }} desk{{ $loc->workstations_count === 1 ? '' : 's' }})</option>
            @endforeach
        </select>
        @endif
        <a href="{{ route('workstations.index', array_filter(['location' => $location?->id])) }}" class="btn btn-outline"><i class="bi bi-list-ul"></i>Workstation List</a>
    </div>
</div>

@if($errors->any())
<div class="alert alert-danger mb-4">
    <i class="bi bi-exclamation-triangle"></i>
    <span class="flex-1">{{ $errors->first() }}</span>
</div>
@endif

@if(!$location)
<div class="card">
    <div class="card-body py-10 text-center text-slate-500 dark:text-slate-400">
        <i class="bi bi-map mb-2 block text-3xl text-slate-300 dark:text-slate-600"></i>
        No locations yet. <a href="{{ route('locations.create') }}" class="text-primary-600 hover:underline dark:text-primary-400">Add a location</a> and its workstations first.
    </div>
</div>
@elseif(!$location->floor_plan_path)
<div class="card">
    <div class="card-body py-10">
        <div class="mx-auto max-w-lg text-center">
            <i class="bi bi-map mb-3 block text-4xl text-slate-300 dark:text-slate-600"></i>
            <h6 class="mb-1 text-base font-semibold text-slate-800 dark:text-slate-100">No floor plan for {{ $location->name }} yet</h6>
            <p class="mb-5 text-sm text-slate-500 dark:text-slate-400">
                Upload an image of the office layout, then drag its {{ $desks->count() }} desk{{ $desks->count() === 1 ? '' : 's' }} onto their places.
                A PDF floor plan can be exported or screenshotted as an image first.
            </p>
            <form method="POST" action="{{ route('locations.floor-plan.upload', $location) }}" enctype="multipart/form-data" class="flex flex-col items-center gap-3 sm:flex-row sm:justify-center">
                @csrf
                <label for="floor-plan-file" class="sr-only">Floor plan image</label>
                <input type="file" id="floor-plan-file" name="floor_plan" accept="image/jpeg,image/png,image/webp" required
                       class="block w-full max-w-xs text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-200 dark:text-slate-300 dark:file:bg-slate-800 dark:file:text-slate-200">
                <button class="btn btn-primary"><i class="bi bi-upload"></i>Upload Floor Plan</button>
            </form>
            <p class="mt-3 text-xs text-slate-400">JPG, PNG or WEBP, up to 10 MB.</p>
        </div>
    </div>
</div>
@else
@php
    $planData = ['desks' => $desks->values(), 'selected' => $selectedDesk, 'saveUrl' => route('locations.floor-plan.layout', $location)];
@endphp
<script type="application/json" id="floor-plan-data">@json($planData)</script>

<div x-data="floorPlan(JSON.parse(document.getElementById('floor-plan-data').textContent))" x-init="init()" class="space-y-4">
    {{-- Toolbar --}}
    <div class="card">
        <div class="card-body flex flex-wrap items-center gap-3">
            <div class="relative min-w-56 flex-1">
                <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <label for="floor-plan-search" class="sr-only">Find a desk or person</label>
                <input type="search" id="floor-plan-search" x-model="search" @keydown.enter.prevent="selectFirstMatch()"
                       class="field-input pl-9" placeholder="Find a desk, person or asset tag...">
            </div>
            <span x-show="search.trim()" x-cloak class="text-xs text-slate-500 dark:text-slate-400" x-text="matchSummary()"></span>

            <div class="inline-flex items-center rounded-lg border border-slate-200 dark:border-slate-700" role="group" aria-label="Zoom">
                <button type="button" class="btn btn-sm btn-icon rounded-r-none" @click="zoomOut()" :disabled="zoom <= zoomLevels[0]" title="Zoom out"><i class="bi bi-zoom-out"></i></button>
                <span class="w-12 text-center text-xs tabular-nums text-slate-600 dark:text-slate-300" x-text="Math.round(zoom * 100) + '%'"></span>
                <button type="button" class="btn btn-sm btn-icon rounded-l-none" @click="zoomIn()" :disabled="zoom >= zoomLevels[zoomLevels.length - 1]" title="Zoom in"><i class="bi bi-zoom-in"></i></button>
            </div>

            <div class="ms-auto flex flex-wrap items-center gap-2">
                <template x-if="!editing">
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" class="btn btn-primary btn-sm" @click="startEditing()"><i class="bi bi-arrows-move"></i>Edit Layout</button>
                        <form method="POST" action="{{ route('locations.floor-plan.upload', $location) }}" enctype="multipart/form-data">
                            @csrf
                            <label class="btn btn-outline btn-sm cursor-pointer" title="Upload a new image; desks keep their positions">
                                <i class="bi bi-image"></i>Replace Image
                                <input type="file" name="floor_plan" accept="image/jpeg,image/png,image/webp" class="hidden" onchange="this.form.submit()">
                            </label>
                        </form>
                        <form method="POST" action="{{ route('locations.floor-plan.destroy', $location) }}"
                              onsubmit="return confirm('Remove the floor plan for {{ addslashes($location->name) }}? All its desks will be taken off the plan.')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-outline-danger btn-sm" title="Remove floor plan"><i class="bi bi-trash"></i>Remove</button>
                        </form>
                    </div>
                </template>
                <template x-if="editing">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-xs text-slate-500 dark:text-slate-400" x-show="dirty">Unsaved changes</span>
                        <button type="button" class="btn btn-outline btn-sm" @click="cancelEditing()" :disabled="saving">Cancel</button>
                        <button type="button" class="btn btn-primary btn-sm" @click="save()" :disabled="saving || !dirty">
                            <i class="bi" :class="saving ? 'bi-hourglass-split' : 'bi-check2'"></i><span x-text="saving ? 'Saving...' : 'Save Layout'"></span>
                        </button>
                    </div>
                </template>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-x-5 gap-y-2 border-t border-slate-200 px-5 py-2.5 text-xs text-slate-600 dark:border-slate-800 dark:text-slate-300">
            <span class="flex items-center gap-1.5"><span class="inline-block h-3 w-5 rounded border border-primary-700 bg-primary-600"></span>Assigned</span>
            <span class="flex items-center gap-1.5"><span class="inline-block h-3 w-5 rounded border border-primary-500 bg-white dark:bg-slate-900"></span>Hot desk</span>
            <span class="flex items-center gap-1.5"><span class="inline-block h-3 w-5 rounded border border-dashed border-slate-400 bg-white dark:bg-slate-900"></span>Empty (no equipment)</span>
            <span class="flex items-center gap-1.5"><span class="inline-block h-2.5 w-2.5 rounded-full bg-amber-500"></span>Audit due</span>
            <span class="flex items-center gap-1.5"><span class="inline-block h-2.5 w-2.5 rounded-full bg-red-500"></span>Last audit found issues</span>
            <span class="ms-auto tabular-nums text-slate-500 dark:text-slate-400" x-text="placed().length + ' of ' + desks.length + ' desks on the plan'"></span>
        </div>
    </div>

    <div x-show="message" x-cloak x-transition class="alert alert-success"><i class="bi bi-check-circle"></i><span class="flex-1" x-text="message"></span></div>
    <div x-show="error" x-cloak class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i><span class="flex-1" x-text="error"></span></div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
        {{-- The plan --}}
        <div class="lg:col-span-9">
            <div class="card overflow-hidden">
                <div x-ref="viewport" class="max-h-[75vh] overflow-auto bg-slate-100 dark:bg-slate-950">
                    <div class="relative select-none" :style="'width:' + (zoom * 100) + '%'">
                        <img x-ref="plan" src="{{ asset('storage/' . $location->floor_plan_path) }}" alt="Floor plan of {{ $location->name }}"
                             class="block w-full" draggable="false" @click="if (!editing) selectedId = null">

                        <template x-for="desk in placed()" :key="desk.id">
                            <button type="button" :id="'desk-marker-' + desk.id"
                                    class="absolute -translate-x-1/2 -translate-y-1/2 whitespace-nowrap rounded-md border px-1.5 py-0.5 font-mono text-[11px] font-semibold leading-4 shadow-sm transition-opacity focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-400"
                                    :class="[markerClass(desk), editing ? 'cursor-move touch-none' : 'cursor-pointer']"
                                    :style="'left:' + desk.x + '%; top:' + desk.y + '%'"
                                    :title="desk.code + (desk.occupant ? ' — ' + desk.occupant : '')"
                                    @pointerdown="startDrag($event, desk)"
                                    @click="select(desk.id)"
                                    @keydown="nudge($event, desk)">
                                <span x-text="desk.label"></span>
                                <span x-show="desk.audit_issues" class="absolute -right-1 -top-1 h-2.5 w-2.5 rounded-full bg-red-500 ring-2 ring-white dark:ring-slate-900"></span>
                                <span x-show="desk.audit_due && !desk.audit_issues" class="absolute -right-1 -top-1 h-2.5 w-2.5 rounded-full bg-amber-500 ring-2 ring-white dark:ring-slate-900"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
            <p x-show="editing" x-cloak class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                Drag desks onto the plan or to a new spot. Drag a desk back to the list to take it off the plan.
                Arrow keys nudge the selected desk (hold Shift for bigger steps).
            </p>
        </div>

        {{-- Side panel --}}
        <div class="space-y-4 lg:col-span-3">
            {{-- Edit mode: desks not on the plan yet --}}
            <div x-show="editing" x-cloak x-ref="tray" class="card" :class="drag && drag.moved ? 'ring-2 ring-primary-300 dark:ring-primary-700' : ''">
                <div class="card-header">
                    <h6 class="text-sm font-semibold text-slate-800 dark:text-slate-100"><i class="bi bi-inbox me-2 text-slate-400"></i>Not on the plan</h6>
                    <span class="badge badge-secondary" x-text="unplaced().length"></span>
                </div>
                <div class="card-body max-h-[60vh] space-y-1.5 overflow-y-auto">
                    <template x-for="desk in unplaced()" :key="desk.id">
                        <div class="flex cursor-move touch-none items-center gap-2 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-sm hover:border-primary-400 dark:border-slate-700 dark:bg-slate-900"
                             :class="drag && drag.id === desk.id ? 'opacity-40' : ''"
                             @pointerdown="startDrag($event, desk)">
                            <i class="bi bi-grip-vertical text-slate-400"></i>
                            <span class="font-mono text-xs font-semibold text-slate-800 dark:text-slate-100" x-text="desk.label"></span>
                            <span class="ms-auto truncate text-xs text-slate-500 dark:text-slate-400" x-text="desk.occupant || 'Hot desk'"></span>
                        </div>
                    </template>
                    <p x-show="unplaced().length === 0" class="py-4 text-center text-xs text-slate-500 dark:text-slate-400">Every desk is on the plan.</p>
                </div>
            </div>

            {{-- Selected desk --}}
            <template x-if="selected()">
                <div class="card">
                    <div class="card-header">
                        <h6 class="truncate font-mono text-sm font-semibold text-slate-800 dark:text-slate-100" x-text="selected().code"></h6>
                        <button type="button" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200" @click="selectedId = null" title="Close"><i class="bi bi-x-lg"></i></button>
                    </div>
                    <div class="card-body space-y-3 text-sm">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span class="badge" :class="{ 'badge-primary': selected().status === 'assigned', 'badge-secondary': selected().status !== 'assigned' }"
                                  x-text="{ assigned: 'Assigned', hotdesk: 'Hot desk', empty: 'Empty' }[selected().status]"></span>
                            <span x-show="selected().audit_issues" class="badge badge-danger">Last audit found issues</span>
                            <span x-show="selected().audit_due && !selected().audit_issues" class="badge badge-warning">Audit due</span>
                            <span x-show="selected().x === null" class="badge badge-secondary">Not on the plan</span>
                        </div>
                        <div>
                            <div class="text-xs text-slate-500 dark:text-slate-400">Occupant</div>
                            <div class="font-semibold text-slate-800 dark:text-slate-100" x-text="selected().occupant || 'Nobody / hot desk'"></div>
                        </div>
                        <div>
                            <div class="text-xs text-slate-500 dark:text-slate-400" x-text="'Equipment (' + selected().assets.length + ')'"></div>
                            <ul class="mt-1 space-y-1">
                                <template x-for="item in selected().assets" :key="item.tag">
                                    <li class="flex items-center gap-2">
                                        <i class="bi text-slate-400" :class="'bi-' + item.icon"></i>
                                        <a :href="item.url" class="font-mono text-xs font-semibold text-primary-600 hover:underline dark:text-primary-400" x-text="item.tag"></a>
                                        <span class="truncate text-xs text-slate-500 dark:text-slate-400" x-text="item.name"></span>
                                    </li>
                                </template>
                            </ul>
                            <p x-show="selected().assets.length === 0" class="mt-1 text-xs text-slate-400">No equipment at this desk.</p>
                        </div>
                        <div>
                            <div class="text-xs text-slate-500 dark:text-slate-400">Last audit</div>
                            <div class="text-slate-700 dark:text-slate-200" x-text="selected().last_audit || 'Never audited'"></div>
                        </div>
                        <div class="flex flex-wrap gap-2 pt-1">
                            <a :href="selected().url" class="btn btn-outline btn-sm flex-1"><i class="bi bi-box-arrow-up-right"></i>Open Desk</a>
                            <a :href="selected().audit_url" class="btn btn-outline-primary btn-sm flex-1"><i class="bi bi-clipboard-check"></i>Audit</a>
                        </div>
                        <button type="button" x-show="editing && selected().x !== null" class="btn btn-outline-danger btn-sm w-full" @click="unplace(selected())">
                            <i class="bi bi-box-arrow-in-down"></i>Take Off the Plan
                        </button>
                    </div>
                </div>
            </template>

            {{-- Summary when nothing is selected --}}
            <div x-show="!selected() && !editing" class="card">
                <div class="card-header">
                    <h6 class="text-sm font-semibold text-slate-800 dark:text-slate-100"><i class="bi bi-pie-chart me-2 text-slate-400"></i>{{ $location->name }}</h6>
                </div>
                <div class="card-body space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-slate-500 dark:text-slate-400">Assigned</span><span class="font-semibold tabular-nums" x-text="count('assigned')"></span></div>
                    <div class="flex justify-between"><span class="text-slate-500 dark:text-slate-400">Hot desks</span><span class="font-semibold tabular-nums" x-text="count('hotdesk')"></span></div>
                    <div class="flex justify-between"><span class="text-slate-500 dark:text-slate-400">Empty</span><span class="font-semibold tabular-nums" x-text="count('empty')"></span></div>
                    <div class="flex justify-between"><span class="text-slate-500 dark:text-slate-400">Audit due</span><span class="font-semibold tabular-nums text-amber-600 dark:text-amber-400" x-text="desks.filter(d => d.audit_due).length"></span></div>
                    <p class="border-t border-slate-200 pt-3 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400">Click a desk on the plan to see who sits there and what equipment is on it.</p>
                    <p x-show="unplaced().length" class="text-xs text-slate-500 dark:text-slate-400">
                        <span x-text="unplaced().length"></span> desk(s) are not on the plan yet.
                        <button type="button" class="font-semibold text-primary-600 hover:underline dark:text-primary-400" @click="startEditing()">Place them</button>
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- What follows the pointer while dragging --}}
    <div x-show="drag && drag.moved" x-cloak class="pointer-events-none fixed z-50 -translate-x-1/2 -translate-y-1/2 whitespace-nowrap rounded-md border border-primary-700 bg-primary-600 px-1.5 py-0.5 font-mono text-[11px] font-semibold text-white shadow-lg"
         :style="drag ? 'left:' + drag.x + 'px; top:' + drag.y + 'px' : ''" x-text="drag ? drag.label : ''"></div>
</div>
@endif
@endsection

@push('scripts')
<script>
    window.floorPlan = function (config) {
        const NUDGE = 0.5;
        const NUDGE_BIG = 2;

        return {
            desks: config.desks,
            saveUrl: config.saveUrl,
            selectedId: config.selected,
            search: '',
            editing: false,
            dirty: false,
            saving: false,
            message: '',
            error: '',
            drag: null,
            zoom: 1,
            zoomLevels: [1, 1.5, 2, 3],
            snapshot: null,

            init() {
                window.addEventListener('beforeunload', (e) => {
                    if (this.dirty) { e.preventDefault(); e.returnValue = ''; }
                });
                this.$watch('dirty', (value) => { window.floorPlanDirty = value; });
                if (this.selectedId) this.$nextTick(() => this.reveal(this.selectedId));
            },

            placed() { return this.desks.filter((d) => d.x !== null && d.y !== null); },
            unplaced() { return this.desks.filter((d) => d.x === null || d.y === null); },
            selected() { return this.desks.find((d) => d.id === this.selectedId) || null; },
            count(status) { return this.desks.filter((d) => d.status === status).length; },

            query() { return this.search.trim().toLowerCase(); },
            matches(desk) { const q = this.query(); return !q || desk.search.includes(q); },
            matchSummary() {
                const all = this.desks.filter((d) => this.matches(d));
                const off = all.filter((d) => d.x === null).length;
                if (!all.length) return 'No matches';
                return all.length + ' match' + (all.length === 1 ? '' : 'es') + (off ? ' (' + off + ' not on the plan)' : '');
            },
            selectFirstMatch() {
                const first = this.placed().find((d) => this.matches(d)) || this.desks.find((d) => this.matches(d));
                if (first) this.select(first.id);
            },

            markerClass(desk) {
                const classes = {
                    assigned: 'border-primary-700 bg-primary-600 text-white',
                    hotdesk: 'border-primary-500 bg-white text-primary-700 dark:bg-slate-900 dark:text-primary-300',
                    empty: 'border-dashed border-slate-400 bg-white/90 text-slate-500 dark:bg-slate-900/90 dark:text-slate-400',
                }[desk.status];
                const state = [];
                if (this.selectedId === desk.id) state.push('z-20 scale-110 ring-2 ring-amber-400 ring-offset-1 dark:ring-offset-slate-900');
                else state.push('z-10');
                if (!this.matches(desk)) state.push('opacity-25');
                if (this.drag && this.drag.moved && this.drag.id === desk.id) state.push('opacity-30');
                return classes + ' ' + state.join(' ');
            },

            select(id) {
                this.selectedId = id;
                this.$nextTick(() => {
                    this.reveal(id);
                    // Dragging cancels the browser's own focus; focus the desk so arrow keys can nudge it.
                    if (this.editing) document.getElementById('desk-marker-' + id)?.focus({ preventScroll: true });
                });
            },
            reveal(id) {
                const el = document.getElementById('desk-marker-' + id);
                if (el) el.scrollIntoView({ block: 'nearest', inline: 'nearest', behavior: 'smooth' });
            },

            zoomIn() { const i = this.zoomLevels.indexOf(this.zoom); if (i < this.zoomLevels.length - 1) this.zoom = this.zoomLevels[i + 1]; },
            zoomOut() { const i = this.zoomLevels.indexOf(this.zoom); if (i > 0) this.zoom = this.zoomLevels[i - 1]; },

            positions() { return this.desks.map((d) => ({ id: d.id, x: d.x, y: d.y })); },
            startEditing() {
                this.snapshot = this.positions();
                this.editing = true;
                this.message = '';
                this.error = '';
            },
            cancelEditing() {
                const byId = Object.fromEntries(this.snapshot.map((p) => [p.id, p]));
                this.desks.forEach((d) => { d.x = byId[d.id].x; d.y = byId[d.id].y; });
                this.editing = false;
                this.dirty = false;
                this.error = '';
            },
            unplace(desk) {
                desk.x = null;
                desk.y = null;
                this.dirty = true;
            },

            startDrag(event, desk) {
                if (!this.editing || event.button > 0) return;
                event.preventDefault();
                this.drag = { id: desk.id, label: desk.label, startX: event.clientX, startY: event.clientY, x: event.clientX, y: event.clientY, moved: false };

                const move = (e) => {
                    this.drag.x = e.clientX;
                    this.drag.y = e.clientY;
                    if (Math.hypot(e.clientX - this.drag.startX, e.clientY - this.drag.startY) > 4) this.drag.moved = true;
                };
                const up = (e) => {
                    window.removeEventListener('pointermove', move);
                    window.removeEventListener('pointerup', up);
                    window.removeEventListener('pointercancel', up);
                    this.endDrag(e, desk);
                };
                window.addEventListener('pointermove', move);
                window.addEventListener('pointerup', up);
                window.addEventListener('pointercancel', up);
            },
            endDrag(event, desk) {
                const drag = this.drag;
                this.drag = null;
                if (!drag || !drag.moved || event.type === 'pointercancel') return;

                const inside = (rect) => event.clientX >= rect.left && event.clientX <= rect.right && event.clientY >= rect.top && event.clientY <= rect.bottom;
                const plan = this.$refs.plan.getBoundingClientRect();
                const viewport = this.$refs.viewport.getBoundingClientRect();

                // Only the visible part of the plan counts when it's zoomed and scrolled.
                if (inside(plan) && inside(viewport)) {
                    const clamp = (v) => Math.min(100, Math.max(0, Math.round(v * 1000) / 1000));
                    desk.x = clamp((event.clientX - plan.left) / plan.width * 100);
                    desk.y = clamp((event.clientY - plan.top) / plan.height * 100);
                    this.dirty = true;
                    this.select(desk.id);
                } else if (inside(this.$refs.tray.getBoundingClientRect()) && desk.x !== null) {
                    this.unplace(desk);
                }
            },
            nudge(event, desk) {
                if (!this.editing || desk.x === null) return;
                const step = event.shiftKey ? NUDGE_BIG : NUDGE;
                const moves = { ArrowLeft: [-step, 0], ArrowRight: [step, 0], ArrowUp: [0, -step], ArrowDown: [0, step] }[event.key];
                if (!moves) return;
                event.preventDefault();
                desk.x = Math.min(100, Math.max(0, Math.round((desk.x + moves[0]) * 1000) / 1000));
                desk.y = Math.min(100, Math.max(0, Math.round((desk.y + moves[1]) * 1000) / 1000));
                this.dirty = true;
            },

            async save() {
                this.saving = true;
                this.error = '';
                try {
                    const response = await fetch(this.saveUrl, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({ positions: this.positions() }),
                    });
                    const data = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        throw new Error(data.message || 'The layout could not be saved. Check your connection and try again.');
                    }
                    this.dirty = false;
                    this.editing = false;
                    this.message = data.message || 'Layout saved.';
                    setTimeout(() => { this.message = ''; }, 5000);
                } catch (e) {
                    this.error = e.message;
                } finally {
                    this.saving = false;
                }
            },
        };
    };
</script>
@endpush
