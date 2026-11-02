<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrewProfile extends Model
{
    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function checkins(): HasMany
    {
        return $this->hasMany(DailyCheckin::class);
    }

    public function readings(): HasMany
    {
        return $this->hasMany(HealthReading::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    public function baselineProfiles(): HasMany
    {
        return $this->hasMany(BaselineProfile::class);
    }

    public function medicalOfficers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'care_assignments', 'crew_profile_id', 'medical_officer_id')
            ->withTimestamps();
    }

    public function missionDay(): int
    {
        return (int) $this->mission->starts_on->copy()->startOfDay()->diffInDays(today()) + 1;
    }
}
