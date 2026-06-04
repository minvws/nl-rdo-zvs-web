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
use Ramsey\Uuid\UuidInterface;
use Throwable;
use Webmozart\Assert\Assert;

use function filled;

readonly class PetitionStatusUpdateAction
{
    public function __construct(
        private DatabaseManager $databaseManager,
    ) {
    }

    /**
     * @param array{
     *     petition_status_id: string,
     *     petition_status_date: string,
     *     petition_status_comment: ?string
     * } $attributes
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

        $comment = filled($attributes['petition_status_comment'] ?? null)
            ? $attributes['petition_status_comment']
            : null;

        $latestHistoryEntry = $petition->petitionStatusHistories()
            ->orderByDesc('date')->latest()
            ->first();

        $isSameStatus = $petition->petition_status_id->equals($petitionStatusId);
        $isNewerOrEqualDate = $latestHistoryEntry === null
            || !$petitionStatusDate->isBefore($latestHistoryEntry->date);

        if ($isSameStatus && $isNewerOrEqualDate) {
            return;
        }

        if (!$isSameStatus && $isNewerOrEqualDate) {
            $this->updateStatusWithHistory($petition, $user, $currentStatus, $petitionStatusId, $petitionStatusDate, $comment);

            return;
        }

        $this->logStatusHistory($petition, $currentStatus, $petitionStatusDate, $comment);
    }

    /**
     * @throws Throwable
     */
    private function updateStatusWithHistory(
        Petition $petition,
        User $user,
        PetitionStatus $currentStatus,
        UuidInterface $petitionStatusId,
        CalendarDate $petitionStatusDate,
        ?string $comment,
    ): void {
        $timelineData = [
            'type' => TimelineType::STATUS_OCCURRENCE,
            'user_id' => $user->id,
            'data' => new ArrayObject([
                'previous_status' => $petition->petitionStatus->status,
                'current_status' => $currentStatus->status,
                'date' => $petitionStatusDate->toDateString(),
                'comment' => $comment,
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
            'comment' => $comment,
        ]);
    }
}
