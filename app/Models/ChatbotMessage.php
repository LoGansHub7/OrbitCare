<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatbotMessage extends Model
{
    public $timestamps = false;

    protected $fillable = ['client_id', 'role', 'message', 'intent', 'created_at'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function astronaut(): BelongsTo
    {
        return $this->belongsTo(User::class, 'astronaut_id');
    }
}
