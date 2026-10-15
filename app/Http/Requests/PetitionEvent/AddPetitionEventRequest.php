<?php

declare(strict_types=1);

namespace App\Http\Requests\PetitionEvent;

use App\Config\DepartmentConfigurationService;
use App\Enums\HearingForm;
use App\Enums\PetitionEventType;
use App\Enums\ResultType;
use App\Enums\SuspensionType;
use App\Enums\UnspecifiedAdjournmentMode;
use App\Http\Requests\FormRequest;
use App\Models\Department;
use App\Models\Petition;
use App\Rules\CalendarDateRule;
use App\Services\DerivedState;
use App\Services\PetitionEvent\PetitionEventsStorage;
use App\Validation\PetitionEvent\PetitionEventRules;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\PenaltyData;
use App\ValueObjects\PetitionEventData;
use App\ValueObjects\WizardEventCollection;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;
use Override;
use Throwable;
use Webmozart\Assert\Assert;

use function __;

class AddPetitionEventRequest extends FormRequest
{
    public function __construct(
        private readonly DerivedState $derivedState,
        private readonly PetitionEventsStorage $storage,
        private readonly DepartmentConfigurationService $departmentConfiguration,
    ) {
        parent::__construct();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(PetitionEventType::class)],
            'date' => ['required', new CalendarDateRule()],
            'duration' => [
                'nullable',
                'integer',
                'min:1',
                'max:730',
                Rule::requiredIf(fn(): bool => $this->collectsAdjournmentTerms()),
            ],
            'withdrawal_duration' => [
                'nullable',
                'integer',
                'min:1',
                'max:730',
                Rule::requiredIf(fn(): bool => $this->collectsAdjournmentTerms()),
            ],
            'term_deadline' => [
                'nullable',
                new CalendarDateRule(),
                Rule::requiredIf(function (): bool {
                    $type = $this->enum('type', PetitionEventType::class);

                    return $type instanceof PetitionEventType && $type->hasEndDate();
                }),
            ],
            'suspension_type' => [
                'nullable',
                Rule::enum(SuspensionType::class),
                Rule::requiredIf(fn(): bool => $this->input('type') === PetitionEventType::LETTER_OF_SUSPENSION_SENT->value),
            ],
            'result_type' => [
                'nullable',
                Rule::enum(ResultType::class),
                Rule::requiredIf(fn(): bool => $this->input('type') === PetitionEventType::FINAL_RESULT->value),
            ],
            'hearing_form' => [
                'nullable',
                Rule::enum(HearingForm::class),
                Rule::requiredIf(fn(): bool => $this->input('type') === PetitionEventType::HEARING_DATE->value),
            ],
            'reasoning' => [
                'nullable',
                'string',
                'max:1000',
                Rule::requiredIf(function (): bool {
                    $type = $this->enum('type', PetitionEventType::class);

                    return $type instanceof PetitionEventType && $type->requiresReasoning();
                }),
                Rule::when(function (): bool {
                    $type = $this->enum('type', PetitionEventType::class);

                    return $type?->reasoningSelectEnumClass() !== null;
                }, function (): array {
                    $type = $this->enum('type', PetitionEventType::class);

                    $reasoningSelectEnumClass = $type?->reasoningSelectEnumClass();
                    Assert::notNull($reasoningSelectEnumClass);

                    return [Rule::enum($reasoningSelectEnumClass)];
                }),
            ],
            'penalties' => ['sometimes', 'array', 'min:1'],
            'penalties.*.amount' => [
                'nullable',
                'integer',
                'min:1',
                'required_with:penalties.*.duration',
            ],
            'penalties.*.duration' => [
                'nullable',
                'integer',
                'min:1',
                'max:730',
                'required_with:penalties.*.amount',
            ],
        ];
    }

    /**
     * Converts validated request data to a PetitionEventData value object.
     * This method performs type conversion at the boundary layer.
     */
    public function toPetitionEventData(): PetitionEventData
    {
        $type = $this->getEnum('type', PetitionEventType::class);

        $suspensionType = $this->filled('suspension_type')
            ? $this->getEnum('suspension_type', SuspensionType::class)
            : null;

        $resultType = $this->filled('result_type')
            ? $this->getEnum('result_type', ResultType::class)
            : null;

        $hearingForm = $this->filled('hearing_form')
            ? $this->getEnum('hearing_form', HearingForm::class)
            : null;

        $reasoning = $this->filled('reasoning') ? $this->string('reasoning')->toString() : null;

        $penalties = $this->resolvePenalties();

        /** @var string $dateInput */
        $dateInput = $this->input('date');
        $eventDate = CalendarDate::create($dateInput);

        $durationInput = $this->resolveDuration($type, $eventDate);

        $withdrawalDuration = $type->hasWithdrawalDuration() && $this->filled('withdrawal_duration')
            ? $this->integer('withdrawal_duration')
            : null;

        return new PetitionEventData(
            type: $type,
            date: $eventDate,
            createdAt: CarbonImmutable::now(),
            duration: $durationInput,
            penalties: $penalties,
            suspensionType: $suspensionType,
            resultType: $resultType,
            hearingForm: $hearingForm,
            reasoning: $reasoning,
            withdrawalDuration: $withdrawalDuration,
        );
    }

    public function withValidator(Validator $validator): void
    {
        $petition = $this->route('petition');
        Assert::isInstanceOf($petition, Petition::class);

        $validator->after(function (Validator $validator) use ($petition): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            try {
                $type = $this->getEnum('type', PetitionEventType::class);
            } catch (Throwable) { // @codeCoverageIgnore
                return; // @codeCoverageIgnore
            } // @codeCoverageIgnore

            if (!$this->isTypeAvailableForDepartment($type)) {
                $validator->errors()->add('type', __('petition_event.validation.not_available_for_department'));

                return;
            }

            $wizardEvents = $this->storage->getWizardEvents($petition) ?? WizardEventCollection::make();

            $this->derivedState
                ->withUnspecifiedAdjournmentMode($this->unspecifiedAdjournmentMode())
                ->addEvents($wizardEvents->all());

            $event = $this->toPetitionEventData();

            $result = PetitionEventRules::validate($type, $event, $this->derivedState);

            foreach ($result->getErrors() as $field => $message) {
                $validator->errors()->add($field, $message);
            }
        });
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'type' => 'event type',
            'date' => 'event date',
            'duration' => 'duration',
            'withdrawal_duration' => 'withdrawal duration',
            'term_deadline' => 'term deadline',
            'suspension_type' => 'suspension type',
            'result_type' => 'result type',
            'hearing_form' => 'hearing form',
            'reasoning' => 'toelichting',
            'penalties.*.amount' => 'penalty amount',
            'penalties.*.duration' => 'penalty duration',
        ];
    }

    /**
     * Sluit gebeurtenistypes uit die dit team niet mag invoeren, ook als het formulier wordt omzeild.
     */
    private function isTypeAvailableForDepartment(PetitionEventType $type): bool
    {
        $department = $this->route('department');
        Assert::isInstanceOf($department, Department::class);

        return $this->departmentConfiguration->isEventTypeAvailable($department, $type);
    }

    /**
     * Departments that keep the running terms alive during an "ongespecificeerde aanhouding"
     * register both follow-up decision terms up front, so both day counts are mandatory there.
     */
    private function collectsAdjournmentTerms(): bool
    {
        $type = $this->enum('type', PetitionEventType::class);

        if (!$type instanceof PetitionEventType || !$type->hasWithdrawalDuration()) {
            return false;
        }

        return $this->unspecifiedAdjournmentMode() === UnspecifiedAdjournmentMode::CONTINUES_TERMS;
    }

    private function unspecifiedAdjournmentMode(): UnspecifiedAdjournmentMode
    {
        $department = $this->route('department');
        Assert::isInstanceOf($department, Department::class);

        return $this->departmentConfiguration->unspecifiedAdjournmentMode($department);
    }

    private function resolveDuration(PetitionEventType $type, CalendarDate $eventDate): ?int
    {
        if ($type->hasEndDate() && $this->filled('term_deadline')) {
            /** @var string $termDeadline */
            $termDeadline = $this->input('term_deadline');

            return $eventDate->diffInDays(CalendarDate::create($termDeadline));
        }

        return $this->filled('duration') ? $this->integer('duration') : null;
    }

    /**
     * Converts penalty arrays to PenaltyData value objects.
     *
     * @return array<int, PenaltyData>
     */
    private function resolvePenalties(): array
    {
        /** @var array<int, array{amount?: int|null, duration?: int|null}> $penaltyArrays */
        $penaltyArrays = $this->input('penalties', []);

        $penalties = [];
        foreach ($penaltyArrays as $penaltyArray) {
            if (!isset($penaltyArray['amount'], $penaltyArray['duration'])) {
                continue;
            }

            $penalties[] = new PenaltyData(amount: (int) $penaltyArray['amount'], duration: (int) $penaltyArray['duration']);
        }

        return $penalties;
    }
}
