<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetMovement extends Model
{
    protected $fillable = [
        'asset_id', 'from_workstation_id', 'to_workstation_id', 'from_label', 'to_label', 'user_id', 'notes',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function fromWorkstation(): BelongsTo
    {
        return $this->belongsTo(Workstation::class, 'from_workstation_id');
    }

    public function toWorkstation(): BelongsTo
    {
        return $this->belongsTo(Workstation::class, 'to_workstation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
