<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BodyRegionGuidance extends Model
{
    protected $table = 'body_region_guidance';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'self_care_steps' => 'array',
            'red_flags' => 'array',
            'sources' => 'array',
        ];
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(BodyRegion::class, 'body_region_id');
    }
}
