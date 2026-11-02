<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BaselineProfile extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'baseline_value' => 'decimal:2',
            'reference_period_start' => 'date',
            'reference_period_end' => 'date',
        ];
    }

    public function crewProfile(): BelongsTo
    {
        return $this->belongsTo(CrewProfile::class);
    }

    public function metric(): BelongsTo
    {
        return $this->belongsTo(MetricDefinition::class, 'metric_definition_id');
    }
}
