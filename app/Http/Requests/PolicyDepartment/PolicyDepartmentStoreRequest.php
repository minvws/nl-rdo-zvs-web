<?php

declare(strict_types=1);

namespace App\Http\Requests\PolicyDepartment;

use App\Http\Requests\FormRequest;

class PolicyDepartmentStoreRequest extends FormRequest
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
                'max:255',
                'unique:policy_departments,name',
            ],
            'active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $this->merge([
                'name' => $this->string('name')->trim()->toString(),
            ]);
        }
    }
}
