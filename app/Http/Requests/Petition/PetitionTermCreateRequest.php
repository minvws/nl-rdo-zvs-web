<?php

declare(strict_types=1);

namespace App\Http\Requests\Petition;

use App\Enums\TermType;
use App\Http\Requests\FormRequest;
use App\Models\Department;
use App\Models\DepartmentTermTypeSetting;
use App\Rules\CalendarDateRule;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Webmozart\Assert\Assert;

class PetitionTermCreateRequest extends FormRequest
{
    private const string MAX_DATE = '2999-12-31';

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        Assert::isInstanceOf($this->termType, TermType::class);
        Assert::isInstanceOf($this->department, Department::class);

        $departmentTermTypeSettings = DepartmentTermTypeSetting::whereDepartmentAndType($this->department, $this->termType)->get();

        return [
            'start_date' => [
                $this->isRequired($departmentTermTypeSettings, 'start_date'),
                new CalendarDateRule(),
                Rule::date()->beforeOrEqual(new CarbonImmutable(self::MAX_DATE)),
            ],
            'duration_in_days' => [
                $this->isRequired($departmentTermTypeSettings, 'duration_in_days'),
                'integer',
                'min:1',
                'max:9999',
            ],
            'penalty_amount_in_euros' => [
                $this->isRequired($departmentTermTypeSettings, 'penalty_amount_in_euros'),
                'integer',
                'min:0',
                'max:10000',
            ],
            'end_date' => [
                $this->isRequired($departmentTermTypeSettings, 'end_date'),
                new CalendarDateRule(),
                Rule::date()->beforeOrEqual(new CarbonImmutable(self::MAX_DATE)),
            ],
            'date_of_message' => [
                $this->isRequired($departmentTermTypeSettings, 'date_of_message'),
                new CalendarDateRule(),
                Rule::date()->beforeOrEqual(new CarbonImmutable(self::MAX_DATE)),
            ],
            'penalty_terms' => [
                'array',
            ],
            'penalty_terms.*.duration_in_days' => [
                'nullable',
                'required_with:penalty_terms.*.penalty_amount_in_euros',
                'integer',
                'min:1',
                'max:9999',
            ],
            'penalty_terms.*.penalty_amount_in_euros' => [
                'nullable',
                'required_with:penalty_terms.*.duration_in_days',
                'integer',
                'min:1',
                'max:10000',
            ],
        ];
    }

    /**
     * @param Collection<int, DepartmentTermTypeSetting> $departmentTermTypeSettings
     */
    private function isRequired(Collection $departmentTermTypeSettings, string $field): ?string
    {
        if ($departmentTermTypeSettings->firstWhere('field', $field)?->active) {
            return 'required';
        }

        return null;
    }
}
