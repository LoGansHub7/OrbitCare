<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WellbeingCheckin extends Model
{
    protected $table = 'wellbeing_checkins';

    protected $fillable = [
        'crew_profile_id',
        'mission_id',
        'mood',
        'stress',
        'sleep_hours',
        'sleep_quality',
        'crew_interaction_minutes',
        'workload',
        'notes',
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
