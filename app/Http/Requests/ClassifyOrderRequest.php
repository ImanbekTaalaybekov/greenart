<?php

namespace App\Http\Requests;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClassifyOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Order $order */
        $order = $this->route('order');
        return $this->user() && $order && $this->user()->can('classify', $order);
    }

    public function rules(): array
    {
        return [
            'payment_type'  => ['required', Rule::in(['included', 'extra'])],
            'payment_money' => ['nullable', 'required_if:payment_type,extra', 'numeric', 'min:0'],
        ];
    }
}
