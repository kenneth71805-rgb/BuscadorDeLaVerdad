<?php

namespace App\Http\Requests;

use App\Enums\Role;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasRole(Role::Admin);
    }

    public function rules(): array
    {
        $editing = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($editing?->id)],
            'role' => ['required', Rule::enum(Role::class)],
            'position' => ['nullable', 'string', 'max:120'],
            'is_active' => ['required', 'boolean'],
            'can_override' => ['boolean'],
            'can_publish' => ['boolean'],
            'category_ids' => ['array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'password' => [$editing ? 'nullable' : 'required', Password::min(12)->letters()->numbers()],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->input('role') === Role::CategoryHead->value && empty($this->input('category_ids'))) {
                    $validator->errors()->add('category_ids', 'A Head of Category needs at least one category.');
                }
            },
        ];
    }
}
