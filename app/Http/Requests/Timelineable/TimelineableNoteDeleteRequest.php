<?php

declare(strict_types=1);

namespace App\Http\Requests\Timelineable;

use App\Http\Requests\FormRequest;

class TimelineableNoteDeleteRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }
}
