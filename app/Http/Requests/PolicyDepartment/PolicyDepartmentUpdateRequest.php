<?php

declare(strict_types=1);

namespace App\Http\Requests\PolicyDepartment;

use App\Models\PolicyDepartment;
use Illuminate\Routing\Route;
use Illuminate\Validation\Rule;
use Override;
use Webmozart\Assert\Assert;

class PolicyDepartmentUpdateRequest extends PolicyDepartmentStoreRequest
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function rules(): array
    {
        $rules = parent::rules();

        $route = $this->route();
        Assert::isInstanceOf($route, Route::class);

        $policyDepartment = $route->parameter('policyDepartment');
        Assert::isInstanceOf($policyDepartment, PolicyDepartment::class);

        $rules['name'] = [
            'required',
            'string',
            'max:255',
            Rule::unique('policy_departments', 'name')->ignore($policyDepartment),
        ];

        return $rules;
    }
}
