<?php

declare(strict_types=1);

namespace App\Http\Requests\Contact;

use App\Enums\ContactRole;
use App\Http\Requests\FormRequest;
use Illuminate\Validation\Rule;

class ContactAttachRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'role' => ['required', Rule::enum(ContactRole::class)],
        ];
    }
}
