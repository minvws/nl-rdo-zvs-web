<?php

declare(strict_types=1);

namespace App\Http\Requests\Contact;

use App\Enums\CorrespondencePreference;
use App\Http\Requests\FormRequest;
use Illuminate\Validation\Rule;

class ContactPetitionUpdateRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reference' => ['nullable', 'string', 'max:128'],
            'correspondence_preference' => ['nullable', Rule::enum(CorrespondencePreference::class)],
        ];
    }
}
