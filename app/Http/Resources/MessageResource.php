<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class MessageResource extends JsonResource
{
    public function toArray($request)
    {
        $user = Auth::user();
        $sender = $this->sender;

        $name = trim($sender->name . ' ' . $sender->surname);
        $role = $sender->role;
        $avatar = $sender->avatar;

        if ($user && $user->role === 'client' && in_array($role, ['admin', 'worker'])) {
            $name = 'Green Art'; 

        }

        return [
            'id' => $this->id,
            'content' => $this->content,
            'attachment' => $this->attachment_path ? url('storage/' . $this->attachment_path) : null,
            'created_at' => $this->created_at->format('Y-m-d H:i'),
            'is_me' => $user->id === $sender->id,
            'sender' => [
                'id' => $sender->id,
                'name' => $name, 
                'role' => $role,
                'avatar' => $avatar ? url('storage/' . $avatar) : null,
            ],
        ];
    }
}