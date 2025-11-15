<?php

namespace App\Http\Requests;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrderReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Order $order */
        $order = $this->route('order');

        return $this->user() && $order && $order->worker_id === $this->user()->id;
    }

    protected function prepareForValidation(): void
    {
        if ($this->query('date') && !$this->input('report_date')) {
            $this->merge(['report_date' => $this->query('date')]);
        }
    }

    public function rules(): array
    {
        return [
            'report_date' => ['required', 'date'],
            'comment'     => ['nullable', 'string', 'max:5000'],
            'photos'      => ['nullable', 'array', 'max:10'],
            'photos.*'    => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
