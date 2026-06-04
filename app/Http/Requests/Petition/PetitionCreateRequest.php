<?php

declare(strict_types=1);

namespace App\Http\Requests\Petition;

use App\Enums\Authorization\Permission;
use App\Enums\OptionalFormFieldSetting;
use App\Http\Requests\FormRequest;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionType;
use App\Models\User;
use App\Rules\CalendarDateRule;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\Rule;
use Ramsey\Uuid\UuidInterface;
use Webmozart\Assert\Assert;

use function sprintf;

class PetitionCreateRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(#[CurrentUser] User $user): array
    {
        $rules = [
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
            'team_id' => [
                'nullable',
                'uuid',
                Rule::exists('teams', 'id')->where('department_id', $this->getDepartmentId()->toString()),
            ],
        ];

        if ($user->can(Permission::PETITION_NUMBER_OVERRULE->value)) {
            $rules['number'] = [
                'nullable',
                'string',
                'max:64',
                'not_regex:' . Config::string('app.petition_number_pattern'),
                Rule::unique(Petition::class, 'number'),
            ];
        }

        return $rules;
    }

    private function getFieldSetting(string $field): string
    {
        $petitionType = $this->route('petitionType');
        Assert::isInstanceOf($petitionType, PetitionType::class);

        $petitionTypeConfiguration = Config::array(sprintf('petition_variant.%s.optional_form_fields', $petitionType->type->value));
        Assert::keyExists($petitionTypeConfiguration, $field);
        Assert::isInstanceOf($petitionTypeConfiguration[$field], OptionalFormFieldSetting::class);

        return $petitionTypeConfiguration[$field]->getValidationRule();
    }

    private function getDepartmentId(): UuidInterface
    {
        $department = $this->route('department');
        Assert::isInstanceOf($department, Department::class);

        return $department->id;
    }
}
