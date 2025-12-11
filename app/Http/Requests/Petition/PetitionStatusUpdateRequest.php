<?php

declare(strict_types=1);

namespace App\Http\Requests\Petition;

use App\Http\Requests\FormRequest;
use App\Rules\CalendarDateRule;

class PetitionStatusUpdateRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'petition_status_id' => [
                'required',
                'uuid',
            ],
            'petition_status_date' => [
                'required',
                new CalendarDateRule(),
            ],
            'petition_status_comment' => [
                'nullable',
                'string',
                'max:256',
            ],
        ];
    }
}
