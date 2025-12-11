<?php

declare(strict_types=1);

namespace App\Http\Requests\Petition;

use App\Http\Requests\FormRequest;

class PetitionAttachRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'number' => ['required', 'string', 'exists:petitions'],
        ];
    }
}
