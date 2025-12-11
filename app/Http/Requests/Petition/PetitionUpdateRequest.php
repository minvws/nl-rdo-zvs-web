<?php

declare(strict_types=1);

namespace App\Http\Requests\Petition;

use App\Config\Config;
use App\Enums\OptionalFormFieldSetting;
use App\Enums\RouteName;
use App\Models\Petition;
use App\Models\User;
use App\Rules\CalendarDateRule;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Routing\Route;
use Override;
use Webmozart\Assert\Assert;

use function route;
use function sprintf;

class PetitionUpdateRequest extends PetitionCreateRequest
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function rules(#[CurrentUser] User $user): array
    {
        return [
            'petition_category_id' => [
                $this->getFieldSetting('petition_category_id'),
                'uuid',
            ],
            'name' => [
                $this->getFieldSetting('name'),
                'string',
                'max:255',
            ],
            'date_of_entry' => [
                'required',
                new CalendarDateRule(),
            ],
            'date_appealed_decision' => [
                $this->getFieldSetting('date_appealed_decision'),
                new CalendarDateRule(),
            ],
            'description' => [
                $this->getFieldSetting('description'),
                'string',
            ],
        ];
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

        return route(RouteName::DEPARTMENTS_PETITIONS_PROPERTIES_EDIT, $parameters);
    }

    private function getFieldSetting(string $field): string
    {
        $route = $this->getRoute();

        $petition = $route->parameter('petition');
        Assert::isInstanceOf($petition, Petition::class);

        $petitionTypeConfiguration = Config::array(
            sprintf('petition_type_type.%s.optional_form_fields', $petition->petitionType->type->value),
        );
        Assert::keyExists($petitionTypeConfiguration, $field);
        Assert::isInstanceOf($petitionTypeConfiguration[$field], OptionalFormFieldSetting::class);

        return $petitionTypeConfiguration[$field]->getValidationRule();
    }

    private function getRoute(): Route
    {
        $route = $this->route();
        Assert::isInstanceOf($route, Route::class);

        return $route;
    }
}
