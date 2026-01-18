<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Chat extends Model
{
    protected $fillable = ['name', 'type', 'description', 'avatar_path'];

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chat_participants');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    protected static function booted()
    {
        static::deleting(function (Chat $chat) {
            if ($chat->avatar_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($chat->avatar_path)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($chat->avatar_path);
            }
        });
    }
}