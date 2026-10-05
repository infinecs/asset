@props(['id', 'maxWidth' => 'max-w-lg'])
<div
    x-data="{ open: false }"
    x-on:open-modal.window="if ($event.detail === '{{ $id }}') open = true"
    x-on:keydown.escape.window="open = false"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50"
    role="dialog"
    aria-modal="true"
>
    <div class="fixed inset-0 flex min-h-screen items-center justify-center p-4">
        <div
            class="fixed inset-0 bg-slate-900/50"
            x-show="open"
            x-transition.opacity
            @click="open = false"
        ></div>
        <div
            class="relative w-full {{ $maxWidth }} rounded-xl bg-white shadow-xl dark:bg-slate-900"
            x-show="open"
            x-transition
            @click.stop
        >
            {{ $slot }}
        </div>
    </div>
</div>
