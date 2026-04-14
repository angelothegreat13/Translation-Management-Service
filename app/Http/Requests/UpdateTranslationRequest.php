<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTranslationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $translation = $this->route('translation');
        $locale      = $this->input('locale', $translation?->locale);

        return [
            'key' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('translations', 'key')
                    ->where('locale', $locale)
                    ->ignore($translation?->id),
            ],
            'locale'  => ['sometimes', 'string', 'max:10'],
            'content' => ['sometimes', 'string'],
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
