<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthReading extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['observed_at' => 'datetime', 'value' => 'decimal:2'];
    }

    public function metric(): BelongsTo
    {
        return $this->belongsTo(MetricDefinition::class, 'metric_definition_id');
    }
}
