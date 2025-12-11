<?php

declare(strict_types=1);

namespace App\Http\Requests\Authentication\Password;

use App\Http\Requests\FormRequest;
use Illuminate\Validation\Rules\Password;
use Webmozart\Assert\Assert;

class ResetPasswordStoreRequest extends FormRequest
{
    /**
     * @return array<string, array<string|Password>>
     */
    public function rules(): array
    {
        $password = Password::defaults();
        Assert::isInstanceOf($password, Password::class);

        return [
            'id' => [
                'required',
                'string',
            ],
            'token' => [
                'required',
                'string',
            ],
            'password' => [
                'required',
                'confirmed',
                $password,
            ],
        ];
    }
}
