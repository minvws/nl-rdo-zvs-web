<?php

declare(strict_types=1);

namespace App\Http\Requests\Decision;

use App\Enums\DecisionType;
use App\Http\Requests\FormRequest;
use App\Models\Department;
use App\Rules\CalendarDateRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Override;
use Ramsey\Uuid\UuidInterface;
use Webmozart\Assert\Assert;

use function strtolower;

class DecisionCreateRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'reference' => ['nullable', 'string', 'unique:decisions,reference'],
            'date' => ['nullable', new CalendarDateRule()],
            'type' => ['required', new Enum(DecisionType::class)],
            'reviewbatch' => ['nullable', 'string', 'max:128'],
            'url' => ['nullable', 'string', 'url', 'max:1024'],
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

    private function getDepartmentId(): UuidInterface
    {
        $department = $this->route('department');
        Assert::isInstanceOf($department, Department::class);

        return $department->id;
    }
}
