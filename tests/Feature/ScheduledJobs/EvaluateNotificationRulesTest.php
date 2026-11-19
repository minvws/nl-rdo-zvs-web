<?php

declare(strict_types=1);

namespace Tests\Feature\ScheduledJobs;

use App\Enums\PetitionEventType;
use App\Models\Department;
use App\Models\NotificationRule;
use App\Models\Petition;
use App\Models\PetitionEvent;
use App\Notifications\NotificationRuleTriggered;
use App\ScheduledJobs\EvaluateNotificationRules;
use App\Services\NotificationRule\NotificationRuleEvaluator;
use App\ValueObjects\CalendarDate;
use Carbon\CarbonImmutable;
use Exception;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

use function app;
use function collect;
use function sprintf;

final class EvaluateNotificationRulesTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-10-20 07:00:00'));
    }

    #[Test]
    public function itNotifiesForDeadlinesThatComeDue(): void
    {
        Notification::fake();
        $petition = $this->petition(['deadline_at' => CalendarDate::create('2026-10-23')]);
        NotificationRule::factory()->forPetitionDeadline()->recycle($petition->department)->create([
            'offset_days' => -3,
            'active_from' => CalendarDate::create('2026-10-01'),
        ]);

        app(EvaluateNotificationRules::class)();

        $assignee = $petition->refresh()->firstAssignee?->user;
        $this->assertNotNull($assignee);
        Notification::assertSentTo($assignee, NotificationRuleTriggered::class);
    }

    #[Test]
    public function itOnlyEvaluatesRunningAssignedPetitionsOfDepartmentsWithActiveRules(): void
    {
        $department = Department::factory()->create();
        NotificationRule::factory()->recycle($department)->create();
        $petition = $this->petition([], $department);
        Petition::factory()->recycle($department)->create();
        $this->petition(['archived_at' => CarbonImmutable::now()], $department);
        $this->petition();
        $inactiveDepartment = Department::factory()->create();
        NotificationRule::factory()->recycle($inactiveDepartment)->inactive()->create();
        $this->petition([], $inactiveDepartment);

        $evaluator = Mockery::mock(NotificationRuleEvaluator::class);
        $evaluator->shouldReceive('evaluate')
            ->once()
            ->withArgs(static fn(Petition $evaluated): bool => $evaluated->id->equals($petition->id));
        $this->app->instance(NotificationRuleEvaluator::class, $evaluator);

        app(EvaluateNotificationRules::class)();
    }

    #[Test]
    public function itLogsAFailingPetitionAndContinues(): void
    {
        Log::spy();
        $department = Department::factory()->create();
        NotificationRule::factory()->recycle($department)->create();
        $failing = $this->petition([], $department);
        $this->petition([], $department);

        $exception = new Exception('failure');

        $evaluator = Mockery::mock(NotificationRuleEvaluator::class);
        $evaluator->shouldReceive('evaluate')
            ->twice()
            ->andReturnUsing(static function (Petition $petition) use ($failing, $exception): void {
                if ($petition->id->equals($failing->id)) {
                    throw $exception;
                }
            });
        $this->app->instance(NotificationRuleEvaluator::class, $evaluator);

        app(EvaluateNotificationRules::class)();

        Log::shouldHaveReceived('error')
            ->once()
            ->with(sprintf('Failed to evaluate notification rules for petition ID: %s', $failing->id), ['exception' => $exception]);
    }

    #[Test]
    public function itIsScheduledDailyAfterThePetitionTotals(): void
    {
        $events = collect(app(Schedule::class)->events())->keyBy(static fn(Event $event): string => (string) $event->description);

        $this->assertSame('0 7 * * *', $events['Evaluate Notification Rules']->expression);
        $this->assertSame('Europe/Amsterdam', $events['Evaluate Notification Rules']->timezone);
        $this->assertSame('0 1 * * *', $events['Set Petition Totals']->expression);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function petition(array $attributes = [], ?Department $department = null): Petition
    {
        $petition = Petition::factory()
            ->recycle($department ?? Department::factory()->create())
            ->withFirstAssignee()
            ->create($attributes);
        PetitionEvent::factory()->for($petition)->withType(PetitionEventType::ACTUAL_DISCLOSURE)->create([
            'date' => CalendarDate::create('2026-01-01'),
        ]);

        return $petition;
    }
}
