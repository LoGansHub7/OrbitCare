<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhysicalReading extends Model
{
    protected $fillable = [
        'crew_profile_id',
        'mission_id',
        'exercise_minutes',
        'muscle_index',
        'bone_index',
        'balance_score',
        'symptoms',
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
