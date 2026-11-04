<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyCheckin extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'mission_date' => 'date',
            'submitted_at' => 'datetime',
            'sleep_hours' => 'decimal:1',
            'symptom_tags' => 'array',
        ];
    }

    public function symptomLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SymptomLog::class);
    }
}
