<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Risk extends Model
{
    protected $fillable = [
        'hazard_id',
        'name',
        'summary',
        'source_url',
    ];

    public function hazard(): BelongsTo
    {
        return $this->belongsTo(Hazard::class);
    }
}
