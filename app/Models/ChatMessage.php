<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ChatMessage extends Model
{
    protected $fillable = [
        'message',
        'chat_session_id',
        'sender_type',
        'sender_id',
        'attachment_path',
        'attachment_type',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function chatSession(): BelongsTo
    {
        return $this->belongsTo(ChatSession::class);
    }

    public function sender(): MorphTo
    {
        // Handle the 'visitor' case
        if ($this->sender_type === 'visitor') {
            return $this->morphTo('sender', null, null, 'chat_session_id');
        }

        return $this->morphTo();
    }
}
