<?php

declare(strict_types=1);

namespace App\Actions\Tag;

use App\Models\Tag;
use Illuminate\Database\DatabaseManager;
use Throwable;

readonly class DeleteTagAction
{
    public function __construct(
        private DatabaseManager $databaseManager,
    ) {
    }

    /**
     * The link table refuses to delete a tag that is still in use, so the links go first, in the same transaction.
     *
     * @throws Throwable
     */
    public function execute(Tag $tag): void
    {
        $this->databaseManager->transaction(static function () use ($tag): void {
            $tag->petitions()->detach();
            $tag->delete();
        });
    }
}
