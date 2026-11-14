<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BodyRegion extends Model
{
    protected $guarded = [];

    public function metrics(): HasMany
    {
        return $this->hasMany(BodyRegionMetric::class)->orderBy('sort_order');
    }

    public function hazards(): HasMany
    {
        return $this->hasMany(BodyRegionHazard::class)->orderBy('sort_order');
    }

    public function guidance(): HasMany
    {
        return $this->hasMany(BodyRegionGuidance::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(BodyRegionIncident::class);
    }

    public function guidanceFor(string $statusLevel): ?BodyRegionGuidance
    {
        return $this->guidance->firstWhere('status_level', $statusLevel);
    }
}
