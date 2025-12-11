<?php

declare(strict_types=1);

namespace App\Http\Requests\ExternalUrl;

use App\Enums\ExternalUrlType;
use App\Enums\RouteName;
use App\Http\Requests\FormRequest;
use App\Models\Department;
use Illuminate\Routing\Route;
use Illuminate\Validation\Rule;
use Override;
use Webmozart\Assert\Assert;

use function route;

class ExternalUrlUpdateRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'external_urls' => [
                'required',
                'array',
            ],
            'external_urls.*.petition_external_url_type' => [
                'string',
                Rule::enum(ExternalUrlType::class),
            ],
            'external_urls.*.url' => [
                'nullable',
                'string',
                'url',
                'max:2048',
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

        return route(RouteName::DEPARTMENTS_PETITIONS_EXTERNAL_URLS_EDIT, $parameters);
    }
}
