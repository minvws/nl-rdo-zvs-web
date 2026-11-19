<?php

declare(strict_types=1);

namespace Tests\Feature\Services\NotificationRule;

use App\Enums\NotificationRuleAnchor;
use App\Enums\PetitionEventType;
use App\Enums\RouteName;
use App\Models\NotificationRule;
use App\Models\Petition;
use App\Models\PetitionEvent;
use App\Models\User;
use App\Notifications\NotificationRuleTriggered;
use App\Services\NotificationRule\NotificationRuleEvaluator;
use App\ValueObjects\CalendarDate;
use Carbon\CarbonImmutable;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Symfony\Component\Mime\Email;
use Tests\Feature\FeatureTestCase;

use function config;
use function route;
use function sprintf;

class NotificationRuleEvaluatorTest extends FeatureTestCase
{
    private NotificationRuleEvaluator $evaluator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-10-20 10:00:00'));
        $this->evaluator = $this->app->make(NotificationRuleEvaluator::class);
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function triggerDateProvider(): array
    {
        return [
            'the day before the trigger date' => ['2026-10-07', false],
            'on the trigger date' => ['2026-10-06', true],
            'after the trigger date' => ['2026-10-05', true],
        ];
    }

    #[Test]
    #[DataProvider('triggerDateProvider')]
    public function itNotifiesThePrimaryAssigneeOnceTheTriggerDateIsReached(string $eventDate, bool $expectNotification): void
    {
        Notification::fake();
        $petition = $this->petitionWithEvent($eventDate);
        $rule = $this->rule($petition, ['offset_days' => 14]);

        $this->evaluator->evaluate($petition);

        $assignee = $this->assignee($petition);
        if ($expectNotification) {
            Notification::assertSentTo($assignee, NotificationRuleTriggered::class);
            $this->assertDatabaseHas('notification_rule_deliveries', [
                'notification_rule_id' => $rule->id->toString(),
                'petition_id' => $petition->id->toString(),
                'anchor_date' => $eventDate,
            ]);
        } else {
            Notification::assertNothingSent();
            $this->assertDatabaseCount('notification_rule_deliveries', 0);
        }
    }

    #[Test]
    public function aBeforeRuleCountsBackFromTheAnchor(): void
    {
        Notification::fake();
        $petition = Petition::factory()->withFirstAssignee()->create(['deadline_at' => CalendarDate::create('2026-10-23')]);
        PetitionEvent::factory()->for($petition)->withType(PetitionEventType::ACTUAL_DISCLOSURE)->create();
        NotificationRule::factory()->forPetitionDeadline()->recycle($petition->department)->create([
            'offset_days' => -3,
            'active_from' => CalendarDate::create('2026-10-01'),
        ]);

        $this->evaluator->evaluate($petition->refresh());

        Notification::assertSentTo($this->assignee($petition), NotificationRuleTriggered::class);
        $this->assertDatabaseHas('notification_rule_deliveries', ['trigger_date' => '2026-10-20']);
    }

    #[Test]
    public function anOnRuleNotifiesOnTheAnchorDate(): void
    {
        Notification::fake();
        $petition = $this->petitionWithEvent('2026-10-20');
        $this->rule($petition, ['offset_days' => 0]);

        $this->evaluator->evaluate($petition);

        Notification::assertSentTo($this->assignee($petition), NotificationRuleTriggered::class);
        $this->assertDatabaseHas('notification_rule_deliveries', ['trigger_date' => '2026-10-20']);
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function activeFromProvider(): array
    {
        return [
            'trigger date before the rule became active' => ['2026-10-11', false],
            'trigger date on the day the rule became active' => ['2026-10-10', true],
        ];
    }

    #[Test]
    #[DataProvider('activeFromProvider')]
    public function itNeverNotifiesForTriggerDatesBeforeTheRuleBecameActive(string $activeFrom, bool $expectNotification): void
    {
        Notification::fake();
        $petition = $this->petitionWithEvent('2026-10-10');
        $this->rule($petition, ['offset_days' => 0, 'active_from' => CalendarDate::create($activeFrom)]);

        $this->evaluator->evaluate($petition);

        Notification::assertSentToTimes($this->assignee($petition), NotificationRuleTriggered::class, $expectNotification ? 1 : 0);
    }

    #[Test]
    public function itSendsEachNotificationOnlyOnce(): void
    {
        Notification::fake();
        $petition = $this->petitionWithEvent('2026-10-10');
        $this->rule($petition, ['offset_days' => 0]);

        $this->evaluator->evaluate($petition);
        $this->evaluator->evaluate($petition);

        Notification::assertSentToTimes($this->assignee($petition), NotificationRuleTriggered::class, 1);
        $this->assertDatabaseCount('notification_rule_deliveries', 1);
    }

    #[Test]
    public function aChangedEventDateGivesANewNotification(): void
    {
        Notification::fake();
        $petition = $this->petitionWithEvent('2026-10-10');
        $this->rule($petition, ['offset_days' => 0]);
        $this->evaluator->evaluate($petition);

        $petition->petitionEvents()->update(['date' => '2026-10-12']);
        $this->evaluator->evaluate($petition->refresh());

        Notification::assertSentToTimes($this->assignee($petition), NotificationRuleTriggered::class, 2);
    }

    #[Test]
    public function itNotifiesForEveryMatchingRule(): void
    {
        Notification::fake();
        $petition = $this->petitionWithEvent('2026-10-10');
        $this->rule($petition, ['offset_days' => 0]);
        $this->rule($petition, ['offset_days' => 2]);

        $this->evaluator->evaluate($petition);

        Notification::assertSentToTimes($this->assignee($petition), NotificationRuleTriggered::class, 2);
    }

    #[Test]
    public function itIgnoresInactiveRulesAndRulesOfOtherDepartments(): void
    {
        Notification::fake();
        $petition = $this->petitionWithEvent('2026-10-10');
        $this->rule($petition, ['offset_days' => 0, 'active' => false]);
        NotificationRule::factory()->create([
            'offset_days' => 0,
            'event_type' => PetitionEventType::ACTUAL_DISCLOSURE,
            'active_from' => CalendarDate::create('2026-01-01'),
        ]);

        $this->evaluator->evaluate($petition);

        Notification::assertNothingSent();
    }

    #[Test]
    public function itDoesNothingForArchivedPetitions(): void
    {
        Notification::fake();
        $petition = $this->petitionWithEvent('2026-10-10');
        $petition->update(['archived_at' => CarbonImmutable::now()]);
        $this->rule($petition, ['offset_days' => 0]);

        $this->evaluator->evaluate($petition);

        Notification::assertNothingSent();
    }

    #[Test]
    public function itDoesNothingWithoutPrimaryAssignee(): void
    {
        Notification::fake();
        $petition = Petition::factory()->create();
        PetitionEvent::factory()->for($petition)->withType(PetitionEventType::ACTUAL_DISCLOSURE)->create([
            'date' => CalendarDate::create('2026-10-10'),
        ]);
        $this->rule($petition, ['offset_days' => 0]);

        $this->evaluator->evaluate($petition->refresh());

        Notification::assertNothingSent();
        $this->assertDatabaseCount('notification_rule_deliveries', 0);
    }

    #[Test]
    public function itSkipsAnInactivePrimaryAssigneeWithoutUsingUpTheNotification(): void
    {
        Notification::fake();
        $petition = $this->petitionWithEvent('2026-10-10');
        $this->rule($petition, ['offset_days' => 0]);
        $assignee = $this->assignee($petition);
        $assignee->update(['active' => false]);

        $this->evaluator->evaluate($petition->refresh());

        Notification::assertNothingSent();
        $this->assertDatabaseCount('notification_rule_deliveries', 0);

        $assignee->update(['active' => true]);
        $this->evaluator->evaluate($petition->refresh());

        Notification::assertSentToTimes($assignee, NotificationRuleTriggered::class, 1);
    }

    #[Test]
    public function itStoresTheNotificationForTheAssignee(): void
    {
        $petition = $this->petitionWithEvent('2026-10-10');
        $rule = $this->rule($petition, ['offset_days' => 0]);

        $this->evaluator->evaluate($petition);

        $notification = DatabaseNotification::query()->sole();
        $this->assertSame('notification-rule-triggered', $notification->type);
        $this->assertEquals($this->assignee($petition)->id, $notification->notifiable_id);
        $this->assertEquals([
            'title' => sprintf('Notificatie voor zaak %s', $petition->number),
            'regarding' => PetitionEventType::ACTUAL_DISCLOSURE->label($petition->petitionType->type),
            'action' => 'Vond 10 dagen geleden plaats',
            'petition_id' => $petition->id->toString(),
            'notification_rule_id' => $rule->id->toString(),
            'trigger_date' => '2026-10-10',
            'url' => route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
                'department' => $petition->department,
                'petition' => $petition->id,
            ], false),
        ], $notification->data);
    }

    #[Test]
    public function itMailsTheAssignee(): void
    {
        $petition = $this->petitionWithEvent('2026-10-13');
        $this->rule($petition, ['offset_days' => 7]);

        $this->evaluator->evaluate($petition);

        $transport = Mail::mailer()->getSymfonyTransport();
        $this->assertInstanceOf(ArrayTransport::class, $transport);
        $messages = $transport->messages();
        $this->assertCount(1, $messages);

        $email = $messages->first()?->getOriginalMessage();
        $this->assertInstanceOf(Email::class, $email);
        $this->assertSame($this->assignee($petition)->email, $email->getTo()[0]->getAddress());
        $this->assertSame(sprintf('[%s]: Notificatie voor zaak %s', config('app.name'), $petition->number), $email->getSubject());

        $html = (string) $email->getHtmlBody();
        $this->assertStringContainsString('Beste behandelaar,', $html);
        $this->assertStringContainsString(sprintf('Er is een notificatie aangemaakt op zaak %s.', $petition->number), $html);
        $this->assertStringContainsString('Actie: Vond 7 dagen geleden plaats', $html);
        $this->assertStringContainsString('bekijk de notificatie bij de notificatiebel', $html);
        $this->assertStringNotContainsString($petition->id->toString(), $html);
    }

    #[Test]
    public function aNotificationThatFailsIsNotRecordedAsSent(): void
    {
        Event::listen(NotificationSending::class, static function (): never {
            throw new RuntimeException('mail server down');
        });
        $petition = $this->petitionWithEvent('2026-10-10');
        $this->rule($petition, ['offset_days' => 0]);

        try {
            $this->evaluator->evaluate($petition);
            $this->fail('The failing notification should have been thrown');
        } catch (RuntimeException) {
            $this->assertDatabaseCount('notification_rule_deliveries', 0);
        }
    }

    private function petitionWithEvent(string $date): Petition
    {
        $petition = Petition::factory()->withFirstAssignee()->create();
        PetitionEvent::factory()->for($petition)->withType(PetitionEventType::ACTUAL_DISCLOSURE)->create([
            'date' => CalendarDate::create($date),
        ]);

        return $petition->refresh();
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function rule(Petition $petition, array $attributes): NotificationRule
    {
        return NotificationRule::factory()->recycle($petition->department)->create([
            'anchor' => NotificationRuleAnchor::EVENT_DATE,
            'event_type' => PetitionEventType::ACTUAL_DISCLOSURE,
            'active_from' => CalendarDate::create('2026-10-01'),
            ...$attributes,
        ]);
    }

    private function assignee(Petition $petition): User
    {
        $user = $petition->refresh()->firstAssignee?->user;
        $this->assertInstanceOf(User::class, $user);

        return $user;
    }
}
