<?php

declare(strict_types=1);

namespace App\ValueObjects;

use InvalidArgumentException;

readonly class PenaltyData
{
    public function __construct(
        public int $amount,
        public int $duration,
    ) {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Penalty amount must be positive');
        }

        if ($duration <= 0) {
            throw new InvalidArgumentException('Penalty duration must be positive');
        }
    }

    /**
     * @param array{amount: int, duration: int} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(amount: $data['amount'], duration: $data['duration']);
    }

    /**
     * @return array{amount: int, duration: int}
     */
    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'duration' => $this->duration,
        ];
    }
}
