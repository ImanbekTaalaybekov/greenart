<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->can('create', \App\Models\Announcement::class);
    }

    public function rules(): array
    {
        return [
            'title'     => ['required','string','max:255'],
            'body'      => ['nullable','string'],
            'audience'  => ['required', Rule::in(['all','clients','workers'])],
            'published_at' => ['nullable','date'],
            'photos'    => ['nullable','array','max:10'],
            'photos.*'  => ['image','mimes:jpg,jpeg,png,webp','max:5120'],
        ];
    }
}
