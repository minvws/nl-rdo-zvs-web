<?php

declare(strict_types=1);

namespace App\Http\Requests\Authentication\OneTimePassword;

use App\Http\Requests\FormRequest;

class OneTimePasswordValidateRequest extends FormRequest
{
    /**
     * @return array<string, array<string>>
     */
    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'regex:/^[0-9]{6}$/',
            ],
        ];
    }
}
