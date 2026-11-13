<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RadiationReading extends Model
{
    protected $fillable = [
        'crew_profile_id',
        'mission_id',
        'dose_rate',
        'neutron_dose',
        'other_dose',
        'cumulative_dose',
        'is_simulated',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'is_simulated' => 'boolean',
            'recorded_at' => 'datetime',
        ];
    }

    public function crewProfile(): BelongsTo
    {
        return $this->belongsTo(CrewProfile::class);
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }
}
