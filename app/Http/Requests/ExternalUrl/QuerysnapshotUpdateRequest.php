<?php

declare(strict_types=1);

namespace App\Http\Requests\ExternalUrl;

use App\Enums\QuerysnapshotType;
use App\Enums\RouteName;
use App\Http\Requests\FormRequest;
use App\Models\Department;
use Illuminate\Routing\Route;
use Illuminate\Validation\Rule;
use Override;
use Webmozart\Assert\Assert;

use function route;

class QuerysnapshotUpdateRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'querysnapshots' => [
                'required',
                'array',
            ],
            'querysnapshots.*.querysnapshot_type' => [
                'string',
                Rule::enum(QuerysnapshotType::class),
            ],
            'querysnapshots.*.querysnapshot_id' => [
                'nullable',
                'string',
                'max:255',
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

        return route(RouteName::DEPARTMENTS_PETITIONS_QUERYSNAPSHOTS_EDIT, $parameters);
    }
}
