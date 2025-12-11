<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Http\Requests\FormRequest;

class ApiAuthenticationRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'api_key' => ['required', 'string', 'min:64', 'max:64'],
            'api_secret' => ['required', 'string', 'min:128,max:128'],
        ];
    }
}
