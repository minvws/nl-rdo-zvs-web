<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Support\Facades\Crypt;

class ConfirmShowRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'confirm_url' => [
                'required',
                'string',
                'url',
            ],
            'cancel_url' => [
                'required',
                'string',
                'url',
            ],
            'message' => [
                'required',
                'string',
                'max:500',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'confirm_url' => Crypt::decryptString($this->string('confirm_url')->toString()),
            'cancel_url' => Crypt::decryptString($this->string('cancel_url')->toString()),
            'message' => $this->string('message')->toString(),
        ]);
    }
}
