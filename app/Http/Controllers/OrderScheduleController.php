<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderScheduleRequest;
use App\Http\Requests\UpdateOrderScheduleRequest;
use App\Models\Order;
use App\Models\OrderSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderScheduleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'worker_id' => ['nullable', 'exists:users,id'],
            'from'      => ['nullable', 'date'],
            'to'        => ['nullable', 'date'],
        ]);

        $query = OrderSchedule::query()
            ->with(['order.client', 'worker'])
            ->orderBy('scheduled_for');

        if ($data['worker_id'] ?? null) {
            $query->where('worker_id', $data['worker_id']);
        }

        if ($data['from'] ?? null) {
            $query->whereDate('scheduled_for', '>=', $data['from']);
        }

        if ($data['to'] ?? null) {
            $query->whereDate('scheduled_for', '<=', $data['to']);
        }

        return response()->json($query->get());
    }

    public function store(StoreOrderScheduleRequest $request): JsonResponse
    {
        $data = $request->validated();

        $schedule = DB::transaction(function () use ($data) {
            $order = Order::lockForUpdate()->findOrFail($data['order_id']);

            if ($order->worker_id !== $data['worker_id']) {
                $nextStatus = $order->status === 'pending' ? 'assigned' : $order->status;
                $order->update([
                    'worker_id' => $data['worker_id'],
                    'status'    => $nextStatus,
                ]);
            }

            $schedule = OrderSchedule::create([
                'order_id'      => $order->id,
                'worker_id'     => $data['worker_id'],
                'scheduled_for' => $data['scheduled_for'],
                'status'        => $data['status'] ?? 'planned',
            ]);

            return $schedule->load(['order.client', 'worker']);
        });

        return response()->json($schedule, 201);
    }

    public function update(UpdateOrderScheduleRequest $request, OrderSchedule $schedule): JsonResponse
    {
        $data = $request->validated();

        $schedule = DB::transaction(function () use ($schedule, $data) {
            if (isset($data['worker_id']) && $schedule->worker_id !== $data['worker_id']) {
                $schedule->order->update(['worker_id' => $data['worker_id']]);
                $schedule->worker_id = $data['worker_id'];
            }

            if (isset($data['scheduled_for'])) {
                $schedule->scheduled_for = $data['scheduled_for'];
            }

            if (isset($data['status'])) {
                $schedule->status = $data['status'];
            }

            $schedule->save();

            return $schedule->load(['order.client', 'worker']);
        });

        return response()->json($schedule);
    }

    public function destroy(OrderSchedule $schedule): JsonResponse
    {
        $schedule->delete();
        return response()->json(['deleted' => true]);
    }
}
