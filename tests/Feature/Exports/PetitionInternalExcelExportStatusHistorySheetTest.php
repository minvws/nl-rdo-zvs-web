<?php

declare(strict_types=1);

namespace Tests\Feature\Exports;

use App\Enums\ExportType;
use App\Exports\ExportCriteria;
use App\Exports\PetitionInternalExcelExportStatusHistorySheet;
use App\Facades\DisplayDate;
use App\Models\Petition;
use App\Models\PetitionStatus;
use App\Models\PetitionStatusHistory;
use App\Models\PetitionType;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\DateRange;
use Tests\Feature\FeatureTestCase;

use function __;
use function sprintf;

class PetitionInternalExcelExportStatusHistorySheetTest extends FeatureTestCase
{
    public function testMap(): void
    {
        $petition = Petition::factory()->create();
        $petitionStatus = PetitionStatus::factory()->create();
        $history = PetitionStatusHistory::factory()
            ->recycle($petition)
            ->recycle($petitionStatus)
            ->create();

        $sheet = new PetitionInternalExcelExportStatusHistorySheet(
            new ExportCriteria(
                new PetitionType(),
                ExportType::INTERNAL,
                new DateRange(CalendarDate::today(), CalendarDate::today()),
            ),
        );

        $result = $sheet->map($history);

        $this->assertEquals([
            $petition->number,
            __(sprintf('petition_status.%s', $petitionStatus->status_group->value)),
            $petitionStatus->status,
            DisplayDate::datetime($history->created_at),
        ], $result);
    }
}
