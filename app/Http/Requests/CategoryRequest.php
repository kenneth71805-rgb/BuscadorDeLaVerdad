<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\Category;
use App\Support\ReservedSlugs;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasRole(Role::Admin);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('categories', 'name')->ignore($this->route('category')?->id)],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999'],
            'is_active' => ['required', 'boolean'],
            'show_in_nav' => ['required', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($this->route('category')) {
                return; // the slug never changes after creation, so public URLs stay stable
            }
            $slug = Str::slug((string) $this->input('name'));
            if ($slug === '' || ReservedSlugs::has($slug)) {
                $validator->errors()->add('name', 'This name cannot be used because it would clash with a system page.');
            } elseif (Category::where('slug', $slug)->exists()) {
                $validator->errors()->add('name', 'A category with a very similar name already exists.');
            }
        }];
    }
}
