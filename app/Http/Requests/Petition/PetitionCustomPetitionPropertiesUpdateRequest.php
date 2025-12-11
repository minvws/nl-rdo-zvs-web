<?php

declare(strict_types=1);

namespace App\Http\Requests\Petition;

use App\Enums\RouteName;
use App\Http\Requests\FormRequest;
use App\Models\CustomPetitionProperty;
use App\Models\Department;
use App\Models\Petition as PetitionModel;
use Illuminate\Routing\Route;
use Illuminate\Validation\Validator;
use Override;
use Webmozart\Assert\Assert;

use function __;
use function route;

class PetitionCustomPetitionPropertiesUpdateRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'custom_petition_properties' => [
                'array',
            ],
            'custom_petition_properties.*' => [
                'uuid',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(static function (Validator $validator): void {
            $data = $validator->getData();
            $propertyIds = $data['custom_petition_properties'] ?? [];

            if (empty($propertyIds)) {
                return;
            }

            $properties = CustomPetitionProperty::query()
                ->whereIn('id', $propertyIds)
                ->whereNotNull('grouping')
                ->select('id', 'grouping')
                ->get();

            $properties->groupBy('grouping')
                ->filter(static fn($propertiesInGroup): bool => $propertiesInGroup->count() > 1)
                ->each(static function ($propertiesInGroup) use ($validator): void {
                    $propertiesInGroup->each(static function ($property) use ($validator): void {
                        $validator->errors()->add(
                            $property->id->toString(),
                            __('validation.unique_custom_petition_property_grouping'),
                        );
                    });
                });
        });
    }

    #[Override]
    protected function getRedirectUrl(): string
    {
        $route = $this->route();
        Assert::isInstanceOf($route, Route::class);

        $department = $route->parameter('department');
        Assert::isInstanceOf($department, Department::class);

        $petition = $route->parameter('petition');
        Assert::isInstanceOf($petition, PetitionModel::class);

        return route(RouteName::DEPARTMENTS_PETITIONS_CUSTOM_PETITION_PROPERTY_EDIT, [
            'department' => $department,
            'petition' => $petition,
        ]);
    }
}
