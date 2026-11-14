<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BodyRegionHazard extends Model
{
    protected $guarded = [];

    public function region(): BelongsTo
    {
        return $this->belongsTo(BodyRegion::class, 'body_region_id');
    }

    public function hazard(): BelongsTo
    {
        return $this->belongsTo(Hazard::class);
    }
}
