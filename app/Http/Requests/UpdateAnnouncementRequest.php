<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Announcement;

class UpdateAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Announcement $a */
        $a = $this->route('announcement');
        return $this->user() && $a && $this->user()->can('update', $a);
    }

    public function rules(): array
    {
        return [
            'title'     => ['sometimes','string','max:255'],
            'body'      => ['sometimes','nullable','string'],
            'audience'  => ['sometimes', Rule::in(['all','clients','workers'])],
            'published_at' => ['sometimes','nullable','date'],
            'photos'    => ['nullable','array','max:10'],
            'photos.*'  => ['image','mimes:jpg,jpeg,png,webp','max:5120'],
        ];
    }
}

