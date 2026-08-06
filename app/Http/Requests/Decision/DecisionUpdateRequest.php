<?php

declare(strict_types=1);

namespace App\Http\Requests\Decision;

use App\Enums\RouteName;
use App\Http\Requests\FormRequest;
use App\Models\Decision;
use App\Models\Department;
use App\Rules\CalendarDateRule;
use Illuminate\Routing\Route;
use Illuminate\Validation\Rule;
use Override;
use Ramsey\Uuid\UuidInterface;
use Webmozart\Assert\Assert;

use function route;
use function strtolower;

class DecisionUpdateRequest extends FormRequest
{
     /**
      * @return array<string, mixed>
      */
    public function rules(): array
    {
        $decision = $this->route('decision');
        $decisionId = $decision instanceof Decision ? $decision->id : null;

        return [
            'name' => ['required', 'string'],
            'reference' => [
                'nullable',
                'string',
                'unique:decisions,reference,' . $decisionId,
            ],
            'date' => ['nullable', new CalendarDateRule()],
            'reviewbatch' => ['nullable', 'string', 'max:128'],
            'team_id' => [
                'nullable',
                'uuid',
                Rule::exists('teams', 'id')->where('department_id', $this->getDepartmentId()->toString()),
            ],
        ];
    }

    #[Override]
    protected function prepareForValidation(): void
    {
        if ($this->filled('reference')) {
            $this->merge(['reference' => strtolower($this->string('reference')->toString())]);
        }
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

    private function getDepartmentId(): UuidInterface
    {
        $department = $this->route('department');
        Assert::isInstanceOf($department, Department::class);

        return $department->id;
    }
}
