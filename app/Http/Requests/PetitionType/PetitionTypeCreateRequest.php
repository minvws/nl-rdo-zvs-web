<?php

declare(strict_types=1);

namespace App\Http\Requests\PetitionType;

use App\Enums\PetitionTypeType;
use App\Http\Requests\FormRequest;
use Illuminate\Validation\Rule;

class PetitionTypeCreateRequest extends FormRequest
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
            'type' => [
                'required',
                Rule::enum(PetitionTypeType::class),
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
