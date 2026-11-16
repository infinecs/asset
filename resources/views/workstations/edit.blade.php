@extends('layouts.app')
@section('title', 'Edit ' . $workstation->name)
@section('page-title', 'Edit Workstation')
@section('content')
<div class="flex justify-center">
    <div class="w-full max-w-lg">
        <div class="card">
            <div class="card-header"><h5 class="text-base font-semibold text-slate-900 dark:text-white">{{ $workstation->name }}</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('workstations.update', $workstation) }}">
                    @csrf
                    @method('PUT')
                    @include('workstations._form-fields')
                    @if($workstation->assets()->exists())
                    <p class="field-hint mt-3"><i class="bi bi-info-circle me-1"></i>Changing the location also updates the location of the equipment at this workstation.</p>
                    @endif
                    <div class="mt-4 flex gap-2">
                        <button class="btn btn-primary px-4"><i class="bi bi-check-lg"></i>Save</button>
                        <a href="{{ route('workstations.show', $workstation) }}" class="btn btn-outline px-4">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
