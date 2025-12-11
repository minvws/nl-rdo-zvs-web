<?php

declare(strict_types=1);

namespace App\Http\Requests\Petition;

use App\Http\Requests\FormRequest;
use App\Models\Petition;
use App\Rules\CalendarDateRule;
use Carbon\CarbonImmutable;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Webmozart\Assert\Assert;

use function __;

class PetitionDraftTermCreateRequest extends FormRequest
{
    private const string MAX_DATE = '2999-12-31';

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        Assert::isInstanceOf($this->petition, Petition::class);

        return [
            'description' => [
                'nullable',
                'string',
                'max:255',
            ],
            'event_date' => [
                'nullable',
                new CalendarDateRule(),
                Rule::date()->beforeOrEqual(new CarbonImmutable(self::MAX_DATE)),
            ],
            'days_after_event' => [
                'nullable',
                'integer',
                'min:0',
                'max:9999',
            ],
            'date_withdrawal' => [
                'nullable',
                new CalendarDateRule(),
                Rule::date()->beforeOrEqual(new CarbonImmutable(self::MAX_DATE)),
            ],
            'days_after_date_withdrawal' => [
                'nullable',
                'integer',
                'min:0',
                'max:9999',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        if (!$this->petition instanceof Petition) {
            return; // @codeCoverageIgnore
        }

        $validator->after(function (Validator $validator): void {
            if (!$this->petition instanceof Petition) {
                return; // @codeCoverageIgnore
            }
            if ($this->petition->draftTerm !== null) {
                $validator->errors()->add('petition', __('draft_term.validation.petition_already_has_draft_term'));
            }

            if ($this->petition->petitionTerms->latestEndDate() === null) {
                $validator->errors()->add('petition', __('draft_term.validation.petition_must_have_existing_terms'));
            }
        });
    }
}
