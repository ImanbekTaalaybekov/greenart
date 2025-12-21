<?php

namespace App\Http\Controllers;

use App\Http\Requests\SetClientWorkerRequest;
use App\Models\Chat;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class ClientWorkerController extends Controller
{
    public function setDefaultWorker(SetClientWorkerRequest $request, User $client): JsonResponse
    {
        if (!$client->hasRole(User::ROLE_CLIENT)) {
            abort(422, 'Указанный пользователь не является клиентом.');
        }

        $workerId = $request->integer('worker_id');

        $client->update(['default_worker_id' => $workerId]);
        $chat = Chat::where('type', 'client_group')
            ->whereHas('participants', function ($q) use ($client) {
                $q->where('user_id', $client->id);
            })->first();

        if (!$chat) {
            $chat = Chat::create([
                'title' => 'Чат объекта: ' . $client->name,
                'type'  => 'client_group',
            ]);

            $chat->participants()->attach($client->id);
            
            $chat->participants()->attach(auth()->id());
        }

        if (!$chat->participants()->where('user_id', $workerId)->exists()) {
            $chat->participants()->attach($workerId);
        }
        
        return response()->json($client->load('defaultWorker'));
    }
}