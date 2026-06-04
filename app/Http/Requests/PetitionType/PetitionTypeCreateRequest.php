<?php

declare(strict_types=1);

namespace App\Http\Requests\PetitionType;

use App\Enums\PetitionVariant;
use App\Http\Requests\FormRequest;
use Illuminate\Validation\Rule;
use Override;

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
                Rule::enum(PetitionVariant::class),
            ],
            'active' => [
                'boolean',
            ],
        ];
    }

    #[Override]
    protected function prepareForValidation(): void
    {
        $this->merge([
            'active' => $this->boolean('active', true),
        ]);
    }
}
