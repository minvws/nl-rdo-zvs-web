<?php

declare(strict_types=1);

namespace App\Actions\Petition;

use App\Enums\TimelineType;
use App\Models\Petition;
use App\Models\User;
use Illuminate\Database\DatabaseManager;

use function collect;

class QuerysnapshotUpdateAction
{
    public function __construct(
        private readonly DatabaseManager $databaseManager,
    ) {
    }

    /**
     * @param array{querysnapshots: list<array{querysnapshot_type: string, querysnapshot_id: string}>} $data
     */
    public function execute(Petition $petition, User $user, array $data): void
    {
        $this->databaseManager->transaction(static function () use ($petition, $data, $user): void {
            $incoming = collect($data['querysnapshots'])
                ->reject(static fn(array $snapshot): bool => empty($snapshot['querysnapshot_id']));

            $incomingTypes = $incoming->pluck('querysnapshot_type');

            $petition->querysnapshots()
                ->whereNotIn('querysnapshot_type', $incomingTypes)
                ->delete();

            foreach ($incoming as $snapshot) {
                $petition->querysnapshots()
                    ->updateOrCreate(
                        ['querysnapshot_type' => $snapshot['querysnapshot_type']],
                        ['querysnapshot_id' => $snapshot['querysnapshot_id']],
                    );
            }

            $petition->timelineItems()->create([
                'user_id' => $user->id,
                'type' => TimelineType::QUERYSNAPSHOT_UPDATED,
                'data' => $data,
            ]);
        });
    }
}
