<?php

declare(strict_types=1);

namespace App\Http\Requests\Authentication\Password;

use App\Http\Requests\FormRequest;
use App\Rules\CurrentPassword;
use App\Services\Authentication\AuthenticationServiceInterface;
use App\Services\HashService;
use Illuminate\Foundation\Http\Attributes\ErrorBag;
use Illuminate\Validation\Rules\Password;
use Webmozart\Assert\Assert;

#[ErrorBag('updatePassword')]
class PasswordStoreRequest extends FormRequest
{
    /**
     * @return array<string, array<string|CurrentPassword|Password>>
     */
    public function rules(AuthenticationServiceInterface $authenticationService, HashService $hashService): array
    {
        $password = Password::defaults();
        Assert::isInstanceOf($password, Password::class);

        return [
            'current_password' => [
                'required',
                new CurrentPassword($authenticationService, $hashService),
            ],
            'password' => [
                'required',
                'confirmed',
                $password,
            ],
        ];
    }
}
