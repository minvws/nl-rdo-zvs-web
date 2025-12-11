<?php

declare(strict_types=1);

namespace App\Actions\CustomDates;

use App\Enums\CustomDateLabel;
use App\Models\PetitionCustomDate;
use App\Models\PetitionType;
use App\Models\PetitionTypeCustomDateLabel;
use Illuminate\Database\DatabaseManager;

readonly class CustomDatesDeleteAction
{
    public function __construct(
        private DatabaseManager $databaseManager,
    ) {
    }

    public function execute(
        PetitionType $petitionType,
        CustomDateLabel $customDateLabel,
    ): void {
        $this->databaseManager->transaction(static function () use ($petitionType, $customDateLabel): void {
            PetitionTypeCustomDateLabel::query()
                ->where('petition_type_id', $petitionType->id)
                ->where('date_label', $customDateLabel)
                ->delete();

            PetitionCustomDate::query()
                ->whereHas('petition', static function ($query) use ($petitionType): void {
                    $query->where('petition_type_id', $petitionType->id);
                })
                ->where('date_label', $customDateLabel)
                ->delete();
        });
    }
}
