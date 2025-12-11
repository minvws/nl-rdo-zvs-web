<?php

declare(strict_types=1);

namespace App\Http\Requests\Decision;

use App\Enums\DecisionType;
use App\Http\Requests\FormRequest;
use App\Rules\CalendarDateRule;
use Illuminate\Validation\Rules\Enum;

class DecisionCreateRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'reference' => ['string', 'nullable'],
            'date' => ['nullable', new CalendarDateRule()],
            'type' => ['required', new Enum(DecisionType::class)],
        ];
    }
}
