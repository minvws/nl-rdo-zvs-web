<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Services\EncryptionService;
use Illuminate\Support\Facades\Crypt;
use Tests\Feature\FeatureTestCase;

class EncryptionServiceTest extends FeatureTestCase
{
    public function testDecrypt(): void
    {
        $value = $this->faker->word();
        $payload = Crypt::encrypt($value);

        $encryptionService = $this->getEncryptionService();
        $output = $encryptionService->decrypt($payload);

        $this->assertEquals($value, $output);
    }

    public function testEncrypt(): void
    {
        $value = $this->faker->word();

        $encryptionService = $this->getEncryptionService();
        $output = $encryptionService->encrypt($value);

        $this->assertNotEquals($output, $value);
        $this->assertEquals(Crypt::decrypt($output), $value);
    }

    private function getEncryptionService(): EncryptionService
    {
        return $this->app->get(EncryptionService::class);
    }
}
