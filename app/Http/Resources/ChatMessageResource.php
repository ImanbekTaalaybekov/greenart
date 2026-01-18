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
    $isMe = $currentUser->id === $sender->id;

    $status = 'sent';
    if ($isMe && $this->resource->relationLoaded('reads') && $this->resource->reads->isNotEmpty()) {
        $status = 'read';
    }

    $senderName = $sender->name;
    if ($currentUser->hasRole(User::ROLE_CLIENT) && !$sender->hasRole(User::ROLE_CLIENT)) {
        $senderName = 'Менеджер GreenArt';
    }

    return [
        'id' => $this->id,
        'message' => $this->message,
        'status' => $status,
        'is_me' => $isMe,
        'sender' => [
            'id' => $sender->id,
            'role' => $currentUser->hasRole(User::ROLE_CLIENT) ? 'manager' : $sender->role,
            'name' => $senderName,
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
