<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $currentUser = $request->user();
        $sender = $this->sender;

        $senderName = $sender->name;
        $isMe = $currentUser->id === $sender->id;

        if ($currentUser->hasRole(User::ROLE_CLIENT) && !$sender->hasRole(User::ROLE_CLIENT)) {
            $senderName = 'Менеджер GreenArt'; 
        }

        return [
            'id' => $this->id,
            'message' => $this->message,
            'is_me' => $isMe,
            'sender' => [
                'id' => $sender->id, 
                'role' => $currentUser->hasRole(User::ROLE_CLIENT) ? 'manager' : $sender->role, 
            ],
            'file' => $this->file_path ? [
                'url' => asset('storage/' . $this->file_path),
                'name' => $this->file_original_name,
                'type' => $this->file_type,
            ] : null,
            'created_at' => $this->created_at,
        ];
    }
}
