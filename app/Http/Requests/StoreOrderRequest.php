<?php

namespace App\Http\Requests;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Order::class);
    }

    public function rules(): array
    {
        return [
            'description'   => ['required', 'string', 'max:5000'],
            'payment_type'  => ['required', Rule::in(['included','extra'])],
            'payment_money' => ['nullable', 'required_if:payment_type,extra', 'numeric', 'min:0'],
            'worker_id'     => ['nullable', 'exists:users,id'],
            'photos'        => ['nullable', 'array', 'max:10'],
            'photos.*'      => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
