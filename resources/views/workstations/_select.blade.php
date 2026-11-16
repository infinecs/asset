{{--
    Workstation picker as a three-step cascade: office → room → desk. With many offices and desks a
    single list gets too long, so the office step is searchable and the room/desk steps stay short.
    The chosen desk is submitted as a hidden "workstation_id" input (empty = no workstation).

    Params: $workstationOptions (from WorkstationController::workstationOptions()), $selected (id or null),
            $selectId (optional id for the hidden input; it fires "change" when the desk changes),
            $noneLabel (default "No workstation"), $exclude (id to hide).

    Each picker element exposes el.wsPicker.reset() to clear it from other scripts.
--}}
@php
    $selected = $selected ?? null;
    $exclude = $exclude ?? null;
@endphp
<div data-ws-picker data-selected="{{ $selected }}" data-exclude="{{ $exclude }}" class="space-y-2">
    <input type="hidden" name="workstation_id" value="{{ $selected }}" @isset($selectId) id="{{ $selectId }}" @endisset>
    <select data-ws-level="location" class="field-input" aria-label="Office">
        <option value="">{{ $noneLabel ?? 'No workstation' }}</option>
    </select>
    <div class="grid grid-cols-2 gap-2">
        <select data-ws-level="room" class="field-input" aria-label="Room" disabled>
            <option value="">Room</option>
        </select>
        <select data-ws-level="desk" class="field-input" aria-label="Desk" disabled>
            <option value="">Desk</option>
        </select>
    </div>
    <p data-ws-code class="field-hint hidden"></p>
</div>

@once
@php
    // Office → room → desk tree for the picker. workstationOptions() is already sorted by office, room, number.
    $workstationTree = $workstationOptions->map(function ($group) {
        $location = $group->first()->location;

        return [
            'id' => $location?->id,
            'label' => ($location?->name ?? 'No location') . ($location?->code ? " ({$location->code})" : ''),
            'rooms' => $group->groupBy(fn ($ws) => (string) $ws->room)->map(fn ($desks, $room) => [
                'room' => $room,
                'desks' => $desks->map(fn ($ws) => [
                    'id' => $ws->id,
                    'code' => $ws->name,
                    'text' => $ws->desk_code
                        . ($ws->occupant ? ' — ' . $ws->occupant->name : '')
                        . ' · ' . ($ws->assets_count ? $ws->assets_count . ' ' . \Illuminate\Support\Str::plural('asset', $ws->assets_count) : 'empty'),
                ])->values(),
            ])->values(),
        ];
    })->values();
@endphp
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tree = @json($workstationTree);

        const option = function (value, text) {
            const el = document.createElement('option');
            el.value = value;
            el.textContent = text;
            return el;
        };

        document.querySelectorAll('[data-ws-picker]').forEach(function (root) {
            const exclude = root.dataset.exclude ? Number(root.dataset.exclude) : null;
            const locations = tree
                .map(function (loc) {
                    const rooms = loc.rooms
                        .map(function (room) {
                            return { room: room.room, desks: room.desks.filter(function (d) { return d.id !== exclude; }) };
                        })
                        .filter(function (room) { return room.desks.length > 0; });
                    return { id: loc.id, label: loc.label, rooms: rooms };
                })
                .filter(function (loc) { return loc.rooms.length > 0; });

            const input = root.querySelector('input[name="workstation_id"]');
            const locationSelect = root.querySelector('[data-ws-level="location"]');
            const roomSelect = root.querySelector('[data-ws-level="room"]');
            const deskSelect = root.querySelector('[data-ws-level="desk"]');
            const codeHint = root.querySelector('[data-ws-code]');

            let currentLocation = null;
            let currentRoom = null;

            locations.forEach(function (loc) {
                locationSelect.appendChild(option(loc.id, loc.label));
            });

            const showCode = function (desk) {
                codeHint.textContent = desk ? 'Selected: ' + desk.code : '';
                codeHint.classList.toggle('hidden', !desk);
            };

            const setValue = function (desk) {
                const value = desk ? String(desk.id) : '';
                showCode(desk);
                if (input.value !== value) {
                    input.value = value;
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                }
            };

            // Room and desk are required once an office is picked, so a half-made choice can't be
            // submitted as "no workstation".
            const fillRooms = function (loc) {
                currentLocation = loc;
                roomSelect.replaceChildren(option('', loc ? 'Choose room' : 'Room'));
                (loc ? loc.rooms : []).forEach(function (room, i) {
                    roomSelect.appendChild(option(i, (room.room || 'No room') + ' (' + room.desks.length + ')'));
                });
                roomSelect.disabled = !loc;
                roomSelect.required = !!loc;
            };

            const fillDesks = function (room) {
                currentRoom = room;
                deskSelect.replaceChildren(option('', room ? 'Choose desk' : 'Desk'));
                (room ? room.desks : []).forEach(function (desk) {
                    deskSelect.appendChild(option(desk.id, desk.text));
                });
                deskSelect.disabled = !room;
                deskSelect.required = !!room;
            };

            const selectLocation = function (loc) {
                fillRooms(loc);
                // Skip the room step when the office has only one room.
                if (loc && loc.rooms.length === 1) {
                    roomSelect.value = '0';
                    fillDesks(loc.rooms[0]);
                } else {
                    fillDesks(null);
                }
            };

            // Finds the desk matching test() among the desks this picker offers.
            const lookup = function (test) {
                for (const loc of locations) {
                    for (let i = 0; i < loc.rooms.length; i++) {
                        const desk = loc.rooms[i].desks.find(test);
                        if (desk) {
                            return { location: loc, roomIndex: i, room: loc.rooms[i], desk: desk };
                        }
                    }
                }
                return null;
            };

            let tomSelect = null;

            const show = function (found) {
                if (tomSelect) {
                    tomSelect.setValue(String(found.location.id), true);
                } else {
                    locationSelect.value = String(found.location.id);
                }
                fillRooms(found.location);
                roomSelect.value = String(found.roomIndex);
                fillDesks(found.room);
                deskSelect.value = String(found.desk.id);
            };

            // Preselect the current workstation (edit form, or after a validation error).
            const selectedId = root.dataset.selected ? Number(root.dataset.selected) : null;
            const preselected = selectedId ? lookup(function (d) { return d.id === selectedId; }) : null;
            if (preselected) {
                show(preselected);
                showCode(preselected.desk);
            }

            if (typeof TomSelect !== 'undefined' && locations.length > 0) {
                tomSelect = new TomSelect(locationSelect, {
                    create: false,
                    maxItems: 1,
                    allowEmptyOption: true,
                    searchField: ['text'],
                    placeholder: 'Search office...',
                    sortField: [{ field: '$score' }, { field: '$order' }],
                });
            }

            locationSelect.addEventListener('change', function () {
                const id = Number(locationSelect.value);
                selectLocation(locations.find(function (loc) { return loc.id === id; }) || null);
                setValue(null);
            });

            roomSelect.addEventListener('change', function () {
                fillDesks(currentLocation && roomSelect.value !== '' ? currentLocation.rooms[Number(roomSelect.value)] : null);
                setValue(null);
            });

            deskSelect.addEventListener('change', function () {
                const id = Number(deskSelect.value);
                setValue(currentRoom ? currentRoom.desks.find(function (d) { return d.id === id; }) || null : null);
            });

            root.wsPicker = {
                findById: function (id) {
                    return lookup(function (d) { return d.id === Number(id); });
                },
                // Accepts the code with or without the INF prefix, ignoring case, spaces and dashes.
                findByCode: function (text) {
                    const code = String(text).toUpperCase().replace(/[\s-]/g, '');
                    return code ? lookup(function (d) { return d.code === code || d.code === 'INF' + code; }) : null;
                },
                select: function (found) {
                    show(found);
                    setValue(found.desk);
                },
                reset: function () {
                    if (tomSelect) {
                        tomSelect.setValue('', true);
                    } else {
                        locationSelect.value = '';
                    }
                    fillRooms(null);
                    fillDesks(null);
                    setValue(null);
                },
            };
        });
    });
</script>
@endpush
@endonce
