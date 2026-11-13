<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CabinReading extends Model
{
    protected $fillable = [
        'mission_id',
        'temperature',
        'pressure',
        'co2_level',
        'light_level',
        'noise_db',
        'microbial_count',
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

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }
}
