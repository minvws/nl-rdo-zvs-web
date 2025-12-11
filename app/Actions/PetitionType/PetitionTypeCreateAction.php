<?php

declare(strict_types=1);

namespace App\Actions\PetitionType;

use App\Enums\StatusGroup;
use App\Models\PetitionStatus;
use App\Models\PetitionType;
use Illuminate\Database\DatabaseManager;

readonly class PetitionTypeCreateAction
{
    public function __construct(
        private DatabaseManager $databaseManager,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function execute(array $data): PetitionType
    {
        return $this->databaseManager->transaction(static function () use ($data): PetitionType {
            $petitionType = PetitionType::query()->create($data);
            PetitionStatus::query()->create([
                'petition_type_id' => $petitionType->id,
                'status_group' => StatusGroup::INTAKE->value,
                'status' => 'Standaard status',
                'bg_color' => '#FFDEEB',
                'order' => 1,
            ]);

            return $petitionType;
        });
    }
}
