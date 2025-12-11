<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Models\PublicHoliday;
use App\Services\LegalTermDeadlineCalculator;
use App\ValueObjects\CalendarDate;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\FeatureTestCase;

use function collect;
use function count;

class LegalTermDeadlineCalculatorTest extends FeatureTestCase
{
    /**
     * @param array<string> $publicHolidays
     */
    #[DataProvider('deadlineDataProvider')]
    public function testCalculateDeadline(array $publicHolidays, CalendarDate $proposedDate, CalendarDate $deadline): void
    {
        PublicHoliday::factory()
            ->count(count($publicHolidays))
            ->sequence(...collect($publicHolidays)->map(static fn($publicHoliday) => ['date' => $publicHoliday])->toArray())
            ->create();
        $calculator = $this->getLegalTermDeadlineCalculator();

        $currentDeadline = $calculator->calculate($proposedDate);

        $this->assertEquals($deadline, $currentDeadline);
    }

    public static function deadlineDataProvider(): array
    {
        return [
            [
                ['2024-03-29', '2024-03-31', '2024-04-01'],
                CalendarDate::create('2024-03-29'),
                CalendarDate::create('2024-04-02'),
            ],
            [
                [],
                CalendarDate::create('2024-04-20'),
                CalendarDate::create('2024-04-22'),
            ],
            [
                [],
                CalendarDate::create('2024-04-21'),
                CalendarDate::create('2024-04-22'),
            ],
            [
                ['2024-12-25', '2024-12-26'],
                CalendarDate::create('2024-12-25'),
                CalendarDate::create('2024-12-27'),
            ],
            [
                ['2024-12-26'],
                CalendarDate::create('2024-12-26'),
                CalendarDate::create('2024-12-27'),
            ],
        ];
    }

    /**
     * @param array<string> $publicHolidays
     */
    #[DataProvider('weekendOrHolidayDataProvider')]
    public function testIsWeekendOrHoliday(array $publicHolidays, CalendarDate $proposedDate, bool $expectedResult): void
    {
        PublicHoliday::factory()
            ->count(count($publicHolidays))
            ->sequence(...collect($publicHolidays)->map(static fn($publicHoliday) => ['date' => $publicHoliday])->toArray())
            ->create();

        $calculator = $this->getLegalTermDeadlineCalculator();
        $result = $calculator->isWeekendOrHoliday($proposedDate);

        $this->assertEquals($expectedResult, $result);
    }

    public static function weekendOrHolidayDataProvider(): array
    {
        return [
            [
                ['2024-03-29', '2024-03-31', '2024-04-01'],
                CalendarDate::create('2024-03-29'),
                true,
            ],
            [
                [],
                CalendarDate::create('2024-04-19'),
                false,
            ],
            [
                [],
                CalendarDate::create('2024-04-21'),
                true,
            ],
        ];
    }

    private function getLegalTermDeadlineCalculator(): LegalTermDeadlineCalculator
    {
        return $this->app->get(LegalTermDeadlineCalculator::class);
    }
}
