<?php

declare(strict_types=1);

namespace App\Http\Requests\Authentication;

use App\Http\Requests\FormRequest;

class LoginStoreRequest extends FormRequest
{
    /**
     * @return array<string, array<string>>
     */
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'string',
                'email',
            ],
            'password' => [
                'required',
                'string',
            ],
        ];
    }
}
