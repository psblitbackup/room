<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatSession extends Model
{
    protected $guarded = [];

    protected $casts = [
        'ended_at' => 'datetime',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(ChatAgent::class, 'agent_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }
}
