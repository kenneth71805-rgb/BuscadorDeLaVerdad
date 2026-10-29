<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $article = $this->route('article');

        return $article && $this->user() && $this->user()->can('correct', $article);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'subtitle' => ['nullable', 'string', 'max:300'],
            'body' => ['required', 'string', 'min:20', 'max:100000'],
            'reason' => ['required', 'string', 'max:1000'],
            'public_note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
