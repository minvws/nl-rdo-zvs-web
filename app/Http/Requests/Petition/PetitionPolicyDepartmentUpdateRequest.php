<?php

declare(strict_types=1);

namespace App\Http\Requests\Petition;

use App\Http\Requests\FormRequest;
use Webmozart\Assert\Assert;

use function array_filter;

class PetitionPolicyDepartmentUpdateRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'policy_department_ids' => [
                'array',
            ],
            'policy_department_ids.*' => [
                'uuid',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        Assert::isArray($this->input('policy_department_ids', []));
        $this->merge([
            'policy_department_ids' => array_filter($this->input('policy_department_ids', [])),
        ]);
    }
}
