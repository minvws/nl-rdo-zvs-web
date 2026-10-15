<?php

declare(strict_types=1);

namespace App\Actions\Tag;

use App\Models\Tag;

class UpdateTagAction
{
    /**
     * @param array<string, mixed> $data
     */
    public function execute(Tag $tag, array $data): Tag
    {
        $tag->update($data);

        return $tag;
    }
}
