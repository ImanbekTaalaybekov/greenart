<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignWorkerRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Order;
use App\Models\OrderPhoto;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Order::class);

        $query = Order::query()
            ->with(['client','worker','photos'])
            ->when(request('status'), fn($q) => $q->where('status', request('status')))
            ->when(request('worker_id'), fn($q) => $q->where('worker_id', request('worker_id')))
            ->when(request('client_id'), fn($q) => $q->where('client_id', request('client_id')))
            ->latest();

        return response()->json($query->paginate(20));
    }

    public function show(Order $order): JsonResponse
    {
        $this->authorize('view', $order);
        return response()->json($order->load(['client','worker','photos']));
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($data['payment_type'] === 'included') {
            $data['payment_money'] = null;
        }

        $data['client_id'] = $data['client_id'] ?? $request->user()->id;

        $order = DB::transaction(function () use ($data, $request) {
            $order = Order::create($data);

            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $file) {
                    $path = $file->store("orders/{$order->id}", 'public');
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

        return response()->json($order->load('photos'), 201);
    }

    public function update(UpdateOrderRequest $request, Order $order): JsonResponse
    {
        $data = $request->validated();

        if (($data['payment_type'] ?? null) === 'included') {
            $data['payment_money'] = null;
        }

        if ($request->user()->role === 'accountant') {
            $data = array_intersect_key($data, array_flip(['payment_type','payment_money']));
        }

        $order = DB::transaction(function () use ($order, $data, $request) {
            $order->update($data);

            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $file) {
                    $path = $file->store("orders/{$order->id}", 'public');
                    OrderPhoto::create([
                        'order_id'      => $order->id,
                        'path'          => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'mime_type'     => $file->getClientMimeType(),
                        'size'          => $file->getSize(),
                    ]);
                }
            }

            return $order->load('photos');
        });

        return response()->json($order);
    }

    public function assignWorker(AssignWorkerRequest $request, Order $order): JsonResponse
    {
        $order->update([
            'worker_id' => $request->integer('worker_id'),
            'status'    => $order->status === 'pending' ? 'assigned' : $order->status,
        ]);

        return response()->json($order->fresh(['worker']));
    }

    public function destroy(Order $order): JsonResponse
    {
        $this->authorize('delete', $order);
        $order->delete();
        return response()->json(['deleted' => true]);
    }
}
