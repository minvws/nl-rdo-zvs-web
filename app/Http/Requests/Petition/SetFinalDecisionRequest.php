<?php

declare(strict_types=1);

namespace App\Http\Requests\Petition;

use App\Http\Requests\FormRequest;
use App\Models\Petition;
use Illuminate\Validation\Rule;
use Override;
use Webmozart\Assert\Assert;

class SetFinalDecisionRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $petition = $this->route('petition');
        Assert::isInstanceOf($petition, Petition::class);

        return [
            'final_decision_id' => [
                'nullable',
                'uuid',
                Rule::exists('decision_petition', 'decision_id')
                    ->where('petition_id', $petition->id->toString()),
            ],
        ];
    }

    /**
     * @codeCoverageIgnore
     */
    #[Override]
    protected function prepareForValidation(): void
    {
        if ($this->input('final_decision_id') === '') {
            $this->merge(['final_decision_id' => null]);
        }
    }
}
