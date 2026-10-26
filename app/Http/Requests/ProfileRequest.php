<?php

namespace App\Http\Requests;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;

class ProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasRole(Role::Admin);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'position' => ['required', 'string', 'max:120'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'link_url' => ['nullable', 'url:http,https', 'max:300'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999'],
            'is_public' => ['required', 'boolean'],
            'archived' => ['required', 'boolean'],
            'photo' => ['nullable', 'file', 'max:8192'],
            'remove_photo' => ['nullable', 'boolean'],
        ];
    }
}
