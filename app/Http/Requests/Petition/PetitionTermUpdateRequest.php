<?php

declare(strict_types=1);

namespace App\Http\Requests\Petition;

use App\Http\Requests\FormRequest;
use App\Models\Department;
use App\Models\DepartmentTermTypeSetting;
use App\Models\PetitionTerm;
use App\Rules\CalendarDateRule;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Webmozart\Assert\Assert;

class PetitionTermUpdateRequest extends FormRequest
{
    private const string MAX_DATE = '2999-12-31';

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        Assert::isInstanceOf($this->petitionTerm, PetitionTerm::class);
        Assert::isInstanceOf($this->department, Department::class);

        $departmentTermTypeSettings = DepartmentTermTypeSetting::whereDepartmentAndType(
            $this->department,
            $this->petitionTerm->type,
        )->get();

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
            ],
            'end_date' => [
                $this->isRequired($departmentTermTypeSettings, 'end_date'),
                new CalendarDateRule(),
                Rule::date()->beforeOrEqual(new CarbonImmutable(self::MAX_DATE)),
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
