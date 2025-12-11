<?php

declare(strict_types=1);

namespace App\Http\Requests\PublicHoliday;

use App\Http\Requests\FormRequest;
use App\Rules\CalendarDateRule;

class PublicHolidayStoreRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:64',
            ],
            'date' => [
                'required',
                new CalendarDateRule(),
            ],
        ];
    }
}
