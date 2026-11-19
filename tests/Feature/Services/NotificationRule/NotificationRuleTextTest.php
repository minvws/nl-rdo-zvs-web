<?php

declare(strict_types=1);

namespace Tests\Feature\Services\NotificationRule;

use App\Enums\NotificationRuleAnchor;
use App\Enums\PetitionEventType;
use App\Enums\PetitionVariant;
use App\Models\NotificationRule;
use App\Services\NotificationRule\NotificationRuleText;
use App\ValueObjects\CalendarDate;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class NotificationRuleTextTest extends FeatureTestCase
{
    /**
     * @return array<string, array{NotificationRuleAnchor, int, string}>
     */
    public static function ruleActionProvider(): array
    {
        return [
            'before an event' => [NotificationRuleAnchor::EVENT_DATE, -3, 'Vindt plaats over 3 dagen'],
            'on an event' => [NotificationRuleAnchor::EVENT_DATE, 0, 'Vindt plaats op [datum]'],
            'after an event' => [NotificationRuleAnchor::EVENT_DATE, 1, 'Vond 1 dag geleden plaats'],
            'before an end' => [NotificationRuleAnchor::EVENT_END_DATE, -2, 'Loopt af over 2 dagen'],
            'on an end' => [NotificationRuleAnchor::EVENT_END_DATE, 0, 'Loopt af op [datum]'],
            'after an end' => [NotificationRuleAnchor::EVENT_END_DATE, 7, 'Is 7 dagen geleden afgelopen'],
            'before the deadline' => [NotificationRuleAnchor::PETITION_DEADLINE, -7, 'Beslistermijn eindigt over 7 dagen'],
            'on the deadline' => [NotificationRuleAnchor::PETITION_DEADLINE, 0, 'Beslistermijn eindigt op [datum]'],
            'after the deadline' => [NotificationRuleAnchor::PETITION_DEADLINE, 7, 'Beslistermijn is 7 dagen geleden verlopen'],
        ];
    }

    #[Test]
    #[DataProvider('ruleActionProvider')]
    public function withoutAnchorDateItDescribesTheRule(NotificationRuleAnchor $anchor, int $offsetDays, string $expected): void
    {
        $rule = NotificationRule::factory()->make(['anchor' => $anchor, 'offset_days' => $offsetDays]);

        $this->assertSame($expected, NotificationRuleText::action($rule));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function anchorDateActionProvider(): array
    {
        return [
            'the deadline is still ahead' => ['2026-10-19', 'Beslistermijn eindigt over 3 dagen'],
            'the deadline is tomorrow' => ['2026-10-17', 'Beslistermijn eindigt over 1 dag'],
            'the deadline is today' => ['2026-10-16', 'Beslistermijn eindigt op 16-10-2026'],
            'the deadline has passed' => ['2026-10-13', 'Beslistermijn is 3 dagen geleden verlopen'],
        ];
    }

    /**
     * A notification that goes out late tells the real distance to the date, not the rule's number of days.
     */
    #[Test]
    #[DataProvider('anchorDateActionProvider')]
    public function withAnAnchorDateItDescribesHowFarThatDateIsFromToday(string $anchorDate, string $expected): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-16 09:00:00'));
        $rule = NotificationRule::factory()->forPetitionDeadline()->make([
            'offset_days' => -3,
        ]);

        $this->assertSame($expected, NotificationRuleText::action($rule, CalendarDate::create($anchorDate)));
    }

    #[Test]
    public function itNamesTheEventItIsAbout(): void
    {
        $rule = NotificationRule::factory()->make(['event_type' => PetitionEventType::RECEIPT_OF_OBJECTION]);

        $this->assertSame(PetitionEventType::RECEIPT_OF_OBJECTION->label(), NotificationRuleText::regarding($rule));
        $this->assertSame(
            PetitionEventType::RECEIPT_OF_OBJECTION->label(PetitionVariant::BEZWAAR),
            NotificationRuleText::regarding($rule, PetitionVariant::BEZWAAR),
        );
    }

    /**
     * @return array<string, array{int, string}>
     */
    public static function termProvider(): array
    {
        return [
            'on the anchor' => [0, 'Direct'],
            'one day before' => [-1, '1 dag'],
            'days after' => [7, '7 dagen'],
        ];
    }

    #[Test]
    #[DataProvider('termProvider')]
    public function itDescribesTheTerm(int $offsetDays, string $expected): void
    {
        $rule = NotificationRule::factory()->make(['offset_days' => $offsetDays]);

        $this->assertSame($expected, NotificationRuleText::term($rule));
    }
}
