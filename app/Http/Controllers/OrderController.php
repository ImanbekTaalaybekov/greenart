<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignWorkerRequest;
use App\Http\Requests\ClassifyOrderRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Order;
use App\Models\OrderPhoto;
use App\Models\User;
use App\Services\Order\CreateOrderService;
use App\Services\PhotoService;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Order::class);
        $user = request()->user();

        $query = Order::query()
            ->with(['client', 'worker', 'photos'])
            ->latest();

        if ($user->hasRole(User::ROLE_CLIENT)) {
            $query->where('client_id', $user->id);
        } elseif ($user->hasRole(User::ROLE_WORKER)) {
            $query->where('worker_id', $user->id);
        }
        
        $query->when(request('status'), fn($q) => $q->where('status', request('status')))
              ->when(request('worker_id'), fn($q) => $q->where('worker_id', request('worker_id')))
              ->when(request('client_id'), fn($q) => $q->where('client_id', request('client_id')));

        return response()->json($query->paginate(20));
    }

    public function show(Order $order): JsonResponse
    {
        $this->authorize('view', $order);
        return response()->json($order->load(['client', 'worker', 'photos']));
    }

    public function store(StoreOrderRequest $request, CreateOrderService $createOrderService, PhotoService $photoService): JsonResponse
    {
        $order = $createOrderService->apply($request->validated());

        $photoService->apply($order, $request, 'orders', OrderPhoto::class, 'order_id');

        return response()->json($order->load(['photos', 'client', 'worker']), 201);
    }

    public function update(UpdateOrderRequest $request, Order $order, PhotoService $photoService): JsonResponse
    {
        $data = $request->validated();
        if (($data['payment_type'] ?? null) === 'included') {
            $data['payment_money'] = null;
        }
        $order->update($data);

        $photoService->apply($order, $request, 'orders', OrderPhoto::class, 'order_id');

        return response()->json($order->load('photos'));
    }

    public function assignWorker(AssignWorkerRequest $request, Order $order): JsonResponse
    {
        $this->authorize('assignWorker', $order);
        $order->update([
            'worker_id' => $request->integer('worker_id'),
            'status' => $order->status === 'pending' ? 'assigned' : $order->status,
        ]);
        return response()->json($order->fresh(['worker']));
    }

    public function classify(ClassifyOrderRequest $request, Order $order): JsonResponse
    {
        $this->authorize('classify', $order);
        $data = $request->validated();
        if ($data['payment_type'] === 'included') {
            $data['payment_money'] = null;
        }
        $order->update($data);
        return response()->json($order->fresh());
    }

    public function destroy(Order $order): JsonResponse
    {
        $this->authorize('delete', $order);
        $order->delete();

        return response()->json(['message' => 'Заявка успешно удалена']);
    }
}