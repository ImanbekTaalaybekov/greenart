<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;


Broadcast::channel('chat.{id}', function ($user, $id) {
    return DB::table('chat_participants')
        ->where('chat_id', $id)
        ->where('user_id', $user->id)
        ->exists();
});
