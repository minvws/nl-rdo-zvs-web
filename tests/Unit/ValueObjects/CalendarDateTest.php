<?php

declare(strict_types=1);

namespace Tests\Unit\ValueObjects;

use App\ValueObjects\CalendarDate;
use App\ValueObjects\CalendarDateException;
use Tests\TestCase;

class CalendarDateTest extends TestCase
{
    public function testInstance(): void
    {
        $dateTime = $this->faker->dateTime();

        $calendarDate = CalendarDate::instance($dateTime);

        $this->assertEquals($dateTime->format('Y-m-d'), $calendarDate->format('Y-m-d'));
    }

    public function testCreate(): void
    {
        $dateTime = $this->faker->dateTime();

        $calendarDate = CalendarDate::create($dateTime->toString());

        $this->assertEquals($dateTime->format('Y-m-d'), $calendarDate->format('Y-m-d'));
    }

    public function testCreateFromFormat(): void
    {
        $dateTime = $this->faker->dateTime();

        $calendarDate = CalendarDate::createFromFormat('d-m-Y', $dateTime->format('d-m-Y'));

        $this->assertEquals($dateTime->format('Y-m-d'), $calendarDate->format('Y-m-d'));
    }

    public function testCreateWithInvalidFormat(): void
    {
        $this->expectException(CalendarDateException::class);
        CalendarDate::create($this->faker->uuid()->toString());
    }

    public function testToString(): void
    {
        $dateTime = $this->faker->dateTime();

        $calendarDate = CalendarDate::instance($dateTime);

        $this->assertEquals($dateTime->format('Y-m-d'), $calendarDate->toDateString());
    }

    public function testToDateString(): void
    {
        $dateTime = $this->faker->dateTime();

        $calendarDate = CalendarDate::instance($dateTime);

        $this->assertEquals($dateTime->format('Y-m-d'), $calendarDate->toDateString());
    }

    public function testAddDay(): void
    {
        $dateTime = $this->faker->dateTime();

        $calendarDate = CalendarDate::instance($dateTime);
        $newCalendarDate = $calendarDate->addDay();

        $this->assertEquals($dateTime->addDay()->format('Y-m-d'), $newCalendarDate->format('Y-m-d'));
    }

    public function testAddDays(): void
    {
        $dateTime = $this->faker->dateTime();
        $daysToAdd = $this->faker->randomDigit();

        $calendarDate = CalendarDate::instance($dateTime);
        $newCalendarDate = $calendarDate->addDays($daysToAdd);

        $this->assertEquals($dateTime->addDays($daysToAdd)->format('Y-m-d'), $newCalendarDate->format('Y-m-d'));
    }

    public function testSubDays(): void
    {
        $dateTime = $this->faker->dateTime();
        $daysToSubtract = $this->faker->randomDigit();

        $calendarDate = CalendarDate::instance($dateTime);
        $newCalendarDate = $calendarDate->subDays($daysToSubtract);

        $this->assertEquals($dateTime->subDays($daysToSubtract)->format('Y-m-d'), $newCalendarDate->format('Y-m-d'));
    }

    public function testIsBefore(): void
    {
        $dateTime = $this->faker->dateTime();

        $calendarDate = CalendarDate::instance($dateTime);
        $newCalendarDate = CalendarDate::instance($dateTime)->addDays($this->faker->numberBetween(1, 5));

        $this->assertTrue($calendarDate->isBefore($newCalendarDate));
    }

    public function testSubDay(): void
    {
        $dateTime = $this->faker->dateTime();

        $calendarDate = CalendarDate::instance($dateTime);
        $newCalendarDate = $calendarDate->subDay();

        $this->assertEquals($dateTime->subDay()->format('Y-m-d'), $newCalendarDate->format('Y-m-d'));
    }

    public function testFormat(): void
    {
        $dateTime = $this->faker->dateTime();

        $calendarDate = CalendarDate::instance($dateTime);

        $this->assertEquals($dateTime->format('Y-m-d'), $calendarDate->format('Y-m-d'));
    }

    public function testIsoFormat(): void
    {
        $dateTime = $this->faker->dateTime();

        $calendarDate = CalendarDate::instance($dateTime);

        $this->assertEquals($dateTime->isoFormat('DD-MM-YYYY'), $calendarDate->isoFormat('DD-MM-YYYY'));
    }

    public function testIsWeekend(): void
    {
        $dateTime = $this->faker->dateTime();

        $calendarDate = CalendarDate::instance($dateTime);

        $this->assertEquals($dateTime->isWeekend(), $calendarDate->isWeekend());
    }

    public function testIsInThePast(): void
    {
        $dateTime = $this->faker->dateTimeBetween('-1 year', '-1 day');
        $calendarDate = CalendarDate::instance($dateTime);
        $this->assertTrue($calendarDate->isInThePast());

        $calendarDate = CalendarDate::today();
        $this->assertFalse($calendarDate->isInThePast());

        $dateTime = $this->faker->dateTimeBetween('+1 day', '+1 year');
        $calendarDate = CalendarDate::instance($dateTime);
        $this->assertFalse($calendarDate->isInThePast());
    }

    public function testLocale(): void
    {
        $dateTime = '2024-3-19';

        $calendarDate = CalendarDate::create($dateTime);
        $calendarDate->locale('nl_NL');

        $this->assertEquals('19 maart 2024', $calendarDate->isoFormat('D MMMM YYYY'));
    }

    public function testGreaterThanLaterIsFalse(): void
    {
        $calendarDate = CalendarDate::today();
        $tomorrow = CalendarDate::today()->addDay();
        $this->assertFalse($calendarDate->greaterThan($tomorrow));
    }

    public function testGreaterThanEarlierIsTrue(): void
    {
        $calendarDate = CalendarDate::today();
        $yesterday = CalendarDate::today()->subDay();
        $this->assertTrue($calendarDate->greaterThan($yesterday));
    }

    public function testGreaterThanOrEqualsTo(): void
    {
        $calendarDate = CalendarDate::today();
        $today = CalendarDate::today();
        $this->assertTrue($calendarDate->greaterThanOrEqualTo($today));
        $yesterday = CalendarDate::today()->subDay();
        $this->assertTrue($calendarDate->greaterThanOrEqualTo($yesterday));
    }

    public function testLessThanOrEqualsTo(): void
    {
        $calendarDate = CalendarDate::today();
        $today = CalendarDate::today();
        $this->assertTrue($calendarDate->lessThanOrEqualTo($today));
        $tomorrow = CalendarDate::today()->addDay();
        $this->assertTrue($calendarDate->lessThanOrEqualTo($tomorrow));
    }

    public function testSerializeDate(): void
    {
        $dateString = '2024-03-19';
        $calendarDate = CalendarDate::create($dateString);
        $serialized = $calendarDate->jsonSerialize();
        $this->assertSame($dateString, $serialized);
    }
}
