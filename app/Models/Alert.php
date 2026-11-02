<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Alert extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'evidence' => 'array',
            'first_detected_at' => 'datetime',
            'last_evaluated_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function crewProfile(): BelongsTo
    {
        return $this->belongsTo(CrewProfile::class);
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(AlertRule::class, 'alert_rule_id');
    }

    public function followups(): HasMany
    {
        return $this->hasMany(FollowUp::class);
    }
}
