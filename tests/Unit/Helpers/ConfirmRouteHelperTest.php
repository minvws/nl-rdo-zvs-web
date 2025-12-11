<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Enums\RouteName;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

use function confirmRoute;
use function parse_str;
use function parse_url;
use function route;

use const PHP_URL_QUERY;

class ConfirmRouteHelperTest extends TestCase
{
    public function testConfirmRouteGeneratesCorrectUrl(): void
    {
        $confirmUrl = 'https://example.com/confirm-action';
        $cancelUrl = 'https://example.com/cancel-action';
        $message = 'Test message';

        $result = confirmRoute($confirmUrl, $cancelUrl, $message);

        $this->assertStringContainsString(route(RouteName::CONFIRM), $result);
        $this->assertStringContainsString('confirm_url=', $result);
        $this->assertStringContainsString('cancel_url=', $result);
        $this->assertStringContainsString('message=', $result);
    }

    public function testConfirmRouteEncryptsUrls(): void
    {
        $confirmUrl = 'https://example.com/confirm-action';
        $cancelUrl = 'https://example.com/cancel-action';
        $message = 'Test message';

        $result = confirmRoute($confirmUrl, $cancelUrl, $message);

        parse_str(parse_url($result, PHP_URL_QUERY), $queryParams);

        $this->assertEquals($confirmUrl, Crypt::decryptString($queryParams['confirm_url']));
        $this->assertEquals($cancelUrl, Crypt::decryptString($queryParams['cancel_url']));
        $this->assertEquals($message, $queryParams['message']);
    }
}
