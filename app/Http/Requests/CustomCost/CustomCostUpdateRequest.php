<?php

declare(strict_types=1);

namespace App\Http\Requests\CustomCost;

use App\Enums\CustomCostType;
use App\Enums\RouteName;
use App\Http\Requests\FormRequest;
use App\Models\Department;
use Illuminate\Routing\Route;
use Illuminate\Validation\Rule;
use Override;
use Webmozart\Assert\Assert;

use function route;

class CustomCostUpdateRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'custom_costs' => [
                'required',
                'array',
            ],
            'custom_costs.*.custom_cost_type' => [
                'string',
                Rule::enum(CustomCostType::class),
            ],
            'custom_costs.*.custom_cost_amount_in_euros' => [
                'numeric',
                'min:0',
                'max:100000000',
            ],
        ];
    }

    #[Override]
    protected function getRedirectUrl(): string
    {
        $route = $this->route();
        Assert::isInstanceOf($route, Route::class);

        $department = $route->parameter('department');
        Assert::isInstanceOf($department, Department::class);

        $parameters = [
            'department' => $department,
            'petition' => $route->parameter('petition'),
        ];

        if ($this->request->has('hx-target')) {
            $parameters['hx-target'] = $this->request->get('hx-target');
        }

        return route(RouteName::DEPARTMENTS_PETITIONS_CUSTOM_COSTS_EDIT, $parameters);
    }
}
