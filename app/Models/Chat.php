<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'type'];

    public function participants()
    {
        return $this->belongsToMany(User::class, 'chat_user');
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }
    
    // Для списка чатов (последнее сообщение)
    public function lastMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }
}