<?php

declare(strict_types=1);

namespace App\Http\Requests\Authentication\Password;

use App\Http\Requests\FormRequest;

class ForgotPasswordStoreRequest extends FormRequest
{
    /**
     * @return array<string, array<string>>
     */
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email',
            ],
        ];
    }
}
