<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\Http\Requests\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * @return array<string, array<string>>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
        ];
    }
}
