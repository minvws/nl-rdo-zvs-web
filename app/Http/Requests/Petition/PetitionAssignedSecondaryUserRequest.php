<?php

declare(strict_types=1);

namespace App\Http\Requests\Petition;

use App\Enums\RouteName;
use App\Http\Requests\FormRequest;
use App\Models\Petition;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Routing\Route;
use Illuminate\Validation\Rule;
use Override;
use Webmozart\Assert\Assert;

use function route;

class PetitionAssignedSecondaryUserRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(#[CurrentUser] User $user): array
    {
        $route = $this->getRoute();

        $petition = $route->parameter('petition');
        Assert::isInstanceOf($petition, Petition::class);

        return [
            'user_id' => [
                'nullable',
                'uuid',
                Rule::when(
                    $petition->firstAssignee !== null,
                    ['different:primary_user_id'],
                    [],
                ),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'user_id' => 'Achtervang gebruiker',
        ];
    }

    /**
     * Get data to be validated from the request.
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function validationData(): array
    {
        $route = $this->getRoute();

        /** @var Petition $petition */
        $petition = $route->parameter('petition');

        /** @var array<string, mixed> $data */
        $data = $this->all();

        if ($petition->firstAssignee !== null) {
            $data['primary_user_id'] = $petition->firstAssignee->user_id->toString();
        }

        return $data;
    }

    #[Override]
    protected function getRedirectUrl(): string
    {
        $route = $this->getRoute();

        $parameters = [
            'department' => $route->parameter('department'),
            'petition' => $route->parameter('petition'),
        ];

        if ($this->request->has('hx-target')) {
            $parameters['hx-target'] = $this->request->get('hx-target');
        }

        return route(RouteName::DEPARTMENTS_PETITIONS_ASSIGN_SECONDARY_EDIT, $parameters);
    }

    private function getRoute(): Route
    {
        $route = $this->route();
        Assert::isInstanceOf($route, Route::class);

        return $route;
    }
}
