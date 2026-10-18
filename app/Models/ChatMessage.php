<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    protected $fillable = [
        'chat_room_id',
        'user_id',
        'body',
        'is_pinned',
        'reaction_emoji',
        'reaction_count',
        'reactions',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'reaction_count' => 'integer',
            'reactions' => 'array',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(ChatRoom::class, 'chat_room_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
