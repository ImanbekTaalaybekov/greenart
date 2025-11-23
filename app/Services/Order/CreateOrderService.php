<?php

namespace App\Services\Order;

use App\Models\Order;
use App\Models\User;

class CreateOrderService
{
    public function apply($data)
    {
        if ($data['payment_type'] === 'included') {
            $data['payment_money'] = null;
        }

        $data['client_id'] = $data['client_id'] ?? auth()->user()->id;

        $client = auth()->user()->id === $data['client_id'] ? auth()->user() : User::find($data['client_id']);

        if (isset($data['worker_id']) && is_null($data['worker_id'])) {
            $data['worker_id'] = $client->default_worker_id;
            $data['status'] = $data['status'] ?? 'assigned';
        }

        return Order::create($data);
    }
}
