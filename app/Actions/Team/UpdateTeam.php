<?php

declare(strict_types=1);

namespace App\Actions\Team;

use App\Models\Team;

class UpdateTeam
{
    /**
     * @param array<string, mixed> $data
     */
    public function execute(Team $team, array $data): Team
    {
        $team->update($data);

        return $team;
    }
}
