<?php

declare(strict_types=1);

namespace App\Http\Requests\Decision;

use App\Enums\DecisionType;
use App\Http\Requests\FormRequest;
use App\Rules\CalendarDateRule;
use Illuminate\Validation\Rules\Enum;
use Override;

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
        ];
    }

    #[Override]
    protected function prepareForValidation(): void
    {
        if ($this->filled('reference')) {
            $this->merge(['reference' => strtolower($this->string('reference')->toString())]);
        }
    }
}
