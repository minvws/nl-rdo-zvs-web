<?php

declare(strict_types=1);

namespace App\Http\Requests\PetitionType;

use App\Http\Requests\FormRequest;

class PetitionTypeUpdateRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:64',
            ],
            'particularity_label' => [
                'nullable',
                'string',
                'max:16',
            ],
            'active' => [
                'boolean',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'active' => $this->boolean('active', true),
        ]);
    }
}
