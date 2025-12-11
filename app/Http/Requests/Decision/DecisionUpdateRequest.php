<?php

declare(strict_types=1);

namespace App\Http\Requests\Decision;

use App\Enums\RouteName;
use App\Http\Requests\FormRequest;
use App\Models\Department;
use App\Rules\CalendarDateRule;
use Illuminate\Routing\Route;
use Override;
use Webmozart\Assert\Assert;

use function route;

class DecisionUpdateRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'reference' => ['string', 'nullable'],
            'date' => ['nullable', new CalendarDateRule()],
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
            'decision' => $route->parameter('decision'),
        ];

        if ($this->request->has('hx-target')) {
            $parameters['hx-target'] = $this->request->get('hx-target');
        }

        return route(RouteName::DEPARTMENTS_DECISIONS_EDIT, $parameters);
    }
}
