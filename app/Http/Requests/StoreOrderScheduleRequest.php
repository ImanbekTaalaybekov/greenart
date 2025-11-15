<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'order_id'      => ['required', 'exists:orders,id'],
            'worker_id'     => ['required', 'exists:users,id'],
            'scheduled_for' => ['required', 'date'],
            'status'        => ['nullable', Rule::in(['planned', 'done', 'cancelled'])],
        ];
    }
}
