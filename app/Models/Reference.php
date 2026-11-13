<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reference extends Model
{
    protected $fillable = [
        'hazard_id',
        'title',
        'publisher',
        'url',
        'summary',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'date',
        ];
    }

    public function hazard(): BelongsTo
    {
        return $this->belongsTo(Hazard::class);
    }
}
