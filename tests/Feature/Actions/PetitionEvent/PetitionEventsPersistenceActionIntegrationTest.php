<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\PetitionEvent;

use App\Actions\PetitionEvent\PetitionEventsPersistenceAction;
use App\Enums\Authorization\Permission;
use App\Enums\HearingForm;
use App\Enums\PetitionEventChange;
use App\Enums\PetitionEventType;
use App\Enums\PetitionVariant;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionEvent;
use App\Models\PetitionStatus;
use App\Models\PetitionType;
use App\Models\TimelineItem;
use App\Models\User;
use App\ValueObjects\CalendarDate;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;
use Tests\Helpers\ConfigHelper;

use function app;
use function array_column;

final class PetitionEventsPersistenceActionIntegrationTest extends FeatureTestCase
{
    #[Test]
    public function testPersistingBezwaarEventsUpdatesDateOfEntryAndDeadline(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create(['type' => PetitionVariant::BEZWAAR]);
        PetitionStatus::factory()->recycle($department)->for($petitionType)->create();

        $petition = Petition::factory()
            ->recycle($department)
            ->for($petitionType)
            ->create([
                'date_of_entry' => CalendarDate::create('2025-01-01'),
                'deadline_at' => null,
            ]);

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $petitionEventDataArray = [
            [
                'date' => '2025-01-13',
                'type' => PetitionEventType::PRIMARY_DECISION->value,
                'duration' => 42,
                'created_at' => CarbonImmutable::now(),
            ],
            [
                'date' => '2025-02-18',
                'type' => PetitionEventType::RECEIPT_OF_OBJECTION->value,
                'duration' => 42,
                'created_at' => CarbonImmutable::now(),
            ],
        ];

        $action = app(PetitionEventsPersistenceAction::class);
        $action->execute($petition, $petitionEventDataArray, $user);

        $petition->refresh();

        $this->assertTrue($petition->date_of_entry->equals(CalendarDate::create('2025-02-18')));
        $this->assertNotNull($petition->deadline_at);
        $this->assertTrue($petition->deadline_at->equals(CalendarDate::create('2025-04-07')));
    }

    #[Test]
    public function testPersistingWooVerzoekEventsUpdatesDateOfEntryAndDeadline(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create(['type' => PetitionVariant::WOO_VERZOEK]);
        PetitionStatus::factory()->recycle($department)->for($petitionType)->create();

        $petition = Petition::factory()
            ->recycle($department)
            ->for($petitionType)
            ->create([
                'date_of_entry' => CalendarDate::create('2025-01-01'),
                'deadline_at' => null,
            ]);

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $petitionEventDataArray = [
            [
                'date' => '2025-01-10',
                'type' => PetitionEventType::PETITION_RECEIVED->value,
                'duration' => 28,
                'created_at' => CarbonImmutable::now(),
            ],
        ];

        $action = app(PetitionEventsPersistenceAction::class);
        $action->execute($petition, $petitionEventDataArray, $user);

        $petition->refresh();

        $this->assertTrue($petition->date_of_entry->equals(CalendarDate::create('2025-01-10')));
        $this->assertNotNull($petition->deadline_at);
    }

    #[Test]
    public function testDeletingAllEventsDoesNotResetDeadline(): void
    {
        ConfigHelper::set('app.features.term_engine_v2', false);

        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create(['type' => PetitionVariant::BEZWAAR]);
        PetitionStatus::factory()->recycle($department)->for($petitionType)->create();

        $petition = Petition::factory()
            ->recycle($department)
            ->for($petitionType)
            ->create([
                'date_of_entry' => CalendarDate::create('2025-01-01'),
                'deadline_at' => CalendarDate::create('2025-02-01'),
            ]);

        PetitionEvent::factory()
            ->for($petition)
            ->create([
                'type' => PetitionEventType::RECEIPT_OF_OBJECTION,
                'date' => CalendarDate::create('2025-01-05'),
                'duration' => 42,
            ]);

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $action = app(PetitionEventsPersistenceAction::class);
        $action->execute($petition, [], $user);

        $petition->refresh();

        // When all events are deleted, isTermEngineConverted() returns false and the totals
        // action skips — deadline remains as previously set.
        $this->assertNotNull($petition->deadline_at);
        $this->assertEquals('2025-02-01', $petition->deadline_at->toDateString());
    }

    #[Test]
    public function testUpdatingEventsRecalculatesDeadline(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create(['type' => PetitionVariant::BEZWAAR]);
        PetitionStatus::factory()->recycle($department)->for($petitionType)->create();

        $petition = Petition::factory()
            ->recycle($department)
            ->for($petitionType)
            ->create([
                'date_of_entry' => CalendarDate::create('2025-01-05'),
                'deadline_at' => CalendarDate::create('2025-03-01'),
            ]);

        PetitionEvent::factory()
            ->for($petition)
            ->create([
                'type' => PetitionEventType::RECEIPT_OF_OBJECTION,
                'date' => CalendarDate::create('2025-01-05'),
                'duration' => 30,
            ]);

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $newPetitionEventDataArray = [
            [
                'date' => '2025-01-05',
                'type' => PetitionEventType::RECEIPT_OF_OBJECTION->value,
                'duration' => 42,
                'created_at' => CarbonImmutable::now(),
            ],
        ];

        $action = app(PetitionEventsPersistenceAction::class);
        $action->execute($petition, $newPetitionEventDataArray, $user);

        $petition->refresh();

        $this->assertNotEquals('2025-03-01', $petition->deadline_at->toDateString());
    }

    #[Test]
    public function testPersistingEventsCalculatesAndCachesSuspensionDays(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create(['type' => PetitionVariant::BEZWAAR]);
        PetitionStatus::factory()->recycle($department)->for($petitionType)->create();

        $petition = Petition::factory()
            ->recycle($department)
            ->for($petitionType)
            ->create([
                'date_of_entry' => CalendarDate::create('2025-01-01'),
                'deadline_at' => null,
                'total_days_suspended' => 0,
            ]);

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $petitionEventDataArray = [
            [
                'date' => '2025-01-15',
                'type' => PetitionEventType::RECEIPT_OF_OBJECTION->value,
                'duration' => 42,
                'created_at' => CarbonImmutable::now(),
            ],
            [
                'date' => '2025-01-20',
                'type' => PetitionEventType::LETTER_OF_SUSPENSION_SENT->value,
                'duration' => 10,
                'suspension_type' => 'suspension',
                'created_at' => CarbonImmutable::now(),
            ],
        ];

        $action = app(PetitionEventsPersistenceAction::class);
        $action->execute($petition, $petitionEventDataArray, $user);

        $petition->refresh();

        $this->assertGreaterThan(0, $petition->total_days_suspended);
    }

    #[Test]
    public function testPersistingHearingFormStoresTheColumn(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create(['type' => PetitionVariant::BEZWAAR]);
        PetitionStatus::factory()->recycle($department)->for($petitionType)->create();

        $petition = Petition::factory()
            ->recycle($department)
            ->for($petitionType)
            ->create();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $petitionEventDataArray = [
            [
                'date' => '2025-01-13',
                'type' => PetitionEventType::HEARING_DATE->value,
                'hearing_form' => HearingForm::DIGITAL->value,
                'created_at' => CarbonImmutable::now(),
            ],
        ];

        $action = app(PetitionEventsPersistenceAction::class);
        $action->execute($petition, $petitionEventDataArray, $user);

        $this->assertDatabaseHas(PetitionEvent::class, [
            'petition_id' => $petition->id,
            'type' => PetitionEventType::HEARING_DATE->value,
            'hearing_form' => HearingForm::DIGITAL->value,
        ]);
    }

    #[Test]
    public function testDeletingAllEventsDoesNotResetCachedTotals(): void
    {
        $this->app['config']->set('app.features.term_engine_v2', false);

        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create(['type' => PetitionVariant::BEZWAAR]);
        PetitionStatus::factory()->recycle($department)->for($petitionType)->create();

        $petition = Petition::factory()
            ->recycle($department)
            ->for($petitionType)
            ->create([
                'date_of_entry' => CalendarDate::create('2025-01-01'),
                'deadline_at' => CalendarDate::create('2025-03-01'),
                'total_days_suspended' => 5,
                'igs_penalty_today' => 100,
                'bnt_penalty_today' => 50,
                'igs_forfeited' => 500,
                'bnt_forfeited' => 250,
                'igs_penalty_maximum' => 1000,
                'bnt_penalty_maximum' => 500,
            ]);

        PetitionEvent::factory()
            ->for($petition)
            ->create([
                'type' => PetitionEventType::RECEIPT_OF_OBJECTION,
                'date' => CalendarDate::create('2025-01-05'),
                'duration' => 42,
            ]);

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $action = app(PetitionEventsPersistenceAction::class);
        $action->execute($petition, [], $user);

        $petition->refresh();

        // When all events are deleted, isTermEngineConverted() returns false and the totals
        // action skips — cached values remain unchanged from what was set before.
        $this->assertNotNull($petition->deadline_at);
        $this->assertEquals('2025-03-01', $petition->deadline_at->toDateString());
        $this->assertEquals(5, $petition->total_days_suspended);
        $this->assertEquals(100, $petition->igs_penalty_today);
        $this->assertEquals(50, $petition->bnt_penalty_today);
        $this->assertEquals(500, $petition->igs_forfeited);
        $this->assertEquals(250, $petition->bnt_forfeited);
        $this->assertEquals(1000, $petition->igs_penalty_maximum);
        $this->assertEquals(500, $petition->bnt_penalty_maximum);
    }

    #[Test]
    public function testPersistingIGSEventCalculatesIGSPenaltyMaximum(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create(['type' => PetitionVariant::BEZWAAR]);
        PetitionStatus::factory()->recycle($department)->for($petitionType)->create();

        $petition = Petition::factory()
            ->recycle($department)
            ->for($petitionType)
            ->create([
                'date_of_entry' => CalendarDate::create('2025-01-01'),
                'deadline_at' => null,
                'igs_penalty_maximum' => 0,
            ]);

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $petitionEventDataArray = [
            [
                'date' => '2025-01-05',
                'type' => PetitionEventType::RECEIPT_OF_OBJECTION->value,
                'duration' => 42,
                'created_at' => CarbonImmutable::now(),
            ],
            [
                'date' => '2025-02-01',
                'type' => PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED->value,
                'duration' => 28,
                'penalties' => [
                    ['amount' => 50, 'duration' => 10],
                    ['amount' => 75, 'duration' => 10],
                ],
                'created_at' => CarbonImmutable::now(),
            ],
        ];

        $action = app(PetitionEventsPersistenceAction::class);
        $action->execute($petition, $petitionEventDataArray, $user);

        $petition->refresh();

        $this->assertEquals(1250, $petition->igs_penalty_maximum);
    }

    #[Test]
    public function testPersistingBNTEventCalculatesBNTPenaltyMaximum(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create(['type' => PetitionVariant::BEZWAAR]);
        PetitionStatus::factory()->recycle($department)->for($petitionType)->create();

        $petition = Petition::factory()
            ->recycle($department)
            ->for($petitionType)
            ->create([
                'date_of_entry' => CalendarDate::create('2025-01-01'),
                'deadline_at' => null,
                'bnt_penalty_maximum' => 0,
            ]);

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $petitionEventDataArray = [
            [
                'date' => '2025-01-05',
                'type' => PetitionEventType::RECEIPT_OF_OBJECTION->value,
                'duration' => 42,
                'created_at' => CarbonImmutable::now(),
            ],
            [
                'date' => '2025-02-01',
                'type' => PetitionEventType::APPEAL_DECISION_NOT_TIMELY->value,
                'duration' => 28,
                'penalties' => [
                    ['amount' => 300, 'duration' => 14],
                    ['amount' => 200, 'duration' => 14],
                ],
                'created_at' => CarbonImmutable::now(),
            ],
        ];

        $action = app(PetitionEventsPersistenceAction::class);
        $action->execute($petition, $petitionEventDataArray, $user);

        $petition->refresh();

        $this->assertEquals(7000, $petition->bnt_penalty_maximum);
    }

    #[Test]
    public function testTimelineItemRecordsAddedAndRemovedEvents(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create(['type' => PetitionVariant::BEZWAAR]);
        PetitionStatus::factory()->recycle($department)->for($petitionType)->create();
        $petition = Petition::factory()->recycle($department)->for($petitionType)->create();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $receipt = [
            'date' => '2025-02-18',
            'type' => PetitionEventType::RECEIPT_OF_OBJECTION->value,
            'duration' => 42,
            'created_at' => CarbonImmutable::now(),
        ];
        $decision = [
            'date' => '2025-01-13',
            'type' => PetitionEventType::PRIMARY_DECISION->value,
            'duration' => 42,
            'created_at' => CarbonImmutable::now(),
        ];

        $action = app(PetitionEventsPersistenceAction::class);
        $action->execute($petition, [$receipt, $decision], $user);
        $firstItemIds = $petition->timelineItems()->pluck('internal_id')->all();

        // Remove the receipt and add a petition received event, the total number of events stays the same.
        $received = [
            'date' => '2025-03-01',
            'type' => PetitionEventType::PETITION_RECEIVED->value,
            'duration' => 42,
            'created_at' => CarbonImmutable::now(),
        ];
        $action->execute($petition, [$decision, $received], $user);

        $item = $petition->timelineItems()->whereNotIn('internal_id', $firstItemIds)->first();
        $this->assertNotNull($item);
        $this->assertEquals([
            ['type' => PetitionEventType::RECEIPT_OF_OBJECTION->value, 'change' => PetitionEventChange::REMOVED->value],
            ['type' => PetitionEventType::PRIMARY_DECISION->value, 'change' => PetitionEventChange::UNCHANGED->value],
            ['type' => PetitionEventType::PETITION_RECEIVED->value, 'change' => PetitionEventChange::ADDED->value],
        ], $item->data['events']);
    }

    #[Test]
    public function testTimelineItemRecordsChangedEventAsRemovedAndAdded(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create(['type' => PetitionVariant::BEZWAAR]);
        PetitionStatus::factory()->recycle($department)->for($petitionType)->create();
        $petition = Petition::factory()->recycle($department)->for($petitionType)->create();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $decision = [
            'date' => '2025-01-13',
            'type' => PetitionEventType::PRIMARY_DECISION->value,
            'duration' => 42,
            'created_at' => CarbonImmutable::now(),
        ];

        $action = app(PetitionEventsPersistenceAction::class);
        $action->execute($petition, [$decision], $user);
        $firstItemIds = $petition->timelineItems()->pluck('internal_id')->all();

        $action->execute($petition, [[...$decision, 'date' => '2025-01-20']], $user);

        $item = $petition->timelineItems()->whereNotIn('internal_id', $firstItemIds)->first();
        $this->assertNotNull($item);
        $this->assertEquals([
            ['type' => PetitionEventType::PRIMARY_DECISION->value, 'change' => PetitionEventChange::ADDED->value],
            ['type' => PetitionEventType::PRIMARY_DECISION->value, 'change' => PetitionEventChange::REMOVED->value],
        ], $item->data['events']);
    }

    #[Test]
    public function testTimelineItemRecordsUnchangedEventsWhenNothingChanged(): void
    {
        $decision = $this->eventData(PetitionEventType::PRIMARY_DECISION, '2025-01-13');
        $receipt = $this->eventData(PetitionEventType::RECEIPT_OF_OBJECTION, '2025-02-18');

        $item = $this->saveTwice([$decision, $receipt], [$decision, $receipt]);

        $this->assertSame(
            [PetitionEventChange::UNCHANGED->value, PetitionEventChange::UNCHANGED->value],
            array_column($item->data['events'], 'change'),
        );
        $this->assertSame(2, $item->data['count']);
    }

    #[Test]
    public function testTimelineItemMatchesDuplicateEventsOneToOne(): void
    {
        $decision = $this->eventData(PetitionEventType::PRIMARY_DECISION, '2025-01-13');

        $item = $this->saveTwice([$decision, $decision], [$decision]);

        $this->assertSame([
            ['type' => PetitionEventType::PRIMARY_DECISION->value, 'change' => PetitionEventChange::UNCHANGED->value],
            ['type' => PetitionEventType::PRIMARY_DECISION->value, 'change' => PetitionEventChange::REMOVED->value],
        ], $item->data['events']);
    }

    #[Test]
    public function testTimelineItemRecordsAllEventsAsRemovedWhenCalendarIsEmptied(): void
    {
        $decision = $this->eventData(PetitionEventType::PRIMARY_DECISION, '2025-01-13');
        $receipt = $this->eventData(PetitionEventType::RECEIPT_OF_OBJECTION, '2025-02-18');

        $item = $this->saveTwice([$decision, $receipt], []);

        $this->assertSame(
            [PetitionEventChange::REMOVED->value, PetitionEventChange::REMOVED->value],
            array_column($item->data['events'], 'change'),
        );
        $this->assertSame(0, $item->data['count']);
    }

    #[Test]
    public function testTimelineItemTreatsEventsWithDifferentDetailsAsDifferent(): void
    {
        $decision = $this->eventData(PetitionEventType::PRIMARY_DECISION, '2025-01-13');

        $item = $this->saveTwice([$decision], [[...$decision, 'duration' => 43]]);

        $this->assertSame(
            [PetitionEventChange::ADDED->value, PetitionEventChange::REMOVED->value],
            array_column($item->data['events'], 'change'),
        );
    }

    #[Test]
    public function testTimelineItemPlacesRemovedEventAtItsPreviousPosition(): void
    {
        $first = $this->eventData(PetitionEventType::PRIMARY_DECISION, '2025-01-13');
        $second = $this->eventData(PetitionEventType::RECEIPT_OF_OBJECTION, '2025-02-18');
        $third = $this->eventData(PetitionEventType::PETITION_RECEIVED, '2025-03-01');

        $item = $this->saveTwice([$first, $second, $third], [$first, $third]);

        $this->assertSame([
            ['type' => PetitionEventType::PRIMARY_DECISION->value, 'change' => PetitionEventChange::UNCHANGED->value],
            ['type' => PetitionEventType::RECEIPT_OF_OBJECTION->value, 'change' => PetitionEventChange::REMOVED->value],
            ['type' => PetitionEventType::PETITION_RECEIVED->value, 'change' => PetitionEventChange::UNCHANGED->value],
        ], $item->data['events']);
    }

    #[Test]
    public function testTimelineItemTreatsStoredEmptyPenaltiesAsUnchanged(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create(['type' => PetitionVariant::BEZWAAR]);
        PetitionStatus::factory()->recycle($department)->for($petitionType)->create();
        $petition = Petition::factory()->recycle($department)->for($petitionType)->create();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        // Events stored by other sources than the wizard, such as the seeders, have an empty list of penalties.
        PetitionEvent::factory()->for($petition)->create([
            'type' => PetitionEventType::PRIMARY_DECISION,
            'date' => CalendarDate::create('2025-01-13'),
            'duration' => 42,
            'penalties' => [],
        ]);

        app(PetitionEventsPersistenceAction::class)->execute(
            $petition,
            [$this->eventData(PetitionEventType::PRIMARY_DECISION, '2025-01-13')],
            $user,
        );

        $item = $petition->timelineItems()->first();
        $this->assertNotNull($item);
        $this->assertSame(
            [PetitionEventChange::UNCHANGED->value],
            array_column($item->data['events'], 'change'),
        );
    }

    /**
     * @return array{type: string, date: string, duration: int, created_at: CarbonImmutable}
     */
    private function eventData(PetitionEventType $type, string $date): array
    {
        return [
            'date' => $date,
            'type' => $type->value,
            'duration' => 42,
            'created_at' => CarbonImmutable::now(),
        ];
    }

    /**
     * Saves the first set of events, then the second, and returns the timeline item of the second save.
     *
     * @param array<int, array<string, mixed>> $first
     * @param array<int, array<string, mixed>> $second
     */
    private function saveTwice(array $first, array $second): TimelineItem
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create(['type' => PetitionVariant::BEZWAAR]);
        PetitionStatus::factory()->recycle($department)->for($petitionType)->create();
        $petition = Petition::factory()->recycle($department)->for($petitionType)->create();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $action = app(PetitionEventsPersistenceAction::class);
        $action->execute($petition, $first, $user);
        $firstItemIds = $petition->timelineItems()->pluck('internal_id')->all();

        $action->execute($petition, $second, $user);

        $item = $petition->timelineItems()->whereNotIn('internal_id', $firstItemIds)->first();
        $this->assertNotNull($item);

        return $item;
    }
}
