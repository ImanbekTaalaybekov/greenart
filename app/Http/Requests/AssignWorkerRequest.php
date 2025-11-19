<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Order;

class AssignWorkerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $order = $this->route('order');
        return $this->user() && $order && $this->user()->can('assignWorker', $order);
    }

    public function rules(): array
    {
        return [
            'worker_id' => ['required', 'exists:users,id'],
        ];
    }
}
