<?php

declare(strict_types=1);

namespace App\Http\Requests\Petition;

use App\Http\Requests\FormRequest;
use App\Rules\CalendarDateRule;

class PetitionDeliverableCreateRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'deadline_at' => [
                'required',
                new CalendarDateRule(),
            ],
            'description' => [
                'nullable',
                'string',
                'max:124',
            ],
        ];
    }
}
