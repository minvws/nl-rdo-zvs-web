<?php

declare(strict_types=1);

namespace App\ScheduledJobs;

use App\Models\NotificationRule;
use App\Models\Petition;
use App\Services\NotificationRule\NotificationRuleEvaluator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

use function sprintf;

/**
 * Sends the notifications that come due without anyone saving an event, such as "3 days before the deadline".
 * Scheduled after SetPetitionTotals, so it counts from up-to-date deadlines.
 */
class EvaluateNotificationRules
{
    private const int BATCH_SIZE = 500;

    public function __construct(
        private readonly NotificationRuleEvaluator $notificationRuleEvaluator,
    ) {
    }

    public function __invoke(): void
    {
        Petition::query()
            ->whereIn('department_id', NotificationRule::query()->active()->select('department_id'))
            ->whereHas('firstAssignee')
            ->notArchived()
            ->chunk(self::BATCH_SIZE, function (Collection $petitions): void {
                foreach ($petitions as $petition) {
                    try {
                        $this->notificationRuleEvaluator->evaluate($petition);
                    } catch (Throwable $throwable) {
                        Log::error(sprintf('Failed to evaluate notification rules for petition ID: %s', $petition->id), [
                            'exception' => $throwable,
                        ]);
                    }
                }
            });
    }
}
