<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderRequest extends FormRequest
{

    public function rules(): array
    {
        $user = $this->user();
        $isAdminOrAccountant = $user->hasRole(User::ROLE_ADMIN, User::ROLE_ACCOUNTANT);

        $rules = [
            'description'   => ['sometimes', 'string', 'max:5000'],
            'photos'        => ['nullable', 'array', 'max:10'],
            'photos.*'      => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];

        if ($isAdminOrAccountant) {
            $rules['payment_type']  = ['sometimes', Rule::in(['included','extra'])];
            $rules['payment_money'] = ['nullable', 'required_if:payment_type,extra', 'numeric', 'min:0'];
            $rules['worker_id']     = ['sometimes', 'nullable', 'exists:users,id'];
            $rules['status']        = ['sometimes', Rule::in(['pending','assigned','in_progress','done','cancelled'])];
        }

        return $rules;
    }
}
