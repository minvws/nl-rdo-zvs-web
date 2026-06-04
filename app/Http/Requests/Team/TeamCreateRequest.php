<?php

declare(strict_types=1);

namespace App\Http\Requests\Team;

use App\Http\Requests\FormRequest;

class TeamCreateRequest extends FormRequest
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
            'active' => [
                'required',
                'boolean',
            ],
        ];
    }
}
