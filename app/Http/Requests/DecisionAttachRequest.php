<?php

declare(strict_types=1);

namespace App\Http\Requests;

class DecisionAttachRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reference' => ['required', 'string', 'exists:decisions'],
        ];
    }
}
