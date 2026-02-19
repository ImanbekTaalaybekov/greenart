<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => [
                'required',
                'exists:orders,id',
                function ($attribute, $value, $fail) {
                    if (!\App\Models\Order::where('id', $value)->where('worker_id', $this->user()->id)->exists()) {
                        $fail('Вы не являетесь исполнителем данного заказа (или заказ не найден).');
                    }
                }
            ],
            'visit_date' => ['required', 'date'],
            'comment' => ['nullable', 'string', 'max:5000'],
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        throw new \Illuminate\Http\Exceptions\HttpResponseException(response()->json([
            'message' => 'Ошибка валидации.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
