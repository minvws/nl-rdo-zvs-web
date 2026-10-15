<?php

declare(strict_types=1);

namespace Tests\Unit\Services\PeriodGenerators;

use App\Enums\PetitionEventType;
use App\Enums\SuspensionType;
use App\Services\PeriodGenerators\SuspensionPeriodGenerator;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\EventCalendar;
use App\ValueObjects\EventCalendarDay;
use App\ValueObjects\PetitionEventData;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Helpers\InspectsEventCalendar;
use Tests\TestCase;

use function collect;
use function count;

class SuspensionPeriodGeneratorTest extends TestCase
{
    use InspectsEventCalendar;

    /**
     * Every letter is a [date, duration] pair, the suspension starts the day after the letter
     *
     * @return array<string, array{
     *     letters: list<array{string, int|null}>,
     *     ends: list<string>,
     *     suspendedDays: list<string>,
     * }>
     */
    public static function suspensionProvider(): array
    {
        return [
            'no letter suspends nothing' => [
                'letters' => [],
                'ends' => ['2025-01-12'],
                'suspendedDays' => [],
            ],
            'a letter without an end suspends its full duration' => [
                'letters' => [
                    ['2025-01-10', 3],
                ],
                'ends' => [],
                'suspendedDays' => ['2025-01-11', '2025-01-12', '2025-01-13'],
            ],
            'a letter without a duration or an end suspends nothing' => [
                'letters' => [
                    ['2025-01-10', null],
                ],
                'ends' => [],
                'suspendedDays' => [],
            ],
            'a suspension of a single day' => [
                'letters' => [
                    ['2025-01-10', 1],
                ],
                'ends' => [],
                'suspendedDays' => ['2025-01-11'],
            ],
            'an end cuts the suspension short; the end day itself is not suspended' => [
                'letters' => [
                    ['2025-01-10', 10],
                ],
                'ends' => ['2025-01-14'],
                'suspendedDays' => ['2025-01-11', '2025-01-12', '2025-01-13'],
            ],
            'an end on the last planned day' => [
                'letters' => [
                    ['2025-01-10', 3],
                ],
                'ends' => ['2025-01-13'],
                'suspendedDays' => ['2025-01-11', '2025-01-12'],
            ],
            'an end on the first suspended day suspends nothing' => [
                'letters' => [
                    ['2025-01-10', 3],
                ],
                'ends' => ['2025-01-11'],
                'suspendedDays' => [],
            ],
            'an end before the letter is ignored' => [
                'letters' => [
                    ['2025-01-10', 2],
                ],
                'ends' => ['2025-01-05'],
                'suspendedDays' => ['2025-01-11', '2025-01-12'],
            ],
            'a suspension crossing the turn of the year' => [
                'letters' => [
                    ['2024-12-30', 4],
                ],
                'ends' => [],
                'suspendedDays' => ['2024-12-31', '2025-01-01', '2025-01-02', '2025-01-03'],
            ],
            'two suspensions that each end early' => [
                'letters' => [
                    ['2025-01-10', 5],
                    ['2025-01-20', 5],
                ],
                'ends' => ['2025-01-23', '2025-01-12'],
                'suspendedDays' => ['2025-01-11', '2025-01-21', '2025-01-22'],
            ],
            'a new suspension after the previous one was ended' => [
                'letters' => [
                    ['2025-01-10', 3],
                    ['2025-01-20', 5],
                ],
                'ends' => ['2025-01-13', '2025-01-25'],
                'suspendedDays' => ['2025-01-11', '2025-01-12', '2025-01-21', '2025-01-22', '2025-01-23', '2025-01-24'],
            ],
        ];
    }

    /**
     * @param list<array{string, int|null}> $letters
     * @param list<string> $ends
     * @param list<string> $suspendedDays
     */
    #[Test]
    #[DataProvider('suspensionProvider')]
    public function testSuspension(array $letters, array $ends, array $suspendedDays): void
    {
        $events = collect([
            ...collect($letters)->map(static fn(array $letter): PetitionEventData => new PetitionEventData(
                type: PetitionEventType::LETTER_OF_SUSPENSION_SENT,
                date: CalendarDate::create($letter[0]),
                createdAt: CarbonImmutable::now(),
                duration: $letter[1],
                suspensionType: SuspensionType::SUSPENSION,
            )),
            ...collect($ends)->map(static fn(string $end): PetitionEventData => new PetitionEventData(
                type: PetitionEventType::SUSPENSION_END,
                date: CalendarDate::create($end),
                createdAt: CarbonImmutable::now(),
            )),
        ]);
        $calendar = new EventCalendar();

        new SuspensionPeriodGenerator()->generate($events, $calendar);

        $this->assertSame($suspendedDays, $this->datesWhere(
            $calendar,
            static fn(EventCalendarDay $day): bool => $day->suspensionType === SuspensionType::SUSPENSION,
        ));
        $this->assertCount(count($suspendedDays), $calendar->all());
    }

    /**
     * @return array<string, array{SuspensionType}>
     */
    public static function suspensionTypeProvider(): array
    {
        return collect(SuspensionType::cases())
            ->mapWithKeys(static fn(SuspensionType $type): array => [$type->value => [$type]])
            ->all();
    }

    #[Test]
    #[DataProvider('suspensionTypeProvider')]
    public function testSuspendedDaysCarryTheTypeOfTheLetter(SuspensionType $type): void
    {
        $calendar = new EventCalendar();

        new SuspensionPeriodGenerator()->generate(collect([
            new PetitionEventData(
                type: PetitionEventType::LETTER_OF_SUSPENSION_SENT,
                date: CalendarDate::create('2025-01-10'),
                createdAt: CarbonImmutable::now(),
                duration: 2,
                suspensionType: $type,
            ),
        ]), $calendar);

        $this->assertSame($type, $calendar->findDay(CalendarDate::create('2025-01-11'))?->suspensionType);
        $this->assertSame($type, $calendar->findDay(CalendarDate::create('2025-01-12'))?->suspensionType);
    }
}
