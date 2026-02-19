<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWorkVisitRequest;
use App\Models\WorkVisit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WorkVisitController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'date' => ['nullable', 'date'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $query = WorkVisit::with(['order.client', 'reports'])
            ->where('worker_id', $request->user()->id)
            ->orderByDesc('visit_date');

        if ($date = $data['date'] ?? null) {
            $query->whereDate('visit_date', $date);
        } elseif (($from = $data['from'] ?? null) && ($to = $data['to'] ?? null)) {
            $query->whereBetween('visit_date', [$from, $to]);
        }

        return response()->json($query->get());
    }

    public function indexAdmin(Request $request): JsonResponse
    {
        $data = $request->validate([
            'worker_id' => ['nullable', 'exists:users,id'],
            'date' => ['nullable', 'date'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $query = WorkVisit::with(['order.client', 'reports', 'worker'])
            ->orderByDesc('visit_date');

        if ($workerId = $data['worker_id'] ?? null) {
            $query->where('worker_id', $workerId);
        }

        if ($date = $data['date'] ?? null) {
            $query->whereDate('visit_date', $date);
        } elseif (($from = $data['from'] ?? null) && ($to = $data['to'] ?? null)) {
            $query->whereBetween('visit_date', [$from, $to]);
        }

        return response()->json($query->paginate(50));
    }

    public function store(StoreWorkVisitRequest $request): JsonResponse
    {
        $visit = WorkVisit::create([
            'worker_id' => $request->user()->id,
            'order_id' => $request->input('order_id'),
            'visit_date' => $request->input('visit_date'),
            'comment' => $request->input('comment'),
        ]);

        return response()->json($visit->load('order.client'), 201);
    }

    public function destroy(Request $request, WorkVisit $visit): JsonResponse
    {
        if ($visit->worker_id !== $request->user()->id) {
            abort(403, 'Нет доступа.');
        }

        $visit->delete();

        return response()->json(['message' => 'Визит удалён.']);
    }
}
