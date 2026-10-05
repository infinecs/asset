{{-- Levels switch for an org chart page. Expects $maxDepth (null = all levels). --}}
<div class="inline-flex overflow-hidden rounded-lg border border-slate-200 dark:border-slate-700" role="group" aria-label="Levels shown">
    @foreach([1 => '1 level', 2 => '2 levels', 3 => '3 levels', null => 'All'] as $levels => $label)
    @php $active = ($maxDepth ?? null) === ($levels ?: null); @endphp
    <a href="{{ request()->fullUrlWithQuery(['levels' => $levels ?: null]) }}"
       class="px-3 py-1.5 text-xs font-medium no-underline {{ $active ? 'bg-primary-600 text-white' : 'text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800' }} {{ !$loop->first ? 'border-l border-slate-200 dark:border-slate-700' : '' }}"
       @if($active) aria-current="true" @endif>{{ $label }}</a>
    @endforeach
</div>
