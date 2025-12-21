<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Message;
use App\Http\Resources\MessageResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    public function index()
    {
        $chats = Auth::user()->chats()
            ->with(['lastMessage'])
            ->get()
            ->sortByDesc(fn($chat) => $chat->lastMessage->created_at ?? $chat->created_at)
            ->values();

        return response()->json($chats);
    }

    public function show($id)
    {
        $chat = Chat::findOrFail($id);

        if (!$chat->participants->contains(Auth::id())) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $messages = $chat->messages()
            ->with('sender')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return MessageResource::collection($messages);
    }

    public function sendMessage(Request $request, $id)
    {
        $request->validate([
            'content' => 'nullable|string',
            'file' => 'nullable|file|max:10240',
        ]);

        $chat = Chat::findOrFail($id);
        
        if (!$chat->participants->contains(Auth::id())) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $path = null;
        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('chat_files', 'public');
        }

        $message = Message::create([
            'chat_id' => $chat->id,
            'user_id' => Auth::id(),
            'content' => $request->content,
            'attachment_path' => $path,
        ]);

        return new MessageResource($message);
    }
}