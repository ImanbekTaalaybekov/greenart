<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Order;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        $order = $this->route('order');
        return $this->user() && $order && $this->user()->can('update', $order);
    }

    public function rules(): array
    {
        return [
            'description'   => ['sometimes', 'string', 'max:5000'],
            'payment_type'  => ['sometimes', Rule::in(['included','extra'])],
            'payment_money' => ['nullable', 'required_if:payment_type,extra', 'numeric', 'min:0'],
            'worker_id'     => ['sometimes', 'nullable', 'exists:users,id'],
            'status'        => ['sometimes', Rule::in(['pending','assigned','in_progress','done','cancelled'])],
            'photos'        => ['nullable', 'array', 'max:10'],
            'photos.*'      => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    protected function prepareForValidation(): void
    {
    }
}
