<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use App\Http\CspNonceGenerator;
use Illuminate\Support\Facades\Vite;
use Tests\Feature\FeatureTestCase;

class CspNonceGeneratorTest extends FeatureTestCase
{
    public function testNonceShouldEqualViteCspNonce(): void
    {
        /** @var CspNonceGenerator $cspNonceGenerator */
        $cspNonceGenerator = $this->app->get(CspNonceGenerator::class);
        $nonce = $cspNonceGenerator->generate();

        $this->assertEquals($nonce, Vite::cspNonce());
    }
}
