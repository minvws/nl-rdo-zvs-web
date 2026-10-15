<?php

declare(strict_types=1);

namespace App\Collections;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends Collection<int, Tag>
 */
class TagCollection extends Collection
{
    public function toString(): string
    {
        return $this->map(static fn(Tag $tag): string => $tag->name)->join(', ');
    }
}
