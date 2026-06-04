<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Support\Str;
use Override;

use function is_string;

class DecisionAttachRequest extends FormRequest
{
    /**
     * Lowercase the reference before validation rules are applied.
     */
    #[Override]
    protected function prepareForValidation(): void
    {
        $reference = $this->input('reference');

        if (is_string($reference) && $reference !== '') {
            $this->merge(['reference' => Str::lower($reference)]);
        }
    }

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
