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
    $planData = [
        'desks' => $desks->values(),
        'employees' => $employees->values(),
        'selected' => $selectedDesk,
        'saveUrl' => route('locations.floor-plan.layout', $location),
        'printUrl' => route('workstations.floor-plan.print', ['location' => $location->id]),
        'audit' => $audit,
        'maxCopies' => \App\Http\Controllers\FloorPlanController::MAX_COPIES,
    ];
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

            {{-- What the desk colours show --}}
            <div class="inline-flex items-center rounded-lg border border-slate-200 p-0.5 dark:border-slate-700" role="group" aria-label="Colour desks by">
                <button type="button" class="btn btn-sm" :class="colourBy === 'occupancy' ? 'btn-primary' : 'text-slate-600 dark:text-slate-300'"
                        @click="colourBy = 'occupancy'" :aria-pressed="colourBy === 'occupancy'" title="Colour desks by who sits there"><i class="bi bi-person"></i>Occupancy</button>
                <button type="button" class="btn btn-sm" :class="colourBy === 'audit' ? 'btn-primary' : 'text-slate-600 dark:text-slate-300'"
                        @click="colourBy = 'audit'" :aria-pressed="colourBy === 'audit'" title="Colour desks by how long since they were audited"><i class="bi bi-clipboard-check"></i>Audit</button>
            </div>

            <button type="button" x-show="attentionCount() || attentionOnly" x-cloak class="btn btn-sm"
                    :class="attentionOnly ? 'border-orange-500 bg-orange-500 text-white hover:bg-orange-600' : 'btn-outline text-orange-600 dark:text-orange-400'"
                    @click="attentionOnly = !attentionOnly" :aria-pressed="attentionOnly"
                    title="Highlight only the desks that need attention">
                <i class="bi bi-exclamation-triangle"></i><span x-text="'Needs attention (' + attentionCount() + ')'"></span>
            </button>

            <div class="ms-auto flex flex-wrap items-center gap-2">
                <template x-if="!editing">
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" class="btn btn-primary btn-sm" @click="startEditing()"><i class="bi bi-arrows-move"></i>Edit Layout</button>
                        <a :href="printHref()" target="_blank" class="btn btn-outline btn-sm" title="Open a print-ready copy of the plan; print it or save it as a PDF"><i class="bi bi-printer"></i>Print / PDF</a>
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
                        <label class="me-2 flex cursor-pointer select-none items-center gap-1.5 text-xs text-slate-600 dark:text-slate-300"
                               title="Resizing one desk resizes every desk on the plan. Turn off to size a desk on its own.">
                            <input type="checkbox" class="h-4 w-4 rounded border-slate-300 accent-primary-600" x-model="matchSizes">
                            Same size for all desks
                        </label>
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
            <template x-if="colourBy === 'occupancy'">
                <div class="contents">
                    <span class="flex items-center gap-1.5"><span class="inline-block h-3 w-5 rounded border border-primary-700 bg-primary-600"></span>Assigned</span>
                    <span class="flex items-center gap-1.5"><span class="inline-block h-3 w-5 rounded border border-primary-500 bg-white dark:bg-slate-900"></span>Hot desk</span>
                    <span class="flex items-center gap-1.5"><span class="inline-block h-3 w-5 rounded border border-dashed border-slate-400 bg-white dark:bg-slate-900"></span>Empty (no equipment)</span>
                    <span class="flex items-center gap-1.5"><span class="inline-block h-2.5 w-2.5 rounded-full bg-amber-500"></span>Audit due</span>
                </div>
            </template>
            <template x-if="colourBy === 'audit'">
                <div class="contents">
                    <span class="flex items-center gap-1.5"><span class="inline-block h-3 w-5 rounded border border-emerald-700 bg-emerald-600"></span>Audited in the last <span x-text="audit.interval - audit.soon"></span> days</span>
                    <span class="flex items-center gap-1.5"><span class="inline-block h-3 w-5 rounded border border-amber-500 bg-amber-300"></span>Due within <span x-text="audit.soon"></span> days</span>
                    <span class="flex items-center gap-1.5"><span class="inline-block h-3 w-5 rounded border border-red-700 bg-red-600"></span>Overdue (over <span x-text="audit.interval"></span> days)</span>
                    <span class="flex items-center gap-1.5"><span class="inline-block h-3 w-5 rounded border border-dashed border-red-500 bg-white dark:bg-slate-900"></span>Never audited</span>
                </div>
            </template>
            <span class="flex items-center gap-1.5"><span class="inline-block h-2.5 w-2.5 rounded-full bg-red-500"></span>Last audit found issues</span>
            <span class="flex items-center gap-1.5"><span class="flex h-3.5 w-3.5 items-center justify-center rounded-full bg-orange-500 text-[9px] font-bold text-white">!</span>Needs attention</span>
            <span class="ms-auto tabular-nums text-slate-500 dark:text-slate-400" x-text="placed().length + ' of ' + desks.length + ' desks on the plan'"></span>
        </div>
    </div>

    <div x-show="message" x-cloak x-transition class="alert alert-success"><i class="bi bi-check-circle"></i><span class="flex-1" x-text="message"></span></div>
    <div x-show="error" x-cloak class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i><span class="flex-1" x-text="error"></span></div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
        {{-- The plan --}}
        <div class="lg:col-span-9">
            <div class="card relative overflow-hidden">
                {{-- Shown while dragging: let go over it (or press Esc) to put the desk back where it was --}}
                <div x-ref="cancelZone" x-show="drag && drag.moved" x-cloak
                     class="pointer-events-none absolute right-3 top-3 z-30 flex items-center gap-1.5 rounded-full border-2 px-3 py-2 text-xs font-semibold shadow-lg transition"
                     :class="drag && drag.overCancel ? 'scale-110 border-red-600 bg-red-600 text-white' : 'border-red-300 bg-white text-red-600 dark:border-red-800 dark:bg-slate-900 dark:text-red-400'">
                    <i class="bi bi-x-lg"></i><span>Drop here to cancel</span>
                </div>
                <div x-ref="viewport" class="max-h-[75vh] overflow-auto bg-slate-100 dark:bg-slate-950" @scroll="hover = null">
                    <div class="relative select-none" :style="'width:' + (zoom * 100) + '%'">
                        <img x-ref="plan" src="{{ asset('storage/' . $location->floor_plan_path) }}" alt="Floor plan of {{ $location->name }}"
                             class="block w-full" draggable="false" @click="if (!editing) selectedId = null">

                        <template x-for="desk in placed()" :key="desk.id">
                            <button type="button" :id="'desk-marker-' + desk.id"
                                    class="group absolute flex -translate-x-1/2 -translate-y-1/2 items-center justify-center whitespace-nowrap rounded-md border px-1.5 py-0.5 font-mono text-[11px] font-semibold leading-4 shadow-sm transition-opacity focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-400"
                                    :class="[markerClass(desk), editing ? 'cursor-move touch-none' : 'cursor-pointer']"
                                    :style="markerStyle(desk)"
                                    :title="desk.code + (desk.occupant ? ' — ' + desk.occupant : '')"
                                    @pointerdown="startDrag($event, desk)"
                                    @pointerenter="showHover($event, desk)"
                                    @pointerleave="hover = null"
                                    @click="select(desk.id)"
                                    @keydown="nudge($event, desk)">
                                <span class="min-w-0 truncate" x-text="desk.label"></span>
                                {{-- The handles and dots turn with a vertical desk, so they're placed on the corner that ends up where it is on a horizontal one --}}
                                {{-- Rotate knob (edit mode): turn the desk between horizontal and vertical --}}
                                <span x-show="editing" title="Rotate (R)"
                                      class="absolute flex h-4 w-4 cursor-pointer touch-none items-center justify-center rounded-full border-2 border-white bg-sky-600 text-[8px] text-white shadow transition-opacity dark:border-slate-900"
                                      :class="[desk.r === 90 ? '-bottom-2 -left-2' : '-left-2 -top-2', selectedId === desk.id ? 'opacity-100' : 'opacity-0 group-hover:opacity-100']"
                                      @pointerdown.stop.prevent @click.stop="rotate(desk)"><i class="bi bi-arrow-clockwise"></i></span>
                                {{-- Resize handle (edit mode): drag the corner to match the desk's size on the plan --}}
                                <span x-show="editing" title="Drag to resize"
                                      class="absolute h-3.5 w-3.5 cursor-se-resize touch-none rounded-sm border-2 border-white bg-amber-500 shadow transition-opacity dark:border-slate-900"
                                      :class="[desk.r === 90 ? '-right-1.5 -top-1.5' : '-bottom-1.5 -right-1.5', selectedId === desk.id || (resizing && resizing.id === desk.id) ? 'opacity-100' : 'opacity-0 group-hover:opacity-100']"
                                      @pointerdown.stop="startResize($event, desk)"></span>
                                <span x-show="desk.audit_issues" class="absolute h-2.5 w-2.5 rounded-full bg-red-500 ring-2 ring-white dark:ring-slate-900"
                                      :class="desk.r === 90 ? '-left-1 -top-1' : '-right-1 -top-1'"></span>
                                {{-- In audit colours the desk itself shows that it's due --}}
                                <span x-show="desk.audit_due && !desk.audit_issues && colourBy !== 'audit'" class="absolute h-2.5 w-2.5 rounded-full bg-amber-500 ring-2 ring-white dark:ring-slate-900"
                                      :class="desk.r === 90 ? '-left-1 -top-1' : '-right-1 -top-1'"></span>
                                {{-- Needs attention: bottom-left on screen, kept upright on a vertical desk --}}
                                <span x-show="desk.warnings.length" class="absolute flex h-3.5 w-3.5 items-center justify-center rounded-full bg-orange-500 text-[9px] font-bold leading-none text-white ring-2 ring-white dark:ring-slate-900"
                                      :class="desk.r === 90 ? '-bottom-1.5 -right-1.5' : '-bottom-1.5 -left-1.5'"
                                      :style="desk.r ? 'rotate:-' + desk.r + 'deg' : ''">!</span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
            <p x-show="editing" x-cloak class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                Drag desks onto the plan or to a new spot. Drag a desk back to the list to take it off the plan.
                While dragging, let go over <strong>Drop here to cancel</strong> (or press Esc) to leave it where it was.
                Drag the amber corner of a desk to resize it to the desk's real size<span x-show="matchSizes">; with <strong>Same size for all desks</strong> on, every desk follows</span>.
                Click the blue <i class="bi bi-arrow-clockwise"></i> knob (or press R) to turn a desk between horizontal and vertical.
                Arrow keys nudge the selected desk (hold Shift for bigger steps).
                A vertical desk keeps its length and depth, just turned.
                To lay out a row, select a desk and use <strong>Add copies</strong> in the side panel.
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
                            <span x-show="selected().isNew" class="badge badge-primary">New: created when you save</span>
                        </div>
                        <div x-show="selected().warnings.length" class="rounded-lg border border-orange-200 bg-orange-50 px-3 py-2 text-xs text-orange-800 dark:border-orange-900/50 dark:bg-orange-950/30 dark:text-orange-300">
                            <div class="mb-0.5 font-semibold"><i class="bi bi-exclamation-triangle me-1"></i>Needs attention</div>
                            <ul class="list-disc space-y-0.5 ps-4">
                                <template x-for="warning in selected().warnings" :key="warning"><li x-text="warning"></li></template>
                            </ul>
                        </div>
                        <div>
                            <div class="flex items-center justify-between gap-2">
                                <div class="text-xs text-slate-500 dark:text-slate-400">Occupant</div>
                                <button type="button" x-show="!seating && !selected().isNew" class="text-xs font-semibold text-primary-600 hover:underline dark:text-primary-400"
                                        @click="openSeating()" x-text="selected().occupant ? 'Change' : 'Assign someone'"></button>
                            </div>
                            <div x-show="!seating" class="font-semibold text-slate-800 dark:text-slate-100" x-text="selected().occupant || 'Nobody / hot desk'"></div>

                            {{-- Pick who sits here; saved straight away --}}
                            <div x-show="seating" class="mt-1 space-y-1.5" @keydown.escape.stop.prevent="closeSeating()">
                                <input type="text" x-ref="seatSearch" x-model="seatQuery" class="field-input" placeholder="Search name or ID number..."
                                       :disabled="seatSaving" @keydown.enter.prevent="seatOptions()[0] && assign(seatOptions()[0])">
                                <ul class="max-h-60 divide-y divide-slate-100 overflow-y-auto rounded-lg border border-slate-200 dark:divide-slate-800 dark:border-slate-700"
                                    :class="seatSaving ? 'pointer-events-none opacity-60' : ''">
                                    <li x-show="selected().occupant_id">
                                        <button type="button" class="flex w-full items-center gap-2 px-3 py-2 text-left text-xs text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800" @click="assign(null)">
                                            <i class="bi bi-person-dash"></i>Nobody (make it a hot desk)
                                        </button>
                                    </li>
                                    <template x-for="person in seatOptions()" :key="person.id">
                                        <li>
                                            <button type="button" class="w-full px-3 py-2 text-left hover:bg-slate-50 dark:hover:bg-slate-800" @click="assign(person)">
                                                <span class="flex items-center gap-2">
                                                    <span class="truncate text-sm text-slate-800 dark:text-slate-100" x-text="person.name"></span>
                                                    <i x-show="person.id === selected().occupant_id" class="bi bi-check2 text-primary-600"></i>
                                                    <span class="ms-auto shrink-0 font-mono text-[10px] text-slate-400" x-text="person.id_number"></span>
                                                </span>
                                                <span x-show="elsewhere(person).length" class="block truncate text-[11px] text-amber-600 dark:text-amber-400"
                                                      x-text="'Sits at ' + elsewhere(person).map((d) => d.code).join(', ')"></span>
                                            </button>
                                        </li>
                                    </template>
                                    <li x-show="seatOptions().length === 0" class="px-3 py-2 text-xs text-slate-400">No active employee matches.</li>
                                </ul>
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] text-slate-400" x-text="seatSaving ? 'Saving...' : 'Saved as soon as you pick someone.'"></span>
                                    <button type="button" class="text-xs text-slate-500 hover:underline" @click="closeSeating()" :disabled="seatSaving">Cancel</button>
                                </div>
                            </div>
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
                        <div x-show="!selected().isNew" class="flex flex-wrap gap-2 pt-1">
                            <a :href="selected().url" class="btn btn-outline btn-sm flex-1"><i class="bi bi-box-arrow-up-right"></i>Open Desk</a>
                            <a :href="selected().audit_url" class="btn btn-outline-primary btn-sm flex-1"><i class="bi bi-clipboard-check"></i>Audit</a>
                        </div>

                        {{-- Add copies (edit mode): a row of desks like this one, numbered on from the room's last desk --}}
                        <div x-show="editing && selected().x !== null" class="space-y-2 rounded-lg border border-slate-200 p-2.5 dark:border-slate-700">
                            <div class="text-xs font-semibold text-slate-700 dark:text-slate-200"><i class="bi bi-copy me-1 text-slate-400"></i>Add copies of this desk</div>
                            <div class="flex items-center gap-2">
                                <label for="copy-count" class="sr-only">How many copies</label>
                                <input type="number" id="copy-count" min="1" :max="maxCopies" x-model.number="copyCount" class="field-input w-16 px-2 py-1 text-sm"
                                       @keydown.enter.prevent="addCopies(selected())">
                                <div class="inline-flex overflow-hidden rounded-lg border border-slate-200 dark:border-slate-700" role="group" aria-label="Direction">
                                    <template x-for="dir in [['left', 'arrow-left'], ['up', 'arrow-up'], ['down', 'arrow-down'], ['right', 'arrow-right']]" :key="dir[0]">
                                        <button type="button" class="btn btn-sm btn-icon rounded-none"
                                                :class="copyDirection === dir[0] ? 'btn-primary' : 'text-slate-600 dark:text-slate-300'"
                                                :aria-pressed="copyDirection === dir[0]" :title="'Copies go ' + dir[0]"
                                                @click="copyDirection = dir[0]"><i class="bi" :class="'bi-' + dir[1]"></i></button>
                                    </template>
                                </div>
                            </div>
                            <label class="flex cursor-pointer select-none items-center gap-1.5 text-xs text-slate-600 dark:text-slate-300">
                                <input type="checkbox" class="h-4 w-4 rounded border-slate-300 accent-primary-600" x-model="copyGap">
                                Leave a gap between desks
                            </label>
                            <button type="button" class="btn btn-outline-primary btn-sm w-full" @click="addCopies(selected())">
                                <i class="bi bi-plus-lg"></i><span x-text="'Add ' + copyLabel()"></span>
                            </button>
                            <p class="text-[11px] text-slate-400">Copies get the same size and direction, nobody seated and no equipment. They're created when you save the layout.</p>
                        </div>
                        <button type="button" x-show="editing && selected().x !== null" class="btn btn-outline btn-sm w-full" @click="rotate(selected())">
                            <i class="bi bi-arrow-clockwise"></i><span x-text="selected().r === 90 ? 'Turn Horizontal' : 'Turn Vertical'"></span>
                        </button>
                        <button type="button" x-show="editing && selected().w !== null" class="btn btn-outline btn-sm w-full" @click="resetSize(selected())">
                            <i class="bi bi-arrows-angle-contract"></i><span x-text="matchSizes ? 'Reset All Sizes' : 'Reset Size'"></span>
                        </button>
                        <button type="button" x-show="editing && selected().x !== null" class="btn btn-outline-danger btn-sm w-full" @click="unplace(selected())">
                            <i class="bi" :class="selected().isNew ? 'bi-x-lg' : 'bi-box-arrow-in-down'"></i><span x-text="selected().isNew ? 'Discard New Desk' : 'Take Off the Plan'"></span>
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
                    <div x-show="colourBy === 'occupancy'" class="flex justify-between"><span class="text-slate-500 dark:text-slate-400">Audit due</span><span class="font-semibold tabular-nums text-amber-600 dark:text-amber-400" x-text="desks.filter(d => d.audit_due).length"></span></div>
                    <template x-if="colourBy === 'audit'">
                        <div class="space-y-2 border-t border-slate-200 pt-2 dark:border-slate-800">
                            <div class="flex justify-between"><span class="text-slate-500 dark:text-slate-400">Audited recently</span><span class="font-semibold tabular-nums text-emerald-600 dark:text-emerald-400" x-text="auditCount('ok')"></span></div>
                            <div class="flex justify-between"><span class="text-slate-500 dark:text-slate-400">Due soon</span><span class="font-semibold tabular-nums text-amber-600 dark:text-amber-400" x-text="auditCount('soon')"></span></div>
                            <div class="flex justify-between"><span class="text-slate-500 dark:text-slate-400">Overdue</span><span class="font-semibold tabular-nums text-red-600 dark:text-red-400" x-text="auditCount('overdue')"></span></div>
                            <div class="flex justify-between"><span class="text-slate-500 dark:text-slate-400">Never audited</span><span class="font-semibold tabular-nums text-red-600 dark:text-red-400" x-text="auditCount('never')"></span></div>
                        </div>
                    </template>
                    <div x-show="attentionCount()" class="flex justify-between">
                        <button type="button" class="text-orange-600 hover:underline dark:text-orange-400" @click="attentionOnly = true">Needs attention</button>
                        <span class="font-semibold tabular-nums text-orange-600 dark:text-orange-400" x-text="attentionCount()"></span>
                    </div>
                    <p class="border-t border-slate-200 pt-3 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400">Click a desk on the plan to see who sits there and what equipment is on it.</p>
                    <p x-show="unplaced().length" class="text-xs text-slate-500 dark:text-slate-400">
                        <span x-text="unplaced().length"></span> desk(s) are not on the plan yet.
                        <button type="button" class="font-semibold text-primary-600 hover:underline dark:text-primary-400" @click="startEditing()">Place them</button>
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Hover card: the equipment bound to the desk under the mouse --}}
    <template x-if="hoverDesk()">
        <div class="pointer-events-none fixed z-50 w-64 rounded-lg border border-slate-200 bg-white p-3 text-left shadow-xl dark:border-slate-700 dark:bg-slate-900"
             :style="hoverStyle()" @scroll.window="hover = null">
            <div class="flex items-center justify-between gap-2">
                <span class="truncate font-mono text-xs font-semibold text-slate-800 dark:text-slate-100" x-text="hoverDesk().code"></span>
                <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-medium"
                      :class="hoverDesk().status === 'assigned' ? 'bg-primary-100 text-primary-700 dark:bg-primary-900/40 dark:text-primary-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'"
                      x-text="{ assigned: 'Assigned', hotdesk: 'Hot desk', empty: 'Empty' }[hoverDesk().status]"></span>
            </div>
            <div class="mt-0.5 truncate text-xs text-slate-500 dark:text-slate-400" x-text="hoverDesk().occupant || 'Nobody / hot desk'"></div>
            <template x-for="warning in hoverDesk().warnings" :key="warning">
                <div class="mt-1 flex gap-1 text-[11px] leading-4 text-orange-700 dark:text-orange-300"><i class="bi bi-exclamation-triangle shrink-0"></i><span x-text="warning"></span></div>
            </template>

            <div class="mt-2 border-t border-slate-100 pt-2 dark:border-slate-800">
                <div class="mb-1 text-[10px] font-semibold uppercase tracking-wide text-slate-400" x-text="'Equipment (' + hoverDesk().assets.length + ')'"></div>
                <ul class="space-y-1">
                    <template x-for="item in hoverDesk().assets.slice(0, 8)" :key="item.tag">
                        <li class="flex items-center gap-2 text-xs">
                            <i class="bi shrink-0 text-slate-400" :class="'bi-' + item.icon"></i>
                            <span class="shrink-0 font-mono font-semibold text-slate-700 dark:text-slate-200" x-text="item.tag"></span>
                            <span class="min-w-0 truncate text-slate-500 dark:text-slate-400" x-text="item.name"></span>
                        </li>
                    </template>
                </ul>
                <p x-show="hoverDesk().assets.length > 8" class="mt-1 text-[11px] text-slate-400" x-text="'+ ' + (hoverDesk().assets.length - 8) + ' more. Click the desk to see all.'"></p>
                <p x-show="hoverDesk().assets.length === 0" class="text-xs text-slate-400">No equipment bound to this desk.</p>
            </div>

            <div x-show="hoverDesk().audit_issues || hoverDesk().audit_due" class="mt-2 text-[11px] font-medium"
                 :class="hoverDesk().audit_issues ? 'text-red-600 dark:text-red-400' : 'text-amber-600 dark:text-amber-400'"
                 x-text="hoverDesk().audit_issues ? 'Last audit found issues' : (hoverDesk().last_audit ? 'Audit due (last ' + hoverDesk().last_audit + ')' : 'Never audited')"></div>
        </div>
    </template>

    {{-- What follows the pointer while dragging --}}
    <div x-show="drag && drag.moved" x-cloak class="pointer-events-none fixed z-50 flex -translate-x-1/2 -translate-y-1/2 items-center justify-center whitespace-nowrap rounded-md border border-primary-700 bg-primary-600/90 px-1.5 py-0.5 font-mono text-[11px] font-semibold leading-4 text-white shadow-lg"
         :style="drag ? 'left:' + drag.x + 'px; top:' + drag.y + 'px;' + (drag.width ? ' width:' + drag.width + 'px; height:' + drag.height + 'px;' : '') + (drag.r ? ' rotate:' + drag.r + 'deg;' : '') : ''">
        <span class="min-w-0 truncate" x-text="drag ? drag.label : ''"></span>
    </div>
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
            resizing: null,
            hover: null,
            zoom: 1,
            zoomLevels: [1, 1.5, 2, 3],
            snapshot: null,
            matchSizes: true,
            employees: config.employees,
            seating: false,
            seatQuery: '',
            seatSaving: false,
            flashTimer: null,
            printUrl: config.printUrl,
            audit: config.audit,
            colourBy: 'occupancy',
            attentionOnly: false,
            maxCopies: config.maxCopies,
            copyCount: 3,
            copyDirection: 'right',
            copyGap: false,
            newSeq: 0,

            init() {
                this.$watch('selectedId', () => this.closeSeating());
                // These switches are remembered per browser; they're only a convenience.
                try {
                    if (localStorage.getItem('floorPlan.matchSizes') === '0') this.matchSizes = false;
                    if (localStorage.getItem('floorPlan.colourBy') === 'audit') this.colourBy = 'audit';
                } catch (e) {}
                this.$watch('matchSizes', (value) => {
                    try { localStorage.setItem('floorPlan.matchSizes', value ? '1' : '0'); } catch (e) {}
                });
                this.$watch('colourBy', (value) => {
                    try { localStorage.setItem('floorPlan.colourBy', value); } catch (e) {}
                });
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
            matches(desk) {
                const q = this.query();
                return (!q || desk.search.includes(q)) && (!this.attentionOnly || desk.warnings.length > 0);
            },
            attentionCount() { return this.desks.filter((d) => d.warnings.length).length; },

            /** ok, soon (due within audit.soon days), overdue or never; matches the server's "audit due". */
            auditState(desk) {
                if (desk.audit_age === null) return 'never';
                if (desk.audit_due) return 'overdue';
                return desk.audit_age >= this.audit.interval - this.audit.soon ? 'soon' : 'ok';
            },
            auditCount(state) { return this.desks.filter((d) => this.auditState(d) === state).length; },
            printHref() {
                const url = new URL(this.printUrl, window.location.href);
                url.searchParams.set('colour', this.colourBy);
                return url.toString();
            },
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
                const classes = this.colourBy === 'audit'
                    ? {
                        ok: 'border-emerald-700 bg-emerald-600 text-white',
                        soon: 'border-amber-500 bg-amber-300 text-amber-950',
                        overdue: 'border-red-700 bg-red-600 text-white',
                        never: 'border-dashed border-red-500 bg-white/90 text-red-600 dark:bg-slate-900/90 dark:text-red-400',
                    }[this.auditState(desk)]
                    : {
                        assigned: 'border-primary-700 bg-primary-600 text-white',
                        hotdesk: 'border-primary-500 bg-white text-primary-700 dark:bg-slate-900 dark:text-primary-300',
                        empty: 'border-dashed border-slate-400 bg-white/90 text-slate-500 dark:bg-slate-900/90 dark:text-slate-400',
                    }[desk.status];
                const state = [];
                if (desk.isNew) state.push('outline-2 outline-offset-2 outline-dashed outline-sky-500');
                if (this.selectedId === desk.id) state.push('z-20 ring-2 ring-amber-400 ring-offset-1 dark:ring-offset-slate-900' + (this.isSized(desk) ? '' : ' scale-110'));
                else state.push('z-10');
                if (!this.matches(desk)) state.push('opacity-25');
                if (this.drag && this.drag.moved && this.drag.id === desk.id) state.push('opacity-30');
                return classes + ' ' + state.join(' ');
            },
            showHover(event, desk) {
                // Mouse only: on touch screens tapping a desk opens the side panel instead.
                if (event.pointerType !== 'mouse' || this.drag || this.resizing) return;
                const rect = event.currentTarget.getBoundingClientRect();
                this.hover = { id: desk.id, x: rect.left + rect.width / 2, top: rect.top, bottom: rect.bottom };
            },
            hoverDesk() {
                if (!this.hover || this.drag || this.resizing) return null;
                return this.desks.find((d) => d.id === this.hover.id) || null;
            },
            hoverStyle() {
                const h = this.hover;
                const half = 128 + 8; // half the card's width plus a margin
                const x = Math.min(window.innerWidth - half, Math.max(half, h.x));
                // Above the desk, or below it when it's near the top of the window.
                return h.top > 240
                    ? 'left:' + x + 'px; top:' + (h.top - 8) + 'px; transform: translate(-50%, -100%);'
                    : 'left:' + x + 'px; top:' + (h.bottom + 8) + 'px; transform: translate(-50%, 0);';
            },
            isSized(desk) { return desk.w !== null && desk.h !== null; },
            markerStyle(desk) {
                let style = 'left:' + desk.x + '%; top:' + desk.y + '%;';
                if (this.isSized(desk)) style += ' width:' + desk.w + '%; height:' + desk.h + '%;';
                // w / h are the desk's own length and depth; a vertical desk is the same box turned around its centre.
                if (desk.r) style += ' rotate:' + desk.r + 'deg;';
                return style;
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

            zoomIn() { const i = this.zoomLevels.indexOf(this.zoom); if (i < this.zoomLevels.length - 1) this.zoom = this.zoomLevels[i + 1]; this.hover = null; },
            zoomOut() { const i = this.zoomLevels.indexOf(this.zoom); if (i > 0) this.zoom = this.zoomLevels[i - 1]; this.hover = null; },

            /** Every saved desk's spot. Copies not saved yet go separately (newDesks). */
            positions() { return this.desks.filter((d) => !d.isNew).map((d) => ({ id: d.id, x: d.x, y: d.y, w: d.w, h: d.h, r: d.r })); },
            newDesks() { return this.desks.filter((d) => d.isNew).map((d) => ({ key: d.id, room: d.room, x: d.x, y: d.y, w: d.w, h: d.h, r: d.r })); },

            copyLabel() {
                const n = this.clampCopies();
                return n === 1 ? '1 copy' : n + ' copies';
            },
            clampCopies() { return Math.min(this.maxCopies, Math.max(1, Math.round(Number(this.copyCount) || 1))); },
            /**
             * Lay copies of a desk out in a row from it, in copyDirection, each a desk's length (or
             * depth) on from the last, plus a quarter of that with copyGap on. Copies that would be
             * off the plan are left out. They're numbered on from the room's highest desk number,
             * as the server will number them, and created when the layout is saved.
             */
            addCopies(source) {
                if (!this.editing || source.x === null) return;
                const count = this.clampCopies();
                this.copyCount = count;
                const marker = document.getElementById('desk-marker-' + source.id);
                const plan = this.$refs.plan.getBoundingClientRect();
                if (!marker || !plan.width) return;

                // The desk's box on screen without the selected desk's zoom-in; turned on a vertical desk.
                const [across, down] = source.r === 90 ? [marker.offsetHeight, marker.offsetWidth] : [marker.offsetWidth, marker.offsetHeight];
                const spacing = this.copyGap ? 1.25 : 1;
                const [dx, dy] = {
                    right: [across * spacing / plan.width * 100, 0],
                    left: [-across * spacing / plan.width * 100, 0],
                    down: [0, down * spacing / plan.height * 100],
                    up: [0, -down * spacing / plan.height * 100],
                }[this.copyDirection];

                const room = source.room || null;
                let number = Math.max(0, ...this.desks.filter((d) => (d.room || null) === room).map((d) => d.number));
                const codeMarker = source.desk.replace(/\d+$/, ''); // "WS"
                const prefix = source.code.slice(0, source.code.length - source.desk.length);
                const round = (v) => Math.round(v * 1000) / 1000;

                const added = [];
                for (let i = 1; i <= count; i++) {
                    const x = round(source.x + dx * i);
                    const y = round(source.y + dy * i);
                    if (x < 0 || x > 100 || y < 0 || y > 100) break;
                    number++;
                    const deskCode = codeMarker + String(number).padStart(2, '0');
                    const code = prefix + deskCode;
                    const desk = {
                        id: 'new-' + (++this.newSeq), isNew: true,
                        code, desk: deskCode, number, room,
                        label: (room ? room + ' ' : '') + deskCode,
                        occupant: null, occupant_id: null, occupant_url: null,
                        status: 'empty', audit_due: true, audit_issues: false, last_audit: null, audit_age: null,
                        assets: [], warnings: [],
                        search: [code, room, deskCode].filter(Boolean).join(' ').toLowerCase(),
                        x, y, w: source.w, h: source.h, r: source.r,
                        url: null, audit_url: null,
                    };
                    this.desks.push(desk);
                    added.push(desk);
                }

                if (!added.length) {
                    this.error = 'There is no room for a copy that way: it would be off the edge of the plan. Pick another direction.';
                    return;
                }
                this.error = '';
                this.dirty = true;
                const what = added.length === 1 ? added[0].code : added[0].code + ' to ' + added[added.length - 1].code;
                this.flash('Added ' + what + (added.length < count ? ' (only ' + added.length + ' of ' + count + ' fit before the edge of the plan)' : '') + '. Save the layout to create them.');
                // Carry on from the last copy, so pressing Add again continues the row.
                this.select(added[added.length - 1].id);
            },
            startEditing() {
                this.snapshot = this.positions();
                this.editing = true;
                this.message = '';
                this.error = '';
            },
            cancelEditing() {
                // Copies that were never saved go away.
                if (this.selected()?.isNew) this.selectedId = null;
                this.desks = this.desks.filter((d) => !d.isNew);
                const byId = Object.fromEntries(this.snapshot.map((p) => [p.id, p]));
                this.desks.forEach((d) => Object.assign(d, { x: byId[d.id].x, y: byId[d.id].y, w: byId[d.id].w, h: byId[d.id].h, r: byId[d.id].r }));
                this.editing = false;
                this.dirty = false;
                this.error = '';
            },
            unplace(desk) {
                if (desk.isNew) {
                    // A copy that isn't saved yet is simply dropped.
                    if (this.selectedId === desk.id) this.selectedId = null;
                    this.desks = this.desks.filter((d) => d !== desk);
                } else {
                    Object.assign(desk, { x: null, y: null, w: null, h: null, r: 0 });
                }
                this.dirty = true;
            },
            /** Turn a desk between horizontal and vertical, around its centre. */
            rotate(desk) {
                if (!this.editing || desk.x === null) return;
                desk.r = desk.r === 90 ? 0 : 90;
                this.dirty = true;
                this.hover = null;
                this.select(desk.id);
            },
            resetSize(desk) {
                (this.matchSizes ? this.placed() : [desk]).forEach((d) => Object.assign(d, { w: null, h: null }));
                this.dirty = true;
            },
            /**
             * The size the other desks share, for a desk joining them while "Same size for all desks"
             * is on. Null when none of them has been resized.
             */
            sharedSize(desk) {
                if (!this.matchSizes) return null;
                const other = this.placed().find((d) => d.id !== desk.id && this.isSized(d));
                return other ? { w: other.w, h: other.h } : null;
            },

            /**
             * Follow the pointer until it's released; Esc stops early. onMove(e) runs on every move,
             * onEnd(e, cancelled) once at the end.
             */
            track(onMove, onEnd) {
                const move = (e) => onMove(e);
                const finish = (e, cancelled) => {
                    window.removeEventListener('pointermove', move);
                    window.removeEventListener('pointerup', up);
                    window.removeEventListener('pointercancel', up);
                    window.removeEventListener('keydown', key);
                    onEnd(e, cancelled);
                };
                const up = (e) => finish(e, e.type === 'pointercancel');
                const key = (e) => { if (e.key === 'Escape') { e.preventDefault(); finish(e, true); } };
                window.addEventListener('pointermove', move);
                window.addEventListener('pointerup', up);
                window.addEventListener('pointercancel', up);
                window.addEventListener('keydown', key);
            },
            inside(rect, e) {
                return rect.width > 0 && e.clientX >= rect.left && e.clientX <= rect.right && e.clientY >= rect.top && e.clientY <= rect.bottom;
            },

            startDrag(event, desk) {
                if (!this.editing || event.button > 0) return;
                event.preventDefault();
                this.hover = null;
                this.drag = { id: desk.id, label: desk.label, r: desk.x !== null ? desk.r : 0, startX: event.clientX, startY: event.clientY, x: event.clientX, y: event.clientY, moved: false, overCancel: false, width: null, height: null };

                // A resized desk keeps its on-screen size (and turn) while it's being dragged.
                const marker = this.isSized(desk) && document.getElementById('desk-marker-' + desk.id);
                const shared = !this.isSized(desk) && this.sharedSize(desk);
                if (marker) {
                    // The on-screen box of a vertical desk is its length and depth swapped.
                    const rect = marker.getBoundingClientRect();
                    this.drag.width = desk.r === 90 ? rect.height : rect.width;
                    this.drag.height = desk.r === 90 ? rect.width : rect.height;
                } else if (shared) {
                    // A desk joining the plan takes the others' size, so show it at that size.
                    const plan = this.$refs.plan.getBoundingClientRect();
                    this.drag.width = shared.w / 100 * plan.width;
                    this.drag.height = shared.h / 100 * plan.height;
                }

                this.track((e) => {
                    this.drag.x = e.clientX;
                    this.drag.y = e.clientY;
                    if (Math.hypot(e.clientX - this.drag.startX, e.clientY - this.drag.startY) > 4) this.drag.moved = true;
                    this.drag.overCancel = this.drag.moved && this.inside(this.$refs.cancelZone.getBoundingClientRect(), e);
                }, (e, cancelled) => this.endDrag(e, desk, cancelled));
            },
            endDrag(event, desk, cancelled) {
                const drag = this.drag;
                this.drag = null;
                // Esc, or let go over "Drop here to cancel": the desk stays where it was.
                if (!drag || !drag.moved || cancelled || drag.overCancel) return;

                const inside = (rect) => this.inside(rect, event);
                const plan = this.$refs.plan.getBoundingClientRect();
                const viewport = this.$refs.viewport.getBoundingClientRect();

                // Only the visible part of the plan counts when it's zoomed and scrolled.
                if (inside(plan) && inside(viewport)) {
                    const clamp = (v) => Math.min(100, Math.max(0, Math.round(v * 1000) / 1000));
                    desk.x = clamp((event.clientX - plan.left) / plan.width * 100);
                    desk.y = clamp((event.clientY - plan.top) / plan.height * 100);
                    const shared = !this.isSized(desk) && this.sharedSize(desk);
                    if (shared) Object.assign(desk, shared);
                    this.dirty = true;
                    this.select(desk.id);
                } else if (inside(this.$refs.tray.getBoundingClientRect()) && desk.x !== null) {
                    this.unplace(desk);
                }
            },
            /**
             * Drag the marker's bottom-right corner (as seen on screen). The top-left corner stays
             * put, so the centre (x, y) moves by half the change. On a vertical desk the length (w)
             * runs down the screen and the depth (h) across it. With "Same size for all desks" on,
             * every other desk on the plan takes the same length and depth around its own centre,
             * each keeping its own turn. Esc puts them all back.
             */
            startResize(event, desk) {
                if (!this.editing || event.button > 0) return;
                event.preventDefault();
                this.select(desk.id);

                const followers = this.matchSizes ? this.placed().filter((d) => d.id !== desk.id) : [];
                const originals = [desk, ...followers].map((d) => [d, { x: d.x, y: d.y, w: d.w, h: d.h }]);
                const wasDirty = this.dirty;
                const planRect = () => this.$refs.plan.getBoundingClientRect();
                const marker = document.getElementById('desk-marker-' + desk.id).getBoundingClientRect();
                const start = planRect();
                const left = (marker.left - start.left) / start.width * 100;
                const top = (marker.top - start.top) / start.height * 100;
                const round = (v) => Math.round(v * 1000) / 1000;
                const turned = desk.r === 90;
                this.resizing = { id: desk.id };

                this.track((e) => {
                    const plan = planRect();
                    // In screen pixels. At least about 28 x 18 px (length x depth), so the label stays
                    // readable, and no further than the plan's right and bottom edges.
                    const minLength = Math.max(28, plan.width / 200);
                    const minDepth = Math.max(18, plan.height / 200);
                    const [minAcross, minDown] = turned ? [minDepth, minLength] : [minLength, minDepth];
                    let across = Math.min((100 - left) / 100 * plan.width, Math.max(minAcross, e.clientX - plan.left - left / 100 * plan.width));
                    let down = Math.min((100 - top) / 100 * plan.height, Math.max(minDown, e.clientY - plan.top - top / 100 * plan.height));
                    // Length and depth are stored as a share of the plan's width and height, so neither may pass 100%.
                    if (turned) { down = Math.min(down, plan.width); across = Math.min(across, plan.height); }
                    const [length, depth] = turned ? [down, across] : [across, down];
                    Object.assign(desk, {
                        w: round(length / plan.width * 100),
                        h: round(depth / plan.height * 100),
                        x: round(left + across / plan.width * 50),
                        y: round(top + down / plan.height * 50),
                    });
                    followers.forEach((d) => Object.assign(d, { w: desk.w, h: desk.h }));
                    this.dirty = true;
                }, (e, cancelled) => {
                    this.resizing = null;
                    if (cancelled) {
                        originals.forEach(([d, original]) => Object.assign(d, original));
                        this.dirty = wasDirty;
                    }
                });
            },

            nudge(event, desk) {
                if (!this.editing || desk.x === null) return;
                if (event.key.toLowerCase() === 'r' && !event.ctrlKey && !event.metaKey && !event.altKey) {
                    event.preventDefault();
                    this.rotate(desk);
                    return;
                }
                const step = event.shiftKey ? NUDGE_BIG : NUDGE;
                const moves = { ArrowLeft: [-step, 0], ArrowRight: [step, 0], ArrowUp: [0, -step], ArrowDown: [0, step] }[event.key];
                if (!moves) return;
                event.preventDefault();
                desk.x = Math.min(100, Math.max(0, Math.round((desk.x + moves[0]) * 1000) / 1000));
                desk.y = Math.min(100, Math.max(0, Math.round((desk.y + moves[1]) * 1000) / 1000));
                this.dirty = true;
            },

            /**
             * PUT JSON to the server. Resolves with the response data, or throws with the first
             * validation message (or `failure` when there isn't one).
             */
            async put(url, body, failure) {
                let response;
                try {
                    response = await fetch(url, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify(body),
                    });
                } catch (e) {
                    throw new Error(failure);
                }
                const data = await response.json().catch(() => ({}));
                if (!response.ok) {
                    const first = data.errors ? Object.values(data.errors)[0] : null;
                    throw new Error((first && first[0]) || data.message || failure);
                }
                return data;
            },
            flash(text) {
                this.message = text;
                clearTimeout(this.flashTimer);
                this.flashTimer = setTimeout(() => { this.message = ''; }, 5000);
            },

            openSeating() {
                this.seatQuery = '';
                this.seating = true;
                this.error = '';
                this.$nextTick(() => this.$refs.seatSearch?.focus());
            },
            closeSeating() {
                if (this.seatSaving) return;
                this.seating = false;
                this.seatQuery = '';
            },
            /** Active employees (plus whoever sits here now) matching the search, at most 50. */
            seatOptions() {
                const desk = this.selected();
                const q = this.seatQuery.trim().toLowerCase();
                return this.employees
                    .filter((e) => e.active || (desk && e.id === desk.occupant_id))
                    .filter((e) => !q || e.name.toLowerCase().includes(q) || String(e.id_number || '').toLowerCase().includes(q))
                    .slice(0, 50);
            },
            /** The other desks this person sits at. */
            elsewhere(person) {
                return person.desks.filter((d) => d.id !== this.selectedId);
            },
            async assign(person) {
                const desk = this.selected();
                if (!desk || this.seatSaving) return;
                if ((person ? person.id : null) === desk.occupant_id) { this.closeSeating(); return; }

                const others = person ? this.elsewhere(person) : [];
                if (others.length) {
                    const codes = others.map((d) => d.code).join(', ');
                    if (!confirm(person.name + ' already sits at ' + codes + '.\n\nMove them to ' + desk.code + '? ' + codes + ' will become a hot desk.')) return;
                }

                this.seatSaving = true;
                this.error = '';
                try {
                    const data = await this.put(desk.occupant_url, { occupant_id: person ? person.id : null, move: others.length > 0 },
                        'The occupant could not be saved. Check your connection and try again.');

                    // Refresh the desks that changed, keeping any unsaved layout changes on them.
                    data.desks.forEach((fresh) => {
                        const local = this.desks.find((d) => d.id === fresh.id);
                        if (local) Object.assign(local, fresh, { x: local.x, y: local.y, w: local.w, h: local.h, r: local.r });
                    });
                    this.employees.forEach((e) => { e.desks = e.desks.filter((d) => d.id !== desk.id); });
                    if (person) person.desks = [{ id: desk.id, code: desk.code }];

                    this.seatSaving = false;
                    this.closeSeating();
                    this.flash(data.message);
                } catch (e) {
                    this.error = e.message;
                } finally {
                    this.seatSaving = false;
                }
            },

            async save() {
                this.saving = true;
                this.error = '';
                try {
                    const data = await this.put(this.saveUrl, { positions: this.positions(), new_desks: this.newDesks() },
                        'The layout could not be saved. Check your connection and try again.');
                    // Swap each copy for the desk the server created for it.
                    const created = data.created || {};
                    const selected = created[this.selectedId];
                    this.desks = this.desks.map((d) => (d.isNew && created[d.id]) || d);
                    if (selected) this.selectedId = selected.id;
                    this.dirty = false;
                    this.editing = false;
                    this.flash(data.message || 'Layout saved.');
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
