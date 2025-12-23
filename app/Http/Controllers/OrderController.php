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
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Order::class);

        $query = Order::query()
            ->with(['client', 'worker', 'photos'])
            ->when(request('status'), fn($q) => $q->where('status', request('status')))
            ->when(request('worker_id'), fn($q) => $q->where('worker_id', request('worker_id')))
            ->when(request('client_id'), fn($q) => $q->where('client_id', request('client_id')))
            ->latest();

        return response()->json($query->paginate(20));
    }

    public function show(Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        return response()->json($order->load(['client', 'worker', 'photos']));
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        if ($user->role === 'client') {
            $data['client_id'] = $user->id;
            $client = $user;
        } else {
            if (empty($data['client_id'])) {
                abort(422, 'Администратор обязан указать client_id');
            }
            $client = User::find($data['client_id']);
        }

        if (empty($data['worker_id']) && $client?->default_worker_id) {
            $data['worker_id'] = $client->default_worker_id;
            $data['status'] = 'assigned';
        }

        if ($data['payment_type'] === 'included') {
            $data['payment_money'] = null;
        }

        $order = DB::transaction(function () use ($data, $request) {
            $order = Order::create($data);

            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $file) {
                    $path = $file->storeS("orders/{$order->id}", 'public');
                    OrderPhoto::create([
                        'order_id'      => $order->id,
                        'path'          => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'mime_type'     => $file->getClientMimeType(),
                        'size'          => $file->getSize(),
                    ]);
                }
            }

            return $order;
        });

        return response()->json($order->load(['photos', 'client', 'worker']), 201);
    }

    public function update(UpdateOrderRequest $request, Order $order, PhotoService $photoService): JsonResponse
    {
        $data = $request->validated();

        if (($data['payment_type'] ?? null) === 'included') {
            $data['payment_money'] = null;
        }

        $order->update($data);

        $photoService->apply(
            $order,
            $request,
            'orders',
            OrderPhoto::class,
            'order_id'
        );

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

        return response()->json([]);
    }
}
