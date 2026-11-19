<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\NotificationRuleAnchor;
use App\Enums\NotificationRuleRecipient;
use App\Enums\PetitionEventType;
use App\Models\Department;
use App\Models\NotificationRule;
use App\ValueObjects\CalendarDate;

/**
 * @extends Factory<NotificationRule>
 */
class NotificationRuleFactory extends Factory
{
    /** @var class-string<NotificationRule> $model */
    protected $model = NotificationRule::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'department_id' => Department::factory(),
            'anchor' => NotificationRuleAnchor::EVENT_DATE,
            'event_type' => PetitionEventType::ACTUAL_DISCLOSURE,
            'offset_days' => $this->faker->numberBetween(-30, 30),
            'recipient' => NotificationRuleRecipient::PRIMARY_ASSIGNEE,
            'active' => true,
            'active_from' => CalendarDate::today(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['active' => false]);
    }

    public function forPetitionDeadline(): static
    {
        return $this->state(fn (): array => ['anchor' => NotificationRuleAnchor::PETITION_DEADLINE]);
    }
}
