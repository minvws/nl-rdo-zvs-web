<?php

declare(strict_types=1);

namespace App\Actions\PublicHoliday;

use App\Models\PublicHoliday;

class UpdatePublicHolidayAction
{
    /**
     * @param array<string, mixed> $data
     */
    public function execute(PublicHoliday $publicHoliday, array $data): void
    {
        $publicHoliday->update($data);
    }
}
