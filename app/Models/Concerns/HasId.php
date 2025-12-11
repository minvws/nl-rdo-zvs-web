<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Casts\UuidCast;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\UuidInterface;
use Webmozart\Assert\Assert;

/**
 * @property UuidInterface $id
 */
trait HasId
{
    use HasUuids;

    public function initializeHasId(): static
    {
        return $this->mergeCasts([
            'id' => UuidCast::class,
        ]);
    }

    /**
     * @param Model $model
     *
     * @return bool
     */
    // phpcs:ignore
    public function is($model)
    {
        // @codeCoverageIgnoreStart
        if (!$model instanceof self) {
            return false;
        }
        // @codeCoverageIgnoreEnd

        Assert::isInstanceOf($this->getKey(), UuidInterface::class);
        Assert::isInstanceOf($model->getKey(), UuidInterface::class);

        return
            $this->getKey()->toString() === $model->getKey()->toString()
            && $this->getTable() === $model->getTable()
            && $this->getConnectionName() === $model->getConnectionName();
    }
}
