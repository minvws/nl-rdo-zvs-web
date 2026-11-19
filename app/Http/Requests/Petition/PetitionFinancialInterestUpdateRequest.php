<?php

declare(strict_types=1);

namespace App\Http\Requests\Petition;

use App\Enums\Ability;
use App\Enums\RouteName;
use App\Http\Requests\FormRequest;
use App\Models\Department;
use App\Models\Petition;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Gate;
use Override;
use Webmozart\Assert\Assert;

use function route;

class PetitionFinancialInterestUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $petition = $this->route('petition');
        Assert::isInstanceOf($petition, Petition::class);

        return Gate::allows(Ability::UPDATE, $petition);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'amount_in_dispute' => [
                'nullable',
                'integer',
                'min:0',
                'max:2147483647',
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

        if ($this->has('hx-target')) {
            $parameters['hx-target'] = $this->input('hx-target');
        }

        return route(RouteName::DEPARTMENTS_PETITIONS_FINANCIAL_INTEREST_EDIT, $parameters);
    }
}
