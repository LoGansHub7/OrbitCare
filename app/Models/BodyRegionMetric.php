<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BodyRegionMetric extends Model
{
    protected $guarded = [];

    public function region(): BelongsTo
    {
        return $this->belongsTo(BodyRegion::class, 'body_region_id');
    }

    public function metricDefinition(): BelongsTo
    {
        return $this->belongsTo(MetricDefinition::class);
    }
}
