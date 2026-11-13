<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hazard extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'description',
        'icon',
        'color',
        'sort_order',
    ];

    public function risks(): HasMany
    {
        return $this->hasMany(Risk::class)->orderBy('name');
    }

    public function references(): HasMany
    {
        return $this->hasMany(Reference::class)->orderBy('published_at', 'desc');
    }
}
