<?php

namespace App\Http\Controllers;

use App\Models\SalaryAdjustment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SalaryAdjustmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'worker_id' => ['nullable', 'exists:users,id'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'type' => ['nullable', Rule::in(['penalty', 'bonus'])],
        ]);

        $query = SalaryAdjustment::with('worker')->orderByDesc('date');

        if ($workerId = $data['worker_id'] ?? null) {
            $query->where('worker_id', $workerId);
        }

        if (($from = $data['from'] ?? null) && ($to = $data['to'] ?? null)) {
            $query->whereBetween('date', [$from, $to]);
        }

        if ($type = $data['type'] ?? null) {
            $query->where('type', $type);
        }

        return response()->json($query->paginate(50));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'worker_id' => [
                'required',
                'exists:users,id',
                function ($attr, $value, $fail) {
                    $user = User::find($value);
                    if (!$user || $user->role !== User::ROLE_WORKER) {
                        $fail('Пользователь не является садовником.');
                    }
                }
            ],
            'type' => ['required', Rule::in(['penalty', 'bonus'])],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['nullable', 'string', 'max:5000'],
            'date' => ['required', 'date'],
        ]);

        $adjustment = SalaryAdjustment::create($data);

        return response()->json($adjustment->load('worker'), 201);
    }

    public function destroy(SalaryAdjustment $adjustment): JsonResponse
    {
        $adjustment->delete();

        return response()->json(['message' => 'Удалено.']);
    }
}
