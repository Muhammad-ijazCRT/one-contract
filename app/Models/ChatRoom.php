<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatRoom extends Model
{
    protected $fillable = ['slug', 'name', 'type'];

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }
}
