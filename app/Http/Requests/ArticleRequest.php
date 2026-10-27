<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\Category;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class ArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user?->hasRole(Role::Admin, Role::EditorInChief, Role::CategoryHead)) {
            return false;
        }

        // Editing an existing article: the policy decides first, so unauthorized people get a clean 403.
        $article = $this->route('article');

        return $article ? $user->can('update', $article) : true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'subtitle' => ['nullable', 'string', 'max:300'],
            'body' => ['required', 'string', 'min:20', 'max:100000'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'lead_media_id' => ['nullable', 'integer', 'exists:media,id'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $user = $this->user();
                $categoryId = (int) $this->input('category_id');

                $allowed = $user->hasRole(Role::CategoryHead)
                    ? $user->headsCategory($categoryId)
                    : Category::whereKey($categoryId)->where('is_active', true)->exists();

                $mediaId = $this->input('lead_media_id');
                if ($mediaId && ! $user->hasRole(Role::Admin, Role::EditorInChief)
                    && ! \App\Models\Media::whereKey($mediaId)->where('uploader_id', $user->id)->exists()) {
                    $validator->errors()->add('lead_media_id', 'You can only use photos you uploaded yourself.');
                }

                if (! $allowed) {
                    $validator->errors()->add('category_id', 'You cannot write in this category.');
                }
            },
        ];
    }
}
