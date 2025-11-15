<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(User::ROLE_ADMIN) ?? false;
    }

    public function rules(): array
    {
        return [
            'worker_id'     => ['sometimes', 'exists:users,id'],
            'scheduled_for' => ['sometimes', 'date'],
            'status'        => ['sometimes', Rule::in(['planned', 'done', 'cancelled'])],
        ];
    }
}
