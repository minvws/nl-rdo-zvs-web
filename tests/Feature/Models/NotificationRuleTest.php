<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Enums\NotificationRuleAnchor;
use App\Enums\NotificationRuleRecipient;
use App\Enums\NotificationRuleTiming;
use App\Enums\PetitionEventType;
use App\Models\Department;
use App\Models\NotificationRule;
use App\Models\NotificationRuleDelivery;
use App\QueryBuilders\NotificationRuleQueryBuilder;
use App\ValueObjects\CalendarDate;
use Tests\Feature\FeatureTestCase;

class NotificationRuleTest extends FeatureTestCase
{
    public function testCastsAttributes(): void
    {
        $rule = NotificationRule::factory()->create([
            'anchor' => NotificationRuleAnchor::EVENT_END_DATE,
            'event_type' => PetitionEventType::MEETING_SCHEDULED,
            'offset_days' => -3,
            'active_from' => CalendarDate::create('2026-10-12'),
        ])->refresh();

        $this->assertSame(NotificationRuleAnchor::EVENT_END_DATE, $rule->anchor);
        $this->assertSame(PetitionEventType::MEETING_SCHEDULED, $rule->event_type);
        $this->assertSame(-3, $rule->offset_days);
        $this->assertSame(NotificationRuleTiming::BEFORE, $rule->timing());
        $this->assertSame(3, $rule->numberOfDays());
        $this->assertSame(NotificationRuleRecipient::PRIMARY_ASSIGNEE, $rule->recipient);
        $this->assertTrue($rule->active);
        $this->assertSame('2026-10-12', $rule->active_from->toDateString());
    }

    public function testPetitionDeadlineRuleKeepsItsEventType(): void
    {
        $rule = NotificationRule::factory()->forPetitionDeadline()->create()->refresh();

        $this->assertSame(NotificationRuleAnchor::PETITION_DEADLINE, $rule->anchor);
        $this->assertSame(PetitionEventType::ACTUAL_DISCLOSURE, $rule->event_type);
    }

    public function testDepartmentRelationship(): void
    {
        $department = Department::factory()->create();
        $rule = NotificationRule::factory()->recycle($department)->create();

        $this->assertEquals($department->id, $rule->department->id);
        $this->assertCount(1, $department->notificationRules);
    }

    public function testDeliveriesRelationship(): void
    {
        $rule = NotificationRule::factory()->create();
        $delivery = NotificationRuleDelivery::factory()->recycle($rule)->create();

        $this->assertCount(1, $rule->deliveries);
        $this->assertEquals($rule->id, $delivery->notificationRule->id);
        $this->assertNotNull($delivery->petition);
        $this->assertNotNull($delivery->created_at);
    }

    public function testQueryReturnsNotificationRuleQueryBuilder(): void
    {
        $this->assertInstanceOf(NotificationRuleQueryBuilder::class, NotificationRule::query());
    }

    public function testActiveAndForDepartmentScopes(): void
    {
        $department = Department::factory()->create();
        $activeRule = NotificationRule::factory()->recycle($department)->create();
        NotificationRule::factory()->recycle($department)->inactive()->create();
        NotificationRule::factory()->create();

        $rules = NotificationRule::query()->forDepartment($department)->active()->get();

        $this->assertCount(1, $rules);
        $this->assertEquals($activeRule->id, $rules->first()?->id);
    }
}
