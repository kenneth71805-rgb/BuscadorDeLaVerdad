<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class AssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user?->hasRole(Role::Admin, Role::EditorInChief, Role::CategoryHead)) {
            return false;
        }

        // Editing an existing assignment: the policy decides first, so unauthorized people get a clean 403.
        $assignment = $this->route('assignment');

        return $assignment ? $user->can('update', $assignment) : true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'assigned_to' => ['required', 'integer', 'exists:users,id'],
            'due_at' => ['required', 'date', $this->route('assignment') ? 'date' : 'after:now'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            $user = $this->user();

            if ($user->hasRole(Role::CategoryHead) && ! $user->headsCategory((int) $this->input('category_id'))) {
                $validator->errors()->add('category_id', 'You can only create assignments in your own categories.');
            }

            $assignee = User::find($this->input('assigned_to'));
            if (! $assignee || ! $assignee->is_active || $assignee->role === null) {
                $validator->errors()->add('assigned_to', 'Choose an active staff member.');
            }
        }];
    }
}
