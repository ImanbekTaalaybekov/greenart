<?php

namespace App\Http\Controllers;

use App\Http\Resources\ChatMessageResource;
use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $chats = Chat::whereHas('participants', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
        ->withCount(['messages'])
        ->orderByDesc('updated_at') 
        ->get();

        return response()->json($chats);
    }

    public function messages(Request $request, Chat $chat): JsonResponse
    {
        if (!$chat->participants()->where('user_id', $request->user()->id)->exists()) {
            abort(403, 'Вы не являетесь участником этого чата.');
        }

        $messages = $chat->messages()
            ->with('sender')
            ->latest()
            ->paginate(50);

        return ChatMessageResource::collection($messages)->response();
    }

    public function sendMessage(Request $request, Chat $chat): JsonResponse
    {
        $user = $request->user();
        if (!$chat->participants()->where('user_id', $user->id)->exists()) {
            abort(403, 'Нет доступа к чату.');
        }

        $data = $request->validate([
            'message' => ['nullable', 'string', 'max:5000'],
            'file' => ['nullable', 'file', 'max:10240'], // 10MB
        ]);

        if (empty($data['message']) && empty($data['file'])) {
            abort(422, 'Сообщение или файл обязательны.');
        }

        $messageData = [
            'chat_id' => $chat->id,
            'user_id' => $user->id,
            'message' => $data['message'] ?? null,
        ];

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store("chats/{$chat->id}", 'public');
            $messageData['file_path'] = $path;
            $messageData['file_original_name'] = $file->getClientOriginalName();
            $messageData['file_type'] = $file->getMimeType();
        }

        $message = ChatMessage::create($messageData);
        
        $chat->touch();

        return response()->json(new ChatMessageResource($message));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Chat::class); 
        $data = $request->validate([
            'title' => ['required', 'string'],
            'participants' => ['required', 'array'],
            'participants.*' => ['exists:users,id'],
            'type' => ['string', 'in:general,order']
        ]);

        $chat = DB::transaction(function () use ($data, $request) {
            $chat = Chat::create([
                'name' => $data['title'],
                'type' => $data['type'] ?? 'order'
            ]);

            $participants = array_unique([...$data['participants'], $request->user()->id]);
            $chat->participants()->sync($participants);

            return $chat;
        });

        return response()->json($chat);
    }
}