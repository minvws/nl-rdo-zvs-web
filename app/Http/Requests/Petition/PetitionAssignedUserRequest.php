<?php

declare(strict_types=1);

namespace App\Http\Requests\Petition;

use App\Enums\RouteName;
use App\Http\Requests\FormRequest;
use Illuminate\Routing\Route;
use Override;
use Webmozart\Assert\Assert;

use function route;

class PetitionAssignedUserRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'user_id' => [
                'nullable',
                'uuid',
            ],
        ];
    }

    #[Override]
    protected function getRedirectUrl(): string
    {
        $route = $this->route();
        Assert::isInstanceOf($route, Route::class);

        $parameters = [
            'department' => $route->parameter('department'),
            'petition' => $route->parameter('petition'),
        ];

        if ($this->request->has('hx-target')) {
            $parameters['hx-target'] = $this->request->get('hx-target');
        }

        return route(RouteName::DEPARTMENTS_PETITIONS_ASSIGN_USER_EDIT, $parameters);
    }
}
