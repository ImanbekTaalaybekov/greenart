<?php

namespace App\Http\Controllers;

use App\Http\Resources\ChatMessageResource;
use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\ChatMessageRead;
use App\Services\PhotoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Events\MessageSent;


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
            ->with(['sender', 'reads'])
            ->latest()
            ->paginate(50);

        return ChatMessageResource::collection($messages)->response();
    }

    public function markRead(Request $request, Chat $chat): JsonResponse
    {
        $user = $request->user();

        if (!$chat->participants()->where('user_id', $user->id)->exists()) {
            abort(403, 'Нет доступа к чату.');
        }

        $unreadMessages = $chat->messages()
            ->where('user_id', '!=', $user->id)
            ->whereDoesntHave('reads', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->pluck('id');

        if ($unreadMessages->isEmpty()) {
             return response()->json(['status' => 'nothing_to_update']);
        }

        $records = $unreadMessages->map(fn($id) => [
            'chat_message_id' => $id,
            'user_id' => $user->id,
            'read_at' => now(),
        ])->toArray();

        ChatMessageRead::insert($records);

        return response()->json(['status' => 'marked_read', 'count' => count($records)]);
    }

    public function show(Request $request, Chat $chat): JsonResponse
    {
        $user = $request->user();

        if (!$chat->participants()->where('user_id', $user->id)->exists()) {
            abort(403, 'Вы не являетесь участником этого чата.');
        }

        $chatData = [
            'id'          => $chat->id,
            'name'        => $chat->name,
            'type'        => $chat->type,
            'description' => $chat->description,
            'avatar'      => $chat->avatar_path ? asset('storage/' . $chat->avatar_path) : null,
            'created_at'  => $chat->created_at,
            'updated_at'  => $chat->updated_at,
        ];

        if ($user->hasRole(\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_WORKER)) {
            $chat->load('participants');
            
            $chatData['participants'] = $chat->participants->map(function ($participant) {
                return [
                    'id'   => $participant->id,
                    'name' => $participant->name,
                    'role' => $participant->role,
                ];
            });
        }

        return response()->json($chatData);
    }

    public function update(Request $request, Chat $chat, PhotoService $photoService): JsonResponse
    {
        $this->authorize('create', Chat::class); 

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'avatar' => ['nullable', 'image', 'max:5120'],
        ]);

        $chat->update($request->only(['name', 'description']));
        
        if ($request->hasFile('avatar')) {
             if ($chat->avatar_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($chat->avatar_path)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($chat->avatar_path);
            }
            
            $file = $request->file('avatar');
            $path = $file->store("chats/{$chat->id}/avatar", 'public');
            $chat->update(['avatar_path' => $path]);
        }

        return response()->json($chat);
    }

    public function sendMessage(Request $request, Chat $chat): JsonResponse
    {
        $user = $request->user();
        if (!$chat->participants()->where('user_id', $user->id)->exists()) {
            abort(403, 'Нет доступа к чату.');
        }

        $data = $request->validate([
            'message' => ['nullable', 'string', 'max:5000'],
            'file' => ['nullable', 'file', 'max:10240'],
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

        MessageSent::dispatch($message);

        return response()->json(new ChatMessageResource($message));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Chat::class);
        
        $data = $request->validate([
            'title' => ['required', 'string'],
            'participants' => ['required', 'array'],
            'participants.*' => ['exists:users,id'],
            'type' => ['string', 'in:general,order'],
            'description' => ['nullable', 'string', 'max:1000'],
            'avatar' => ['nullable', 'image', 'max:5120'],
        ]);

        $chat = DB::transaction(function () use ($data, $request) {
            $chat = Chat::create([
                'name' => $data['title'],
                'type' => $data['type'] ?? 'order',
                'description' => $data['description'] ?? null,
            ]);

            $participants = array_unique([...$data['participants'], $request->user()->id]);
            $chat->participants()->sync($participants);
            
            if ($request->hasFile('avatar')) {
                $file = $request->file('avatar');
                $path = $file->store("chats/{$chat->id}/avatar", 'public');
                $chat->update(['avatar_path' => $path]);
            }

            return $chat;
        });

        return response()->json($chat);
    }

    public function destroy(Chat $chat): JsonResponse
    {
        $this->authorize('delete', $chat);

        $chat->delete();

        return response()->json(['message' => 'Чат и все сообщения удалены']);
    }
}