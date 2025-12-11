<?php

declare(strict_types=1);

namespace App\Actions\Petition;

use App\Enums\TimelineType;
use App\Models\Petition;
use App\Models\PetitionStatus;
use App\Models\PetitionStatusHistory;
use App\Models\User;
use App\ValueObjects\CalendarDate;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Casts\ArrayObject;
use Ramsey\Uuid\Uuid;
use Throwable;
use Webmozart\Assert\Assert;

readonly class PetitionStatusUpdateAction
{
    public function __construct(
        private DatabaseManager $databaseManager,
    ) {
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @throws Throwable
     */
    public function execute(Petition $petition, User $user, array $attributes): void
    {
        Assert::string($attributes['petition_status_id']);
        Assert::string($attributes['petition_status_date']);

        $petitionStatusId = Uuid::fromString($attributes['petition_status_id']);
        $petitionStatusDate = CalendarDate::create($attributes['petition_status_date']);
        $currentStatus = PetitionStatus::query()->findSole($petitionStatusId);

        $comment = null;

        if (isset($attributes['petition_status_comment']) && $attributes['petition_status_comment'] !== '') {
            Assert::nullOrStringNotEmpty($attributes['petition_status_comment']);
            $comment = $attributes['petition_status_comment'];
        }

        $timelineData = [
            'type' => TimelineType::STATUS_OCCURRENCE,
            'user_id' => $user->id,
            'data' => new ArrayObject([
                'previous_status' => $petition->petitionStatus->status,
                'current_status' => $currentStatus->status,
                'date' => $petitionStatusDate->toDateString(),
                'comment' => $comment ?? null,
            ]),
        ];

        $this->databaseManager->transaction(
            function () use ($petition, $timelineData, $currentStatus, $petitionStatusId, $petitionStatusDate, $comment): void {
                $petition->update(['petition_status_id' => $petitionStatusId]);
                $petition->timelineItems()->create($timelineData);
                $this->logStatusHistory($petition, $currentStatus, $petitionStatusDate, $comment);
            },
        );
    }

    private function logStatusHistory(Petition $petition, PetitionStatus $currentStatus, CalendarDate $date, ?string $comment): void
    {
        PetitionStatusHistory::query()->create([
            'petition_id' => $petition->id,
            'petition_status_id' => $currentStatus->id,
            'created_at' => CarbonImmutable::now(),
            'date' => $date,
            'comment' => $comment ?? null,
        ]);
    }
}
