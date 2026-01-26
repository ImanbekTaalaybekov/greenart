<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class ChatMessage extends Model
{
    protected $fillable = [
        'chat_id', 'user_id', 'message', 
        'file_path', 'file_original_name', 'file_type'
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }
    
    public function reads(): HasMany
    {
        return $this->hasMany(ChatMessageRead::class);
    }
    
    protected static function booted()
    {
        static::deleting(function (ChatMessage $msg) {
            if ($msg->file_path && Storage::disk('public')->exists($msg->file_path)) {
                Storage::disk('public')->delete($msg->file_path);
            }
        });
    }
}