<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BodyRegionIncident extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'associated_symptoms' => 'array',
            'answers' => 'array',
            'recorded_at' => 'datetime',
        ];
    }

    public function crewProfile(): BelongsTo
    {
        return $this->belongsTo(CrewProfile::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(BodyRegion::class, 'body_region_id');
    }
}
