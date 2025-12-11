<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\FormRequest;
use App\ValueObjects\CalendarDate;
use Tests\TestCase;

class FormRequestTest extends TestCase
{
    public function testCalendarDateOrNullReturnsNull(): void
    {
        $request = new class extends FormRequest {
        };

        $request->merge(['date' => null]);

        $this->assertNull($request->getCalendarDateOrNull('date'));
    }

    public function testCalendarDateOrNullReturnsCalendarDate(): void
    {
        $request = new class extends FormRequest {
        };

        $request->merge(['date' => '2022-01-01']);

        $this->assertInstanceOf(CalendarDate::class, $request->getCalendarDateOrNull('date'));
    }
}
