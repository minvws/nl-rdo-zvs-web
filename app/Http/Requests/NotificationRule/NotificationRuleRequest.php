<?php

declare(strict_types=1);

namespace App\Http\Requests\NotificationRule;

use App\Enums\NotificationRuleAnchor;
use App\Enums\NotificationRuleTiming;
use App\Enums\PetitionEventType;
use App\Http\Requests\FormRequest;
use App\Models\Department;
use App\Services\NotificationRule\NotificationRuleAnchorResolver;
use App\Services\NotificationRule\NotificationRuleEventTypes;
use App\ValueObjects\NotificationRuleData;
use Illuminate\Routing\Route;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Webmozart\Assert\Assert;

use function __;

class NotificationRuleRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(NotificationRuleEventTypes $eventTypes): array
    {
        return [
            'event_type' => [
                'required',
                Rule::in($eventTypes->forDepartment($this->department())->keys()),
            ],
            'anchor' => [
                'required',
                Rule::enum(NotificationRuleAnchor::class),
            ],
            'timing' => [
                'required',
                Rule::enum(NotificationRuleTiming::class),
            ],
            'days' => [
                'required',
                'integer',
                'min:0',
                'max:365',
            ],
            'active' => [
                'required',
                'boolean',
            ],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $eventType = PetitionEventType::tryFrom($this->string('event_type')->toString());

                if (
                    $this->input('anchor') === NotificationRuleAnchor::EVENT_END_DATE->value
                    && $eventType instanceof PetitionEventType
                    && !NotificationRuleAnchorResolver::hasEndDate($eventType)
                ) {
                    $validator->errors()->add('anchor', __('notification_rule.event_type_has_no_end'));
                }
            },
        ];
    }

    public function toNotificationRuleData(): NotificationRuleData
    {
        return new NotificationRuleData(
            eventType: $this->getEnum('event_type', PetitionEventType::class),
            anchor: $this->getEnum('anchor', NotificationRuleAnchor::class),
            offsetDays: $this->getEnum('timing', NotificationRuleTiming::class)->offset($this->integer('days')),
            active: $this->boolean('active'),
        );
    }

    /**
     * A notification on the anchor date itself has no number of days.
     */
    protected function prepareForValidation(): void
    {
        if ($this->input('timing') === NotificationRuleTiming::ON->value) {
            $this->merge(['days' => 0]);
        }
    }

    protected function department(): Department
    {
        $route = $this->route();
        Assert::isInstanceOf($route, Route::class);

        $department = $route->parameter('department');
        Assert::isInstanceOf($department, Department::class);

        return $department;
    }
}
