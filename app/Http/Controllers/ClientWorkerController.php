<?php

namespace App\Http\Controllers;

use App\Http\Requests\SetClientWorkerRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class ClientWorkerController extends Controller
{
    public function setDefaultWorker(SetClientWorkerRequest $request, User $client): JsonResponse
    {
        if (!$client->hasRole(User::ROLE_CLIENT)) {
            abort(422, 'Указанный пользователь не является клиентом.');
        }

        $client->update(['default_worker_id' => $request->integer('worker_id')]);

        return response()->json($client->load('defaultWorker'));
    }
}
