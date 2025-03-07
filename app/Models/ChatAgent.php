<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatAgent extends Model
{
    protected $fillable = ['user_id','status', 'last_active_at'];

    protected $casts = [
        'last_active_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function chatSessions(): HasMany
    {
        return $this->hasMany(ChatSession::class, 'agent_id');
    }

    public function isAvailable(): bool
    {
        return $this->status === 'online' && $this->chatSessions()
            ->where('status', 'active')
            ->count() < config('chat.max_concurrent_sessions', 3);
    }
}
