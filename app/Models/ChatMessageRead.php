<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatMessageRead extends Model
{
    public $timestamps = false;
    protected $fillable = ['chat_message_id', 'user_id', 'read_at'];
}