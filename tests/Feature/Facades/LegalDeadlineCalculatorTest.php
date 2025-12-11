<?php

declare(strict_types=1);

namespace Tests\Feature\Facades;

use App\Facades\LegalTermDeadlineCalculator;
use App\Models\PublicHoliday;
use App\ValueObjects\CalendarDate;
use Carbon\CarbonImmutable;
use Tests\Feature\FeatureTestCase;

use function today;

class LegalDeadlineCalculatorTest extends FeatureTestCase
{
    public function testCalculateLegalDeadline(): void
    {
        $firstSaturday = today()->next('Saturday');

        PublicHoliday::factory()->create([
            'date' => CalendarDate::instance($firstSaturday)->addDays(2),
        ]);

        $deadline = LegalTermDeadlineCalculator::calculate(CalendarDate::instance($firstSaturday));

        $this->assertTrue(
            CalendarDate::instance($firstSaturday)->addDays(3)->equals($deadline),
        );

        $this->assertTrue(
            CarbonImmutable::createFromFormat('Y-m-d', $deadline->format('Y-m-d'))->isTuesday(),
        );
    }
}
