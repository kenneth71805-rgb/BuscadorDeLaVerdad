<?php

namespace App\Http\Requests;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;

class IssueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasRole(Role::Admin, Role::EditorInChief);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'issue_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:2000'],
            'cover_media_id' => ['nullable', 'integer', 'exists:media,id'],
            'is_public' => ['required', 'boolean'],
            'pdf' => [$this->route('issue') ? 'nullable' : 'required', 'file', 'max:40960'], // content checked by PdfValidator
        ];
    }
}
