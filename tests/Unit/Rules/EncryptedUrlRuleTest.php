<?php

declare(strict_types=1);

namespace Tests\Unit\Rules;

use App\Rules\EncryptedUrlRule;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class EncryptedUrlRuleTest extends TestCase
{
    private EncryptedUrlRule $rule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rule = new EncryptedUrlRule();
    }

    private function createFailCallback(bool &$failCalled): callable
    {
        return function (string $message) use (&$failCalled): object {
            $failCalled = true;
            return new class {
                public function translate(): string
                {
                    return '';
                }
            };
        };
    }

    public function testValidEncryptedUrl(): void
    {
        $validUrl = 'https://example.com/test';
        $encryptedUrl = Crypt::encryptString($validUrl);

        $failCalled = false;
        $failCallback = $this->createFailCallback($failCalled);

        $this->rule->validate('test_attribute', $encryptedUrl, $failCallback);

        $this->assertFalse($failCalled);
    }

    public function testNonStringValue(): void
    {
        $failCalled = false;
        $failCallback = $this->createFailCallback($failCalled);

        $this->rule->validate('test_attribute', 123, $failCallback);

        $this->assertTrue($failCalled);
    }

    public function testInvalidEncryptedString(): void
    {
        $failCalled = false;
        $failCallback = $this->createFailCallback($failCalled);

        $this->rule->validate('test_attribute', 'invalid-encrypted-string', $failCallback);

        $this->assertTrue($failCalled);
    }

    public function testValidEncryptedStringButInvalidUrl(): void
    {
        $invalidUrl = 'not-a-valid-url';
        $encryptedInvalidUrl = Crypt::encryptString($invalidUrl);

        $failCalled = false;
        $failCallback = $this->createFailCallback($failCalled);

        $this->rule->validate('test_attribute', $encryptedInvalidUrl, $failCallback);

        $this->assertTrue($failCalled);
    }

    public function testValidEncryptedRelativeUrl(): void
    {
        $relativeUrl = '/test/path';
        $encryptedRelativeUrl = Crypt::encryptString($relativeUrl);

        $failCalled = false;
        $failCallback = $this->createFailCallback($failCalled);

        $this->rule->validate('test_attribute', $encryptedRelativeUrl, $failCallback);

        $this->assertTrue($failCalled);
    }

    public function testValidEncryptedHttpUrl(): void
    {
        $httpUrl = 'http://example.com/test';
        $encryptedHttpUrl = Crypt::encryptString($httpUrl);

        $failCalled = false;
        $failCallback = $this->createFailCallback($failCalled);

        $this->rule->validate('test_attribute', $encryptedHttpUrl, $failCallback);

        $this->assertFalse($failCalled);
    }

    public function testEmptyString(): void
    {
        $failCalled = false;
        $failCallback = $this->createFailCallback($failCalled);

        $this->rule->validate('test_attribute', '', $failCallback);

        $this->assertTrue($failCalled);
    }

    public function testArrayValue(): void
    {
        $failCalled = false;
        $failCallback = $this->createFailCallback($failCalled);

        $this->rule->validate('test_attribute', ['not', 'a', 'string'], $failCallback);

        $this->assertTrue($failCalled);
    }

    public function testNullValue(): void
    {
        $failCalled = false;
        $failCallback = $this->createFailCallback($failCalled);

        $this->rule->validate('test_attribute', null, $failCallback);

        $this->assertTrue($failCalled);
    }

    public function testValidEncryptedUrlWithComplexPath(): void
    {
        $complexUrl = 'https://example.com/test/path?param=value&other=123#anchor';
        $encryptedComplexUrl = Crypt::encryptString($complexUrl);

        $failCalled = false;
        $failCallback = $this->createFailCallback($failCalled);

        $this->rule->validate('test_attribute', $encryptedComplexUrl, $failCallback);

        $this->assertFalse($failCalled);
    }
}
