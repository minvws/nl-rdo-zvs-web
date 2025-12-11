<?php

declare(strict_types=1);

namespace App\Rules;

use App\Services\Virusscanner\VirusscannerInterface;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use Psr\Log\LoggerInterface;
use Webmozart\Assert\Assert;

use function fopen;

readonly class VirusscannerRule implements ValidationRule
{
    public function __construct(
        private LoggerInterface $logger,
        private VirusscannerInterface $virusscannerService,
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value instanceof UploadedFile) {
            $fail('The :attribute must be an uploaded file');

            return;
        }

        $resource = fopen($value->getRealPath(), 'rb');
        Assert::resource($resource);

        $this->logger->debug('virusscan start for file', [
            'originalName' => $value->getClientOriginalName(),
        ]);

        $result = $this->virusscannerService->isResourceClean($resource);

        if ($result === false) {
            $this->logger->info('virusscan result: NOT OK', [
                'originalName' => $value->getClientOriginalName(),
            ]);

            $fail('The :attribute is not a valid file.');

            return;
        }

        $this->logger->debug('virusscan result: OK', [
            'originalName' => $value->getClientOriginalName(),
        ]);
    }
}
