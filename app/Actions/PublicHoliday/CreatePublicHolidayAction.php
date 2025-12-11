<?php

declare(strict_types=1);

namespace App\Actions\PublicHoliday;

use App\Models\PublicHoliday;

class CreatePublicHolidayAction
{
    /**
     * @param array<string, mixed> $data
     */
    public function execute(array $data): PublicHoliday
    {
        return PublicHoliday::query()->create($data);
    }
}
