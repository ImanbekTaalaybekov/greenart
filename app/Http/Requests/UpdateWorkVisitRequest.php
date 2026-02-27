<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['sometimes', 'exists:orders,id'],
            'worker_id' => ['sometimes', 'exists:users,id'],
            'visit_date' => ['sometimes', 'date'],
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
