<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\Order;
use App\Models\OrderReport;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function clients(Request $request): JsonResponse
    {
        $clients = User::query()
            ->where('role', User::ROLE_CLIENT)
            ->orderBy('name')
            ->paginate(20);

        return UserResource::collection($clients)->response();
    }

    public function workers(Request $request): JsonResponse
    {
        $workers = User::query()
            ->where('role', User::ROLE_WORKER)
            ->orderBy('name')
            ->paginate(20);

        return UserResource::collection($workers)->response();
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'login' => ['required', 'string', 'max:255', 'unique:users,login'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:255', 'unique:users,phone'],
            'role' => ['required', 'string', Rule::in([
                User::ROLE_CLIENT,
                User::ROLE_WORKER,
                User::ROLE_ADMIN,
                User::ROLE_ACCOUNTANT,
            ])],
            'default_worker_id' => [
                'nullable',
                'exists:users,id',
                Rule::prohibitedUnless('role', User::ROLE_CLIENT),
            ],
            'salary' => [
                'nullable',
                'numeric',
                'min:0',
                Rule::prohibitedUnless('role', User::ROLE_WORKER),
            ],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'login' => $data['login'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'role' => $data['role'],
            'default_worker_id' => $data['default_worker_id'] ?? null,
            'salary' => $data['salary'] ?? null,
            'password' => Hash::make($data['password']),
        ]);

        return response()->json([
            'user' => new UserResource($user),
        ], 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'login' => ['sometimes', 'string', 'max:255', Rule::unique('users', 'login')->ignore($user->id)],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique('users', 'phone')->ignore($user->id)],
            'role' => ['sometimes', 'string', Rule::in([
                User::ROLE_CLIENT,
                User::ROLE_WORKER,
                User::ROLE_ADMIN,
                User::ROLE_ACCOUNTANT,
            ])],
            'default_worker_id' => [
                'sometimes',
                'nullable',
                'exists:users,id',
                function ($attribute, $value, $fail) use ($request, $user) {
                    $role = $request->input('role', $user->role);
                    if ($role !== User::ROLE_CLIENT) {
                        $fail('default_worker_id доступен только для клиентов.');
                    }
                },
            ],
            'salary' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) use ($request, $user) {
                    $role = $request->input('role', $user->role);
                    if ($role !== User::ROLE_WORKER) {
                        $fail('salary доступен только для садовников.');
                    }
                },
            ],
            'password' => ['sometimes', 'nullable', 'string', 'min:8'],
        ]);

        if (array_key_exists('password', $data)) {
            if ($data['password'] === null) {
                unset($data['password']);
            } else {
                $data['password'] = Hash::make($data['password']);
            }
        }

        $user->update($data);

        return response()->json([
            'user' => new UserResource($user->fresh()),
        ]);
    }

    public function workerSchedule(Request $request, User $worker): JsonResponse
    {
        $viewer = $request->user();

        if (!$worker->hasRole(User::ROLE_WORKER)) {
            abort(404, 'Садовник не найден.');
        }

        if ($viewer->hasRole(User::ROLE_CLIENT)) {
            $isRelated = $viewer->default_worker_id === $worker->id
                || Order::query()
                    ->where('client_id', $viewer->id)
                    ->where('worker_id', $worker->id)
                    ->exists();

            if (!$isRelated) {
                abort(403, 'Нет доступа к графику садовника.');
            }
        }

        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'type' => ['nullable', Rule::in(['included', 'extra'])],
        ]);

        $from = $data['from'] ?? now()->subMonth()->toDateString();
        $to = $data['to'] ?? now()->toDateString();

        $query = OrderReport::query()
            ->with(['order' => function ($query) {
                $query->select('id', 'client_id', 'worker_id', 'description', 'payment_type', 'status');
            }])
            ->where('worker_id', $worker->id)
            ->whereBetween('report_date', [$from, $to])
            ->orderBy('report_date');

        if ($type = $data['type'] ?? null) {
            $query->where('work_type', $type);
        }

        if ($viewer->hasRole(User::ROLE_CLIENT)) {
            $query->whereHas('order', function ($orderQuery) use ($viewer) {
                $orderQuery->where('client_id', $viewer->id);
            });
        }

        return response()->json($query->get());
    }
}
