<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutonomyEvent extends Model
{
    protected $fillable = [
        'crew_profile_id',
        'mission_id',
        'event_type',
        'comms_delay_seconds',
        'handled_onboard',
        'escalated_to_earth',
        'description',
        'resolution',
        'is_simulated',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'handled_onboard' => 'boolean',
            'escalated_to_earth' => 'boolean',
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
