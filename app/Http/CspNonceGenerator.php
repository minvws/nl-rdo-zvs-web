<?php

declare(strict_types=1);

namespace App\Http;

use Illuminate\Support\Facades\Vite;
use Spatie\Csp\Nonce\NonceGenerator;

class CspNonceGenerator implements NonceGenerator
{
    public function generate(): string
    {
        return Vite::useCspNonce();
    }
}
