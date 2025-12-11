<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Services\DisplayDateService;
use App\ValueObjects\CalendarDate;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\Feature\FeatureTestCase;
use Tests\Helpers\ConfigHelper;

class DisplayDateServiceTest extends FeatureTestCase
{
    #[TestWith(['15-8-2024 12:52:28', 'nl_NL', 'Europe/Amsterdam', '15-08-2024'])]
    #[TestWith(['25-5-2024 2:28:27', 'nl_NL', 'Europe/Amsterdam', '25-05-2024'])]
    #[TestWith(['3-5-2024 21:56:12', 'fr_FR', 'Europe/Amsterdam', '03-05-2024'])]
    #[TestWith(['3-5-2024 22:56:12', 'fr_FR', 'Europe/Amsterdam', '04-05-2024'])]
    #[TestWith(['13-12-2024 23:56:12', 'nl_NL', 'Europe/Amsterdam', '14-12-2024'])]
    #[TestWith(['13-12-2024 23:56:12', 'nl_NL', 'America/Vancouver', '13-12-2024'])]
    #[TestWith(['13-12-2024 23:56:12', 'nl_NL', 'Asia/Macau', '14-12-2024'])]
    public function testDate(string $date, string $locale, string $timezone, string $expectedOutput): void
    {
        $this->setLocaleAndDisplayTimezone($locale, $timezone);
        $this->assertEquals($expectedOutput, $this->getDisplayDate('date', $date));
    }

    #[TestWith(['15-8-2024 12:52:28', 'nl_NL', 'Europe/Amsterdam', '15-08-2024 14:52'])]
    #[TestWith(['25-5-2024 2:28:27', 'nl_NL', 'Europe/Amsterdam', '25-05-2024 04:28'])]
    #[TestWith(['3-5-2024 21:56:12', 'fr_FR', 'Europe/Amsterdam', '03-05-2024 23:56'])]
    #[TestWith(['3-5-2024 22:56:12', 'fr_FR', 'Europe/Amsterdam', '04-05-2024 00:56'])]
    #[TestWith(['13-12-2024 23:56:12', 'nl_NL', 'Europe/Amsterdam', '14-12-2024 00:56'])]
    #[TestWith(['13-12-2024 23:56:12', 'nl_NL', 'America/Vancouver', '13-12-2024 15:56'])]
    #[TestWith(['13-12-2024 23:56:12', 'nl_NL', 'Asia/Macau', '14-12-2024 07:56'])]
    public function testDateTime(string $date, string $locale, string $timezone, string $expectedOutput): void
    {
        $this->setLocaleAndDisplayTimezone($locale, $timezone);
        $this->assertEquals($expectedOutput, $this->getDisplayDate('datetime', $date));
    }

    #[TestWith(['13-12-2024', '14-12-2024', 1])]
    #[TestWith(['13-12-2024', '15-12-2024', 2])]
    #[TestWith(['13-12-2024', '23-12-2024', 10])]
    public function testDiffInDays(string $start, string $end, int $expectedOutput): void
    {
        $displayDateService = $this->getDisplayDateService();
        $diff = $displayDateService->diffInDays(
            CalendarDate::createFromFormat('d-m-Y', $start),
            CalendarDate::createFromFormat('d-m-Y', $end),
        );

        $this->assertEquals($expectedOutput, $diff);
    }

    #[TestWith(['15-8-2024 12:52:28', 'nl_NL', 'Europe/Amsterdam', '15 augustus 2024 om 14:52'])]
    #[TestWith(['25-5-2024 2:28:27', 'nl_NL', 'Europe/Amsterdam', '25 mei 2024 om 04:28'])]
    #[TestWith(['3-5-2024 21:56:12', 'fr_FR', 'Europe/Amsterdam', '3 mai 2024 om 23:56'])]
    #[TestWith(['3-5-2024 22:56:12', 'fr_FR', 'Europe/Amsterdam', '4 mai 2024 om 00:56'])]
    #[TestWith(['13-12-2024 23:56:12', 'nl_NL', 'Europe/Amsterdam', '14 december 2024 om 00:56'])]
    #[TestWith(['13-12-2024 23:56:12', 'nl_NL', 'America/Vancouver', '13 december 2024 om 15:56'])]
    #[TestWith(['13-12-2024 23:56:12', 'nl_NL', 'Asia/Macau', '14 december 2024 om 07:56'])]
    public function testSentence(string $date, string $locale, string $timezone, string $expectedOutput): void
    {
        $this->setLocaleAndDisplayTimezone($locale, $timezone);
        $this->assertEquals($expectedOutput, $this->getDisplayDate('sentence', $date));
    }

    public function testSentenceWithCalendarDate(): void
    {
        ConfigHelper::set('app.locale', 'nl_NL');
        ConfigHelper::set('app.display_timezone', 'Europe/Amsterdam');

        $displayDateService = $this->getDisplayDateService();
        $sentence = $displayDateService->sentence(CalendarDate::createFromFormat('d-m-Y', '19-09-2024'));

        $this->assertEquals('19 september 2024', $sentence);
    }

    #[TestWith(['15-8-2024 12:52:28', 'nl_NL', 'Europe/Amsterdam', '14:52'])]
    #[TestWith(['25-5-2024 2:28:27', 'nl_NL', 'Europe/Amsterdam', '04:28'])]
    #[TestWith(['3-5-2024 21:56:12', 'fr_FR', 'Europe/Amsterdam', '23:56'])]
    #[TestWith(['3-5-2024 22:56:12', 'fr_FR', 'Europe/Amsterdam', '00:56'])]
    #[TestWith(['13-12-2024 23:56:12', 'nl_NL', 'Europe/Amsterdam', '00:56'])]
    #[TestWith(['13-12-2024 23:56:12', 'nl_NL', 'America/Vancouver', '15:56'])]
    #[TestWith(['13-12-2024 23:56:12', 'nl_NL', 'Asia/Macau', '07:56'])]
    public function testTime(string $date, string $locale, string $timezone, string $expectedOutput): void
    {
        $this->setLocaleAndDisplayTimezone($locale, $timezone);
        $this->assertEquals($expectedOutput, $this->getDisplayDate('time', $date));
    }

    #[TestWith(['Europe/Amsterdam'])]
    #[TestWith(['America/Vancouver'])]
    #[TestWith(['Asia/Macau'])]
    public function testShift(string $displayTimezone): void
    {
        ConfigHelper::set('app.display_timezone', $displayTimezone);

        $displayDateService = $this->getDisplayDateService();
        $result = $displayDateService->shift(CarbonImmutable::now()->utc());

        $this->assertEquals($displayTimezone, $result->timezone);
    }

    private function getDisplayDateService(): DisplayDateService
    {
        return $this->app->get(DisplayDateService::class);
    }

    private function getDisplayDate(string $method, string $date): string
    {
        $displayDateService = $this->getDisplayDateService();

        return $displayDateService->$method(CarbonImmutable::createFromFormat('d-m-Y H:i:s', $date)->utc());
    }

    private function setLocaleAndDisplayTimezone(string $locale, string $displayTimezone): void
    {
        ConfigHelper::set('app.locale', $locale);
        ConfigHelper::set('app.display_timezone', $displayTimezone);
    }
}
