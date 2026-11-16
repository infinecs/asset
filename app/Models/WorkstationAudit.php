<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One desk check. `missing` and `found` hold [{id, tag, name}] snapshots; `found` also records
 * where the record said the asset was (`from`).
 */
class WorkstationAudit extends Model
{
    protected $fillable = ['workstation_id', 'user_id', 'present_count', 'missing', 'found', 'notes'];

    protected $casts = [
        'present_count' => 'integer',
        'missing' => 'array',
        'found' => 'array',
    ];

    public function workstation(): BelongsTo
    {
        return $this->belongsTo(Workstation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hasDiscrepancies(): bool
    {
        return !empty($this->missing) || !empty($this->found);
    }
}
