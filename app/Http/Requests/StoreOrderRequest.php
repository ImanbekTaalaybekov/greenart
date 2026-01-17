<?php

namespace App\Http\Requests;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'description'   => ['required', 'string', 'max:5000'],
            'payment_type'  => ['nullable', Rule::in(['included','extra'])],
            'payment_money' => ['nullable', 'required_if:payment_type,extra', 'numeric', 'min:0'],
            'client_id'     => ['required', 'exists:users,id'],
            'worker_id'     => ['nullable', 'exists:users,id'],
            'photos'        => ['nullable', 'array', 'max:100'],
            'photos.*'      => ['image'],
        ];
    }
}
