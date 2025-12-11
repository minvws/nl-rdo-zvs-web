<?php

declare(strict_types=1);

namespace App\Http\Requests\Petition;

use App\Http\Requests\FormRequest;
use App\Rules\CalendarDateRule;
use Carbon\CarbonImmutable;
use Illuminate\Validation\Rule;

class PetitionDraftTermUpdateRequest extends FormRequest
{
    private const string MAX_DATE = '2999-12-31';

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'description' => [
                'nullable',
                'string',
                'max:255',
            ],
            'event_date' => [
                'nullable',
                new CalendarDateRule(),
                Rule::date()->beforeOrEqual(new CarbonImmutable(self::MAX_DATE)),
            ],
            'days_after_event' => [
                'nullable',
                'integer',
                'min:0',
                'max:9999',
            ],
            'date_withdrawal' => [
                'nullable',
                new CalendarDateRule(),
                Rule::date()->beforeOrEqual(new CarbonImmutable(self::MAX_DATE)),
            ],
            'days_after_date_withdrawal' => [
                'nullable',
                'integer',
                'min:0',
                'max:9999',
            ],
        ];
    }
}
