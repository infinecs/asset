{{-- Last-audit cell for a workstation (needs latestAudit loaded). --}}
@if($workstation->latestAudit)
<span class="{{ $workstation->isAuditDue() ? 'text-amber-600 dark:text-amber-400' : 'text-slate-500 dark:text-slate-400' }}" title="{{ $workstation->latestAudit->created_at->format('d M Y, h:i A') }}">
    @if($workstation->isAuditDue())<i class="bi bi-exclamation-circle me-1"></i>@endif{{ $workstation->latestAudit->created_at->format('d M Y') }}
</span>
@if($workstation->latestAudit->hasDiscrepancies())
<span class="badge badge-warning ms-1" title="The last audit found missing or unrecorded equipment">Issues</span>
@endif
@else
<span class="badge badge-warning">Never</span>
@endif
