<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Contracts\Session\Session;
use Illuminate\Database\DatabaseManager;
use Ramsey\Uuid\UuidInterface;

readonly class SessionRepository
{
    public function __construct(
        private Session $session,
        private DatabaseManager $databaseManager,
    ) {
    }

    public function regenerate(): void
    {
        $this->session->flush();
        $this->session->invalidate();
        $this->session->regenerateToken();
    }

    public function get(string $key): mixed
    {
        return $this->session->get($key);
    }

    public function save(string $key, mixed $value): void
    {
        $this->session->put($key, $value);
    }

    public function invalidateUser(UuidInterface $userId): void
    {
        $this->databaseManager
            ->table('sessions')
            ->where(['user_id' => (string) $userId])
            ->delete();
    }
}
