<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlertEvidence extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['snapshot' => 'array'];
    }
}
