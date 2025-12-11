<?php

declare(strict_types=1);

namespace App\Http\Requests\Authentication\OneTimePassword;

use App\Http\Requests\FormRequest;

class OneTimePasswordConfirmRequest extends FormRequest
{
    /**
     * @return array<string, array<string>>
     */
    public function rules(): array
    {
        return [
            'otp_confirmation' => [
                'required',
                'string',
                'regex:/^[0-9]{6}$/',
            ],
        ];
    }
}
