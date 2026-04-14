<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTranslationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'key'    => [
                'required',
                'string',
                'max:255',
                Rule::unique('translations', 'key')
                    ->where('locale', $this->input('locale')),
            ],
            'locale'  => ['required', 'string', 'max:10'],
            'content' => ['required', 'string'],
            'tags'    => ['sometimes', 'array'],
            'tags.*'  => ['string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'key.unique' => 'This key already exists for the given locale.',
        ];
    }
}
